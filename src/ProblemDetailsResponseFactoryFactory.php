<?php

/**
 * @see       https://github.com/mezzio/mezzio-problem-details for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-problem-details/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-problem-details/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\ProblemDetails;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;

class ProblemDetailsResponseFactoryFactory
{
    public function __invoke(ContainerInterface $container) : ProblemDetailsResponseFactory
    {
        $config = $container->has('config') ? $container->get('config') : [];
        $includeThrowableDetail = $config['debug'] ?? ProblemDetailsResponseFactory::EXCLUDE_THROWABLE_DETAILS;

        $problemDetailsConfig = $config['problem-details'] ?? [];
        $jsonFlags = $problemDetailsConfig['json_flags'] ?? null;

        $responsePrototype = $container->has(ResponseInterface::class)
            ? $container->get(ResponseInterface::class)
            : null;

        $streamFactory = $container->has('Mezzio\ProblemDetails\StreamFactory')
            ? $container->get('Mezzio\ProblemDetails\StreamFactory')
            : null;

        return new ProblemDetailsResponseFactory(
            $includeThrowableDetail,
            $jsonFlags,
            $responsePrototype,
            $streamFactory
        );
    }
}
