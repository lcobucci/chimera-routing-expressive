<?php
declare(strict_types=1);

namespace Lcobucci\Chimera\Routing\Expressive\DependencyInjection;

use Lcobucci\Chimera\Routing\Async;
use Lcobucci\Chimera\Routing\CreateAndFetch;
use Lcobucci\Chimera\Routing\CreateOnly;
use Lcobucci\Chimera\Routing\Dispatcher;
use Lcobucci\Chimera\Routing\ExecuteAndFetch;
use Lcobucci\Chimera\Routing\ExecuteOnly;
use Lcobucci\Chimera\Routing\Expressive\JsonConverter;
use Lcobucci\Chimera\Routing\Expressive\ResponseGenerator;
use Lcobucci\Chimera\Routing\FetchOnly;
use Ramsey\Uuid\Uuid;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\Reference;
use Zend\Expressive\AppFactory;
use Zend\Expressive\Application;
use Zend\Expressive\Router\FastRouteRouter;

final class RegisterServices implements CompilerPassInterface
{
    private const MESSAGE_INVALID_ROUTE = 'You must specify the "app", "route_name", "path", and "type" arguments in '
                                          . '"%s" (tag "%s").';

    private const MESSAGE_INVALID_MIDDLEWARE = 'You must specify the "app" argument in "%s" (tag "%s").';

    private const OVERRIDABLE_DEPENDENCIES = [
        'response_generator' => ResponseGenerator::class,
        'result_converter'   => JsonConverter::class,
        'router_config'      => null
    ];

    private const TYPES = [
        'fetch'         => ['methods' => ['GET'], 'callback' => 'fetchOnly'],
        'create'        => ['methods' => ['POST'], 'callback' => 'createOnly'],
        'create_fetch'  => ['methods' => ['POST'], 'callback' => 'createAndFetch'],
        'execute'       => ['methods' => ['PATCH', 'PUT', 'DELETE'], 'callback' => 'executeOnly'],
        'execute_fetch' => ['methods' => ['PATCH', 'PUT'], 'callback' => 'executeAndFetch'],
    ];

    /**
     * @var string
     */
    private $applicationName;

    /**
     * @var string
     */
    private $commandBusId;

    /**
     * @var string
     */
    private $queryBusId;

    /**
     * @var array
     */
    private $dependencies;

    public function __construct(
        string $applicationName,
        string $commandBusId,
        string $queryBusId,
        array $dependencies = []
    ) {
        $this->applicationName = $applicationName;
        $this->commandBusId    = $commandBusId;
        $this->queryBusId      = $queryBusId;
        $this->dependencies    = $dependencies;
    }

    public function process(ContainerBuilder $container)
    {
        $routes      = $this->extractRoutes($container);
        $middlewares = $this->extractMiddlewares($container);

        $this->registerApplication(
            $container,
            $routes[$this->applicationName] ?? [],
            $this->prioritiseMiddlewares($middlewares[$this->applicationName] ?? [])
        );
    }

    private function getReference(ContainerBuilder $container, string $service): Reference
    {
        if (! $container->hasDefinition($service)) {
            throw new ServiceNotFoundException($service);
        }

        return new Reference($service);
    }

    private function registerRouter(ContainerBuilder $container): Reference
    {
        $routerId = uniqid('chimera.http.router.');

        $container->setDefinition(
            $routerId,
            $this->createService(
                FastRouteRouter::class,
                [
                    null,
                    null,
                    $this->dependencies['router_config'] ?? self::OVERRIDABLE_DEPENDENCIES['router_config']
                ]
            )
        );

        return new Reference($routerId);
    }

    private function registerDispatcher(ContainerBuilder $container, Reference $router): Reference
    {
        $routerId = uniqid('chimera.http.dispatcher.');

        $container->setDefinition(
            $routerId,
            $this->createService(
                Dispatcher::class,
                [$this->registerResponseGenerator($container, $router)]
            )
        );

        return new Reference($routerId);
    }

    private function registerResponseGenerator(ContainerBuilder $container, Reference $router): Reference
    {
        if (isset($this->dependencies['response_generator'])) {
            return new Reference($this->dependencies['response_generator']);
        }

        $id = uniqid('chimera.http.response_generator.');

        $container->setDefinition(
            $id,
            $this->createService(
                self::OVERRIDABLE_DEPENDENCIES['response_generator'],
                [$router, $this->registerResultConverter($container)]
            )
        );

        return new Reference($id);
    }

    private function registerResultConverter(ContainerBuilder $container): Reference
    {
        if (isset($this->dependencies['result_converter'])) {
            return new Reference($this->dependencies['result_converter']);
        }

        $id = uniqid('chimera.http.result_converter.');

        $container->setDefinition(
            $id,
            $this->createService(self::OVERRIDABLE_DEPENDENCIES['result_converter'])
        );

        return new Reference($id);
    }

    /**
     * @return string[][][]
     *
     * @throws \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
     */
    private function extractRoutes(ContainerBuilder $container): array
    {
        $routes = [];

        foreach ($container->findTaggedServiceIds(Tags::ROUTE) as $serviceId => $tags) {
            foreach ($tags as $tag) {
                if (! isset($tag['app'], $tag['route_name'], $tag['path'], $tag['type'])) {
                    throw new InvalidArgumentException(
                        \sprintf(self::MESSAGE_INVALID_ROUTE, $serviceId, Tags::ROUTE)
                    );
                }

                if (isset($tag['methods'])) {
                    $tag['methods'] = explode(',', $tag['methods']);
                }

                $tag['async'] = (bool) ($tag['async'] ?? false);

                $routes[$tag['app']]   = $routes[$tag['app']] ?? [];
                $routes[$tag['app']][] = $tag;
            }
        }

        return $routes;
    }

    /**
     * @return string[][][]
     *
     * @throws \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
     */
    private function extractMiddlewares(ContainerBuilder $container): array
    {
        $middlewares = [];

        foreach ($container->findTaggedServiceIds(Tags::MIDDLEWARE) as $serviceId => $tags) {
            foreach ($tags as $tag) {
                if (! isset($tag['app'])) {
                    throw new InvalidArgumentException(
                        sprintf(self::MESSAGE_INVALID_MIDDLEWARE, $serviceId, Tags::MIDDLEWARE)
                    );
                }

                $priority = $tag['priority'] ?? 0;

                $middlewares[$tag['app']]              = $middlewares[$tag['app']] ?? [];
                $middlewares[$tag['app']][$priority]   = $middlewares[$tag['app']][$priority] ?? [];
                $middlewares[$tag['app']][$priority][] = $serviceId;
            }
        }

        return $middlewares;
    }

    /**
     * @param string[][] $middlewares
     *
     * @return string[]
     */
    private function prioritiseMiddlewares(array $middlewares): array
    {
        krsort($middlewares);

        $prioritised = [];

        foreach ($middlewares as $list) {
            foreach ($list as $reference) {
                $prioritised[] = $reference;
            }
        }

        return $prioritised;
    }

    private function registerServiceLocator(ContainerBuilder $container, array $services): Reference
    {
        return ServiceLocatorTagPass::register(
            $container,
            array_map(
                function (string $id): Reference {
                    return new Reference($id);
                },
                array_combine($services, $services)
            )
        );
    }

    private function createService(string $class, array $arguments = []): Definition
    {
        return (new Definition($class, $arguments))->setPublic(false);
    }

    private function registerApplication(
        ContainerBuilder $container,
        array $routes,
        array $middlewares
    ): void {
        $commandBus = $this->getReference($container, $this->commandBusId);
        $queryBus   = $this->getReference($container, $this->queryBusId);

        $router     = $this->registerRouter($container);
        $dispatcher = $this->registerDispatcher($container, $router);
        $routes     = $this->registerRoutes($container, $commandBus, $queryBus, $routes);

        $application    = new Definition(Application::class);
        $serviceLocator = $this->registerServiceLocator(
            $container,
            array_merge(
                $middlewares,
                [(string) $dispatcher],
                array_keys($routes)
            )
        );

        $application->setPublic(true)
                    ->setArguments([$serviceLocator, $router])
                    ->setFactory([AppFactory::class, 'create']);

        $this->appendMiddlewares($application, $dispatcher, $middlewares);
        $this->appendRoutes($application, $routes);

        $container->setDefinition($this->applicationName, $application);
    }

    private function registerRoutes(
        ContainerBuilder $container,
        Reference $commandBus,
        Reference $queryBus,
        array $routes
    ): array {
        $services = [];

        foreach ($routes as $route) {
            $serviceId = $this->registerRoute($container, $commandBus, $queryBus, $route);

            $services[$serviceId] = $route;
        }

        return $services;
    }

    private function registerRoute(
        ContainerBuilder $container,
        Reference $commandBus,
        Reference $queryBus,
        array $route
    ): string {
        $id = uniqid('chimera.http.route.');

        $container->setDefinition(
            $id,
            $this->{self::TYPES[$route['type']]['callback']}($route, $commandBus, $queryBus)
        );

        if ($route['async'] && in_array($route['type'], ['create', 'execute'], true)) {
            return $this->registerAsyncWrapper($container, $id);
        }

        return $id;
    }

    private function registerAsyncWrapper(ContainerBuilder $container, string $routeId): string
    {
        $id = uniqid('chimera.http.route.');

        $container->setDefinition(
            $id,
            $this->createService(Async::class, [new Reference($routeId)])
        );

        return $id;
    }

    public function fetchOnly(array $route, Reference $commandBus, Reference $queryBus): Definition
    {
        return $this->createService(FetchOnly::class, [$queryBus, $route['query']]);
    }

    public function createOnly(array $route, Reference $commandBus): Definition
    {
        return $this->createService(
            CreateOnly::class,
            [$commandBus, $route['command'], [Uuid::class, 'uuid4'], $route['redirect_to']]
        );
    }

    public function createAndFetch(array $route, Reference $commandBus, Reference $queryBus): Definition
    {
        return $this->createService(
            CreateAndFetch::class,
            [
                $commandBus,
                $queryBus,
                $route['command'],
                $route['query'],
                [Uuid::class, 'uuid4'],
                $route['redirect_to']
            ]
        );
    }

    public function executeOnly(array $route, Reference $commandBus): Definition
    {
        return $this->createService(ExecuteOnly::class, [$commandBus, $route['command']]);
    }

    public function executeAndFetch(array $route, Reference $commandBus, Reference $queryBus): Definition
    {
        return $this->createService(
            ExecuteAndFetch::class,
            [
                $commandBus,
                $queryBus,
                $route['command'],
                $route['query'],
            ]
        );
    }

    private function appendMiddlewares(
        Definition $application,
        Reference $dispatcher,
        array $middlewares
    ): void {
        foreach ($middlewares as $id) {
            $application->addMethodCall('pipe', [$id]);
        }

        $application->addMethodCall('pipeRoutingMiddleware');
        $application->addMethodCall('pipeDispatchMiddleware');

        $application->addMethodCall('pipe', [(string) $dispatcher]);
    }

    private function appendRoutes(
        Definition $application,
        array $routes
    ): void {
        foreach ($routes as $serviceId => $route) {
            $application->addMethodCall(
                'route',
                [
                    $route['path'],
                    $serviceId,
                    $route['methods'] ?? self::TYPES[$route['type']]['methods'],
                    $route['route_name'],
                ]
            );
        }
    }
}
