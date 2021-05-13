<?php

declare(strict_types=1);

namespace Mezzio\ProblemDetails;

use Psr\Container\ContainerInterface;

class ProblemDetailsMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): ProblemDetailsMiddleware
    {
        return new ProblemDetailsMiddleware($container->get(ProblemDetailsResponseFactory::class));
    }
}
