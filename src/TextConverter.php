<?php
declare(strict_types=1);

namespace Lcobucci\Chimera\Routing\Expressive;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;

final class TextConverter implements ResultConverter
{
    public function convert(ServerRequestInterface $request, array $headers, $result): ResponseInterface
    {
        $response = new Response('php://memory', 200, $headers);
        $response = $response->withHeader('Content-Type', 'text/plain');
        $response->getBody()->write((string) $result);

        return $response;
    }
}
