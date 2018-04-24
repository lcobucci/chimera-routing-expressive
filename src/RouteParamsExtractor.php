<?php
declare(strict_types=1);

namespace Lcobucci\Chimera\Routing\Expressive;

use Lcobucci\Chimera\Routing\RouteParamsExtractor as RouteParamsExtractorInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Expressive\Router\RouteResult;
use function assert;

final class RouteParamsExtractor implements RouteParamsExtractorInterface
{
    /**
     * {@inheritdoc}
     */
    public function getParams(ServerRequestInterface $request): array
    {
        $routeResult = $request->getAttribute(RouteResult::class);
        assert($routeResult === null || $routeResult instanceof RouteResult);

        if ($routeResult === null) {
            return [];
        }

        return $routeResult->getMatchedParams();
    }
}
