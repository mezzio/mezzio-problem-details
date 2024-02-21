<?php

declare(strict_types=1);

namespace Mezzio\ProblemDetails;

use Psr\Container\ContainerInterface;
use Webmozart\Assert\Assert;

use function assert;
use function is_bool;
use function is_int;
use function is_string;

class ProblemDetailsResponseFactoryFactory
{
    use Psr17ResponseFactoryTrait;

    public function __invoke(ContainerInterface $container): ProblemDetailsResponseFactory
    {
        $config = $container->has('config') ? $container->get('config') : [];
        Assert::isArrayAccessible($config);
        $debug   = isset($config['debug']) && is_bool($config['debug']) ? $config['debug'] : null;
        $debug ??= ProblemDetailsResponseFactory::EXCLUDE_THROWABLE_DETAILS;

        $problemDetailsConfig = $config['problem-details'] ?? [];
        Assert::isArrayAccessible($problemDetailsConfig);

        $includeThrowableDetail   = isset($problemDetailsConfig['include-throwable-details'])
            && is_bool($problemDetailsConfig['include-throwable-details'])
            ? $problemDetailsConfig['include-throwable-details'] : null;
        $includeThrowableDetail ??= $debug;

        $jsonFlags = $problemDetailsConfig['json_flags'] ?? null;
        assert($jsonFlags === null || is_int($jsonFlags));
        $defaultTypesMap = $problemDetailsConfig['default_types_map'] ?? [];
        Assert::isArray($defaultTypesMap);

        foreach ($defaultTypesMap as $key => $value) {
            assert(is_int($key));
            assert(is_string($value));
        }

        /** @psalm-var array<int, string> $defaultTypesMap */

        return new ProblemDetailsResponseFactory(
            $this->detectResponseFactory($container),
            $debug,
            $jsonFlags,
            $includeThrowableDetail,
            ProblemDetailsResponseFactory::DEFAULT_DETAIL_MESSAGE,
            $defaultTypesMap
        );
    }
}
