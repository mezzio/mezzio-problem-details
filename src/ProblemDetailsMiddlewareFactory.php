<?php

declare(strict_types=1);

namespace Mezzio\ProblemDetails;

use Psr\Container\ContainerInterface;

use function assert;

class ProblemDetailsMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): ProblemDetailsMiddleware
    {
        $responseFactory = $container->get(ProblemDetailsResponseFactory::class);
        assert($responseFactory instanceof ProblemDetailsResponseFactory);

        return new ProblemDetailsMiddleware($responseFactory);
    }
}
