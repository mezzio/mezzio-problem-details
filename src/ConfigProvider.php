<?php

/**
 * @see       https://github.com/mezzio/mezzio-problem-details for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-problem-details/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-problem-details/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\ProblemDetails;

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
    public function __invoke() : array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    /**
     * Returns the container dependencies.
     */
    public function getDependencies() : array
    {
        return [
            // Legacy Zend Framework aliases
            'aliases' => [
                \Zend\ProblemDetails\ProblemDetailsMiddleware::class => ProblemDetailsMiddleware::class,
                \Zend\ProblemDetails\ProblemDetailsResponseFactory::class => ProblemDetailsResponseFactory::class,
            ],
            'factories'  => [
                ProblemDetailsMiddleware::class => ProblemDetailsMiddlewareFactory::class,
                ProblemDetailsResponseFactory::class => ProblemDetailsResponseFactoryFactory::class,
            ],
        ];
    }
}
