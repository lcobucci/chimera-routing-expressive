<?php
declare(strict_types=1);

namespace Lcobucci\Chimera\Routing\Expressive\Tests;

use Lcobucci\Chimera\Routing\Attributes;
use Lcobucci\Chimera\Routing\Expressive\ResponseGenerator;
use Lcobucci\Chimera\Routing\Expressive\ResultConverter;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\ServerRequest;
use Zend\Expressive\Router\RouterInterface;

final class ResponseGeneratorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var RouterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $router;

    /**
     * @var ResultConverter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $converter;

    /**
     * @before
     */
    public function createDependencies(): void
    {
        $this->router    = $this->createMock(RouterInterface::class);
        $this->converter = $this->createMock(ResultConverter::class);
    }

    /**
     * @test
     *
     * @covers \Lcobucci\Chimera\Routing\Expressive\ResponseGenerator
     */
    public function generateResponseShouldConvertTheResultAndReturnTheGeneratedResponse(): void
    {
        $generator = new ResponseGenerator($this->router, $this->converter);
        $request   = new ServerRequest();
        $response  = new EmptyResponse();

        $this->router->expects($this->never())
                     ->method('generateUri');

        $this->converter->expects($this->once())
                        ->method('convert')
                        ->with($request, [], 'testing')
                        ->willReturn($response);

        self::assertSame($response, $generator->generateResponse($request, 'testing'));
    }

    /**
     * @test
     *
     * @covers \Lcobucci\Chimera\Routing\Expressive\ResponseGenerator
     */
    public function generateResponseShouldPassHeadersToConverterWhenIdWasGenerated(): void
    {
        $generator = new ResponseGenerator($this->router, $this->converter);
        $response  = new EmptyResponse();
        $request   = (new ServerRequest())->withAttribute(Attributes::GENERATED_ID, 1)
                                          ->withAttribute(Attributes::RESOURCE_LOCATION, 'test');

        $this->router->expects($this->once())
                     ->method('generateUri')
                     ->with('test', ['id' => 1])
                     ->willReturn('/testing/1');

        $this->converter->expects($this->once())
                        ->method('convert')
                        ->with($request, ['Location' => '/testing/1'], 'testing')
                        ->willReturn($response);

        self::assertSame($response, $generator->generateResponse($request, 'testing'));
    }

    /**
     * @test
     *
     * @covers \Lcobucci\Chimera\Routing\Expressive\ResponseGenerator
     */
    public function generateResponseShouldReturnAnEmptyResponseWhenThereIsNoResult(): void
    {
        $generator = new ResponseGenerator($this->router, $this->converter);
        $request   = new ServerRequest();

        $this->router->expects($this->never())
                     ->method('generateUri');

        $this->converter->expects($this->never())
                        ->method('convert');

        $response = $generator->generateResponse($request);

        self::assertInstanceOf(EmptyResponse::class, $response);
        self::assertSame(204, $response->getStatusCode());
    }

    /**
     * @test
     *
     * @covers \Lcobucci\Chimera\Routing\Expressive\ResponseGenerator
     */
    public function generateResponseShouldReturnModifyTheResponseStatusCodeWhenRequestIsAsync(): void
    {
        $generator = new ResponseGenerator($this->router, $this->converter);
        $request   = (new ServerRequest())->withAttribute(Attributes::ASYNCHRONOUS, true);

        $this->router->expects($this->never())
                     ->method('generateUri');

        $this->converter->expects($this->never())
                        ->method('convert');

        $response = $generator->generateResponse($request);

        self::assertInstanceOf(EmptyResponse::class, $response);
        self::assertSame(202, $response->getStatusCode());
    }

    /**
     * @test
     *
     * @covers \Lcobucci\Chimera\Routing\Expressive\ResponseGenerator
     */
    public function generateResponseShouldAppendHeadersOnEmptyResponseWhenIdWasGenerated(): void
    {
        $generator = new ResponseGenerator($this->router, $this->converter);
        $request   = (new ServerRequest())->withAttribute(Attributes::GENERATED_ID, 1)
                                          ->withAttribute(Attributes::RESOURCE_LOCATION, 'test');

        $this->router->expects($this->once())
                     ->method('generateUri')
                     ->with('test', ['id' => 1])
                     ->willReturn('/testing/1');

        $this->converter->expects($this->never())
                        ->method('convert');

        $response = $generator->generateResponse($request);

        self::assertInstanceOf(EmptyResponse::class, $response);
        self::assertSame(201, $response->getStatusCode());
        self::assertSame('/testing/1', $response->getHeaderLine('Location'));
    }

    /**
     * @test
     *
     * @covers \Lcobucci\Chimera\Routing\Expressive\ResponseGenerator
     */
    public function generateResponseShouldReturnModifyTheResponseStatusCodeWhenRequestIsAsyncAndIdWasGenerated(): void
    {
        $generator = new ResponseGenerator($this->router, $this->converter);
        $request   = (new ServerRequest())->withAttribute(Attributes::ASYNCHRONOUS, true)
                                          ->withAttribute(Attributes::GENERATED_ID, 1)
                                          ->withAttribute(Attributes::RESOURCE_LOCATION, 'test');

        $this->router->expects($this->once())
                     ->method('generateUri')
                     ->with('test', ['id' => 1])
                     ->willReturn('/testing/1');

        $this->converter->expects($this->never())
                        ->method('convert');

        $response = $generator->generateResponse($request);

        self::assertInstanceOf(EmptyResponse::class, $response);
        self::assertSame(202, $response->getStatusCode());
        self::assertSame('/testing/1', $response->getHeaderLine('Location'));
    }
}
