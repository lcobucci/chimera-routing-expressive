<?php
declare(strict_types=1);

namespace Lcobucci\Chimera\Routing\Expressive\DependencyInjection;

interface Tags
{
    public const MIDDLEWARE = 'chimera.http_middleware';
    public const ROUTE      = 'chimera.http_route';
}
