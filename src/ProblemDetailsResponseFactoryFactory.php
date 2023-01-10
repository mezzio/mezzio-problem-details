<?php

declare(strict_types=1);

namespace Mezzio\ProblemDetails;

use Psr\Container\ContainerInterface;
use Webmozart\Assert\Assert;

use function array_keys;
use function is_bool;

class ProblemDetailsResponseFactoryFactory
{
    use Psr17ResponseFactoryTrait;

    public function __invoke(ContainerInterface $container): ProblemDetailsResponseFactory
    {
        $config = $container->has('config') ? $container->get('config') : [];
        Assert::isArrayAccessible($config);
        $debug                  = isset($config['debug']) && is_bool($config['debug']) ? $config['debug'] : null;
        $includeThrowableDetail = $debug ?? ProblemDetailsResponseFactory::EXCLUDE_THROWABLE_DETAILS;

        $problemDetailsConfig = $config['problem-details'] ?? [];
        Assert::isArrayAccessible($problemDetailsConfig);
        $jsonFlags = $problemDetailsConfig['json_flags'] ?? null;
        Assert::nullOrInteger($jsonFlags);
        $defaultTypesMap = $problemDetailsConfig['default_types_map'] ?? [];
        Assert::isArray($defaultTypesMap);
        Assert::allInteger(array_keys($defaultTypesMap));
        Assert::allString($defaultTypesMap);
        /** @psalm-var array<int, string> $defaultTypesMap */

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
