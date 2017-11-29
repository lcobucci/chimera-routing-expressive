<?php
declare(strict_types=1);

namespace Lcobucci\Chimera\Routing\Expressive\Tests;

use Lcobucci\Chimera\Routing\Expressive\TextConverter;
use Zend\Diactoros\ServerRequest;

final class TextConverterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     *
     * @covers \Lcobucci\Chimera\Routing\Expressive\TextConverter
     */
    public function convertShouldReturnATextResponseWithTheProvidedResult(): void
    {
        $converter = new TextConverter();
        $response  = $converter->convert(new ServerRequest(), [], 'testing');

        self::assertSame('testing', (string) $response->getBody());
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('text/plain', $response->getHeaderLine('Content-Type'));
    }

    /**
     * @test
     *
     * @covers \Lcobucci\Chimera\Routing\Expressive\TextConverter
     */
    public function convertShouldAppendHeadersToTheResponse(): void
    {
        $converter = new TextConverter();
        $response  = $converter->convert(new ServerRequest(), ['Content-Language' => 'en_UK'], 'testing');

        self::assertSame('en_UK', $response->getHeaderLine('Content-Language'));
    }
}
