<?php

declare(strict_types=1);

namespace Mezzio\ProblemDetails;

use Zend\ProblemDetails\ProblemDetailsMiddleware as LegacyProblemDetailsMiddleware;
use Zend\ProblemDetails\ProblemDetailsNotFoundHandler as LegacyProblemDetailsNotFoundHandler;
use Zend\ProblemDetails\ProblemDetailsResponseFactory as LegacyProblemDetailsResponseFactory;

/**
 * Configuration provider for the package.
 *
 * @see https://docs.laminas.dev/laminas-component-installer/
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
     *
     * @psalm-suppress UndefinedClass
     */
    public function getDependencies(): array
    {
        return [
            // Legacy Zend Framework aliases
            'aliases'   => [
                LegacyProblemDetailsMiddleware::class      => ProblemDetailsMiddleware::class,
                LegacyProblemDetailsNotFoundHandler::class => ProblemDetailsNotFoundHandler::class,
                LegacyProblemDetailsResponseFactory::class => ProblemDetailsResponseFactory::class,
            ],
            'factories' => [
                ProblemDetailsMiddleware::class      => ProblemDetailsMiddlewareFactory::class,
                ProblemDetailsNotFoundHandler::class => ProblemDetailsNotFoundHandlerFactory::class,
                ProblemDetailsResponseFactory::class => ProblemDetailsResponseFactoryFactory::class,
            ],
        ];
    }
}
