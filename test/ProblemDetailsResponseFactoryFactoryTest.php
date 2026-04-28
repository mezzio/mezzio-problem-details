<?php

declare(strict_types=1);

namespace MezzioTest\ProblemDetails;

use Mezzio\ProblemDetails\ProblemDetailsResponseFactory;
use Mezzio\ProblemDetails\ProblemDetailsResponseFactoryFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use ReflectionObject;
use ReflectionProperty;
use RuntimeException;
use stdClass;
use TypeError;

use const JSON_PARTIAL_OUTPUT_ON_ERROR;
use const JSON_PRESERVE_ZERO_FRACTION;
use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

final class ProblemDetailsResponseFactoryFactoryTest extends TestCase
{
    private InMemoryContainer $container;

    protected function setUp(): void
    {
        $this->container = new InMemoryContainer();
    }

    public function assertResponseFactoryReturns(
        ResponseInterface $expected,
        ProblemDetailsResponseFactory $factory
    ): void {
        $r               = new ReflectionProperty($factory, 'responseFactory');
        $responseFactory = $r->getValue($factory);
        $this->assertInstanceOf(ResponseFactoryInterface::class, $responseFactory);
        $this->assertSame($expected, $responseFactory->createResponse());
    }

    public function testLackOfResponseServiceResultsInException(): void
    {
        $factory = new ProblemDetailsResponseFactoryFactory();
        $this->expectException(RuntimeException::class);
        $factory($this->container);
    }

    public function testNonCallableResponseServiceResultsInException(): void
    {
        $factory = new ProblemDetailsResponseFactoryFactory();
        $this->container->set(ResponseInterface::class, new stdClass());
        $this->expectException(TypeError::class);
        $factory($this->container);
    }

    public function testLackOfConfigServiceResultsInFactoryUsingDefaults(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('withStatus')->willReturnSelf();
        $this->container->set(ResponseInterface::class, static fn(): MockObject&ResponseInterface => $response);

        $factoryFactory = new ProblemDetailsResponseFactoryFactory();
        $factory        = $factoryFactory($this->container);
        $isDebug        = (new ReflectionObject($factory))->getProperty('isDebug');
        $jsonFlags      = (new ReflectionObject($factory))->getProperty('jsonFlags');

        $this->assertSame(ProblemDetailsResponseFactory::EXCLUDE_THROWABLE_DETAILS, $isDebug->getValue($factory));
        $this->assertSame(JSON_UNESCAPED_SLASHES
        | JSON_UNESCAPED_UNICODE
        | JSON_PRESERVE_ZERO_FRACTION
        | JSON_PARTIAL_OUTPUT_ON_ERROR, $jsonFlags->getValue($factory));

        $this->assertResponseFactoryReturns($response, $factory);
    }

    public function testUsesPrettyPrintFlagOnEnabledDebugMode(): void
    {
        $this->container->set('config', ['debug' => true]);
        $this->container->set(ResponseInterface::class, static fn(): null => null);

        $factoryFactory = new ProblemDetailsResponseFactoryFactory();
        $factory        = $factoryFactory($this->container);
        $jsonFlags      = (new ReflectionObject($factory))->getProperty('jsonFlags');
        $value          = $jsonFlags->getValue($factory);
        $this->assertIsInt($value);
        $this->assertSame(JSON_PRETTY_PRINT, $value & JSON_PRETTY_PRINT);
    }

    public function testUsesDebugSettingFromConfigWhenPresent(): void
    {
        $this->container->set('config', ['debug' => true]);
        $this->container->set(ResponseInterface::class, static fn(): null => null);

        $factoryFactory             = new ProblemDetailsResponseFactoryFactory();
        $factory                    = $factoryFactory($this->container);
        $isDebug                    = (new ReflectionObject($factory))->getProperty('isDebug');
        $exceptionDetailsInResponse = (new ReflectionObject($factory))->getProperty('exceptionDetailsInResponse');

        $this->assertSame(ProblemDetailsResponseFactory::INCLUDE_THROWABLE_DETAILS, $isDebug->getValue($factory));
        $this->assertTrue($exceptionDetailsInResponse->getValue($factory));
    }

    public function testUsesJsonFlagsSettingFromConfigWhenPresent(): void
    {
        $this->container->set('config', ['problem-details' => ['json_flags' => JSON_PRETTY_PRINT]]);
        $this->container->set(ResponseInterface::class, static fn(): null => null);

        $factoryFactory = new ProblemDetailsResponseFactoryFactory();
        $factory        = $factoryFactory($this->container);
        $jsonFlags      = (new ReflectionObject($factory))->getProperty('jsonFlags');

        $this->assertSame(JSON_PRETTY_PRINT, $jsonFlags->getValue($factory));
    }

    public function testUsesDefaultTypesSettingFromConfigWhenPresent(): void
    {
        $expectedDefaultTypes = [
            404 => 'https://example.com/problem-details/error/not-found',
        ];

        $this->container->set('config', ['problem-details' => ['default_types_map' => $expectedDefaultTypes]]);
        $this->container->set(ResponseInterface::class, static fn(): null => null);

        $factoryFactory  = new ProblemDetailsResponseFactoryFactory();
        $factory         = $factoryFactory($this->container);
        $defaultTypesMap = (new ReflectionObject($factory))->getProperty('defaultTypesMap');

        $this->assertSame($expectedDefaultTypes, $defaultTypesMap->getValue($factory));
    }

    public function testUsesIncludeThrowableDetailsSettingFromConfigWhenPresent(): void
    {
        $this->container->set(
            'config',
            [
                'problem-details' => [
                    'include-throwable-details' => ProblemDetailsResponseFactory::INCLUDE_THROWABLE_DETAILS,
                ],
            ]
        );
        $this->container->set(ResponseInterface::class, static fn(): null => null);

        $factoryFactory             = new ProblemDetailsResponseFactoryFactory();
        $factory                    = $factoryFactory($this->container);
        $isDebug                    = (new ReflectionObject($factory))->getProperty('isDebug');
        $exceptionDetailsInResponse = (new ReflectionObject($factory))->getProperty('exceptionDetailsInResponse');

        $this->assertSame(ProblemDetailsResponseFactory::EXCLUDE_THROWABLE_DETAILS, $isDebug->getValue($factory));
        $this->assertTrue($exceptionDetailsInResponse->getValue($factory));
    }
}
