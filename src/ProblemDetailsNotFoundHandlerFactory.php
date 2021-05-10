<?php

declare(strict_types=1);

namespace Mezzio\ProblemDetails;

use Psr\Container\ContainerInterface;

class ProblemDetailsNotFoundHandlerFactory
{
    public function __invoke(ContainerInterface $container): ProblemDetailsNotFoundHandler
    {
        return new ProblemDetailsNotFoundHandler($container->get(ProblemDetailsResponseFactory::class));
    }
}
