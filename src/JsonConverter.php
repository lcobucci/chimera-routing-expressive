<?php
declare(strict_types=1);

namespace Lcobucci\Chimera\Routing\Expressive;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

final class JsonConverter implements ResultConverter
{
    public function convert(ServerRequestInterface $request, array $headers, $result): ResponseInterface
    {
        return new JsonResponse($result, 200, $headers);
    }
}
