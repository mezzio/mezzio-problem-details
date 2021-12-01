<?php

declare(strict_types=1);

namespace Mezzio\ProblemDetails;

use Psr\Container\ContainerInterface;
use Webmozart\Assert\Assert;

class ProblemDetailsResponseFactoryFactory
{
    use Psr17ResponseFactoryTrait;

    public function __invoke(ContainerInterface $container): ProblemDetailsResponseFactory
    {
        $config = $container->has('config') ? $container->get('config') : [];
        Assert::isArrayAccessible($config);
        $includeThrowableDetail = $config['debug'] ?? ProblemDetailsResponseFactory::EXCLUDE_THROWABLE_DETAILS;

        $problemDetailsConfig = $config['problem-details'] ?? [];
        Assert::isArrayAccessible($problemDetailsConfig);
        $jsonFlags       = $problemDetailsConfig['json_flags'] ?? null;
        $defaultTypesMap = $problemDetailsConfig['default_types_map'] ?? [];

        return new ProblemDetailsResponseFactory(
            $this->detectResponseFactory($container),
            $includeThrowableDetail,
            $jsonFlags,
            $includeThrowableDetail,
            ProblemDetailsResponseFactory::DEFAULT_DETAIL_MESSAGE,
            $defaultTypesMap
        );
    }
}
