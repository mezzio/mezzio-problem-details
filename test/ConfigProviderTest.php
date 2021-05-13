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

        $this->assertArrayHasKey('dependencies', $config);

        $dependencies = $config['dependencies'];
        $this->assertArrayHasKey('factories', $dependencies);

        $factories = $dependencies['factories'];
        $this->assertCount(3, $factories);
        $this->assertArrayHasKey(ProblemDetailsMiddleware::class, $factories);
        $this->assertArrayHasKey(ProblemDetailsResponseFactory::class, $factories);

        $this->assertSame(
            ProblemDetailsMiddlewareFactory::class,
            $factories[ProblemDetailsMiddleware::class]
        );
        $this->assertSame(
            ProblemDetailsResponseFactoryFactory::class,
            $factories[ProblemDetailsResponseFactory::class]
        );
    }
}
