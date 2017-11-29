<?php
declare(strict_types=1);

namespace Lcobucci\Chimera\Routing\Expressive\Tests;

use Lcobucci\Chimera\Routing\Expressive\JsonConverter;
use Zend\Diactoros\ServerRequest;

final class JsonConverterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     *
     * @covers \Lcobucci\Chimera\Routing\Expressive\JsonConverter
     */
    public function convertShouldReturnAJsonResponseWithTheProvidedResult(): void
    {
        $converter = new JsonConverter();
        $response  = $converter->convert(new ServerRequest(), [], 'testing');

        self::assertSame('"testing"', (string) $response->getBody());
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json', $response->getHeaderLine('Content-Type'));
    }

    /**
     * @test
     *
     * @covers \Lcobucci\Chimera\Routing\Expressive\JsonConverter
     */
    public function convertShouldAppendHeadersToTheResponse(): void
    {
        $converter = new JsonConverter();
        $response  = $converter->convert(new ServerRequest(), ['Content-Language' => 'en_UK'], 'testing');

        self::assertSame('en_UK', $response->getHeaderLine('Content-Language'));
    }
}
