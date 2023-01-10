<?php

declare(strict_types=1);

namespace Mezzio\ProblemDetails;

use Psr\Container\ContainerInterface;

use function assert;

class ProblemDetailsNotFoundHandlerFactory
{
    public function __invoke(ContainerInterface $container): ProblemDetailsNotFoundHandler
    {
        $responseFactory = $container->get(ProblemDetailsResponseFactory::class);
        assert($responseFactory instanceof ProblemDetailsResponseFactory);

        return new ProblemDetailsNotFoundHandler($responseFactory);
    }
}
