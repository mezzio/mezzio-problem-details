<?php

declare(strict_types=1);

namespace Mezzio\ProblemDetails;

/**
 * Configuration provider for the package.
 *
 * @see https://docs.laminas.dev/laminas-component-installer/
 *
 * @final
 */
class ConfigProvider
{
    /**
     * Returns the configuration array.
     */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    /**
     * Returns the container dependencies.
     */
    public function getDependencies(): array
    {
        return [
            'factories' => [
                ProblemDetailsMiddleware::class      => ProblemDetailsMiddlewareFactory::class,
                ProblemDetailsNotFoundHandler::class => ProblemDetailsNotFoundHandlerFactory::class,
                ProblemDetailsResponseFactory::class => ProblemDetailsResponseFactoryFactory::class,
            ],
        ];
    }
}
