<?php

declare(strict_types=1);

namespace MezzioTest\ProblemDetails;

use Mezzio\ProblemDetails\ConfigProvider;
use Mezzio\ProblemDetails\ProblemDetailsMiddleware;
use Mezzio\ProblemDetails\ProblemDetailsMiddlewareFactory;
use Mezzio\ProblemDetails\ProblemDetailsResponseFactory;
use Mezzio\ProblemDetails\ProblemDetailsResponseFactoryFactory;
use PHPUnit\Framework\TestCase;

class ConfigProviderTest extends TestCase
{
    public function testReturnsExpectedDependencies(): void
    {
        $provider = new ConfigProvider();
        $config   = $provider();

        self::assertArrayHasKey('dependencies', $config);
        $dependencies = $config['dependencies'];
        self::assertIsArray($dependencies);
        self::assertArrayHasKey('factories', $dependencies);

        $factories = $dependencies['factories'];
        self::assertIsArray($factories);
        self::assertCount(3, $factories);
        self::assertArrayHasKey(ProblemDetailsMiddleware::class, $factories);
        self::assertArrayHasKey(ProblemDetailsResponseFactory::class, $factories);

        self::assertSame(
            ProblemDetailsMiddlewareFactory::class,
            $factories[ProblemDetailsMiddleware::class]
        );
        self::assertSame(
            ProblemDetailsResponseFactoryFactory::class,
            $factories[ProblemDetailsResponseFactory::class]
        );
    }
}
