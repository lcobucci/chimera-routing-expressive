<?php
declare(strict_types=1);

namespace Lcobucci\Chimera\Routing\Expressive;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface ResultConverter
{
    public function convert(ServerRequestInterface $request, array $headers, $result): ResponseInterface;
}
