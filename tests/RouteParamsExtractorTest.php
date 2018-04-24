<?php
declare(strict_types=1);

namespace Lcobucci\Chimera\Routing\Expressive\Tests;

use Lcobucci\Chimera\Routing\Expressive\RouteParamsExtractor;
use PHPUnit\Framework\TestCase;
use Zend\Diactoros\ServerRequest;
use Zend\Expressive\Router\Route;
use Zend\Expressive\Router\RouteResult;

/**
 * @coversDefaultClass \Lcobucci\Chimera\Routing\Expressive\RouteParamsExtractor
 */
final class RouteParamsExtractorTest extends TestCase
{
    /**
     * @test
     *
     * @covers ::getParams()
     */
    public function getParamsShouldReturnAnEmptyArrayWhenAttributeWasNotConfigured(): void
    {
        $extractor = new RouteParamsExtractor();
        $request   = new ServerRequest();

        self::assertSame([], $extractor->getParams($request));
    }

    /**
     * @test
     *
     * @covers ::getParams()
     */
    public function getParamsShouldRetrieveRouteParamsFromTheAttributeConfiguredByTheRoutingMiddleware(): void
    {
        $routeResult = RouteResult::fromRoute(
            $this->createMock(Route::class),
            ['test' => '1']
        );

        $extractor = new RouteParamsExtractor();
        $request   = (new ServerRequest())->withAttribute(RouteResult::class, $routeResult);

        self::assertSame(['test' => '1'], $extractor->getParams($request));
    }
}
