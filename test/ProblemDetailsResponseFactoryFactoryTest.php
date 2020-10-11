<?php

/**
 * @see       https://github.com/mezzio/mezzio-problem-details for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-problem-details/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-problem-details/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\ProblemDetails;

use Closure;
use Mezzio\ProblemDetails\ProblemDetailsResponseFactory;
use Mezzio\ProblemDetails\ProblemDetailsResponseFactoryFactory;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use ReflectionProperty;
use RuntimeException;
use stdClass;
use TypeError;

use const JSON_PRESERVE_ZERO_FRACTION;
use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

class ProblemDetailsResponseFactoryFactoryTest extends TestCase
{
    protected function setUp() : void
    {
        $this->container = $this->createMock(ContainerInterface::class);
    }

    public function assertResponseFactoryReturns(ResponseInterface $expected, ProblemDetailsResponseFactory $factory)
    {
        $r = new ReflectionProperty($factory, 'responseFactory');
        $r->setAccessible(true);
        $responseFactory = $r->getValue($factory);

        Assert::assertSame($expected, $responseFactory());
    }

    public function testLackOfResponseServiceResultsInException()
    {
        $factory = new ProblemDetailsResponseFactoryFactory();
        $e = new RuntimeException();

        $this->container->method('has')->with('config')->willReturn(false);
        $this->container->method('get')->with(ResponseInterface::class)->willThrowException($e);

        $this->expectException(RuntimeException::class);
        $factory($this->container);
    }

    public function testNonCallableResponseServiceResultsInException()
    {
        $factory = new ProblemDetailsResponseFactoryFactory();

        $this->container->method('has')->with('config')->willReturn(false);
        $this->container->method('get')->with(ResponseInterface::class)->willReturn(new stdClass);

        $this->expectException(TypeError::class);
        $factory($this->container);
    }

    public function testLackOfConfigServiceResultsInFactoryUsingDefaults() : void
    {
        $this->container->method('has')->with('config')->willReturn(false);

        $response = $this->createMock(ResponseInterface::class);
        $this->container
            ->method('get')
            ->with(ResponseInterface::class)
            ->willReturn(function () use ($response) {
                return $response;
            });

        $factoryFactory = new ProblemDetailsResponseFactoryFactory();
        $factory = $factoryFactory($this->container);

        $isDebug = (new \ReflectionObject($factory))->getProperty('isDebug');
        $isDebug->setAccessible(true);

        $jsonFlags = (new \ReflectionObject($factory))->getProperty('jsonFlags');
        $jsonFlags->setAccessible(true);

        $responseFactory = (new \ReflectionObject($factory))->getProperty('responseFactory');
        $responseFactory->setAccessible(true);

        $this->assertInstanceOf(ProblemDetailsResponseFactory::class, $factory);
        $this->assertSame(ProblemDetailsResponseFactory::EXCLUDE_THROWABLE_DETAILS, $isDebug->getValue($factory));
        $this->assertSame(
            JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
            | JSON_PRESERVE_ZERO_FRACTION
            | JSON_PARTIAL_OUTPUT_ON_ERROR,
            $jsonFlags->getValue($factory)
        );

        $this->assertInstanceOf(Closure::class, $responseFactory->getValue($factory));
        $this->assertResponseFactoryReturns($response, $factory);
    }

    public function testUsesPrettyPrintFlagOnEnabledDebugMode() : void
    {
        $this->container->method('has')->with('config')->willReturn(true);

        $this->container
            ->method('get')
            ->willReturnMap([
                ['config', ['debug' => true]],
                [ResponseInterface::class, function () {
                }]
            ]);

        $factoryFactory = new ProblemDetailsResponseFactoryFactory();
        $factory = $factoryFactory($this->container);

        $jsonFlags = (new \ReflectionObject($factory))->getProperty('jsonFlags');
        $jsonFlags->setAccessible(true);

        $this->assertSame(JSON_PRETTY_PRINT, $jsonFlags->getValue($factory) & JSON_PRETTY_PRINT);
    }

    public function testUsesDebugSettingFromConfigWhenPresent() : void
    {
        $this->container->method('has')->with('config')->willReturn(true);

        $this->container
            ->method('get')
            ->willReturnMap([
                ['config', ['debug' => true]],
                [ResponseInterface::class, function () {
                }]
            ]);

        $factoryFactory = new ProblemDetailsResponseFactoryFactory();
        $factory = $factoryFactory($this->container);

        $isDebug = (new \ReflectionObject($factory))->getProperty('isDebug');
        $isDebug->setAccessible(true);

        $exceptionDetailsInResponse = (new \ReflectionObject($factory))->getProperty('exceptionDetailsInResponse');
        $exceptionDetailsInResponse->setAccessible(true);

        $this->assertInstanceOf(ProblemDetailsResponseFactory::class, $factory);
        $this->assertSame(ProblemDetailsResponseFactory::INCLUDE_THROWABLE_DETAILS, $isDebug->getValue($factory));
        $this->assertSame(true, $exceptionDetailsInResponse->getValue($factory));
    }

    public function testUsesJsonFlagsSettingFromConfigWhenPresent() : void
    {
        $this->container->method('has')->with('config')->willReturn(true);

        $this->container
            ->method('get')
            ->willReturnMap([
                ['config', ['problem-details' => ['json_flags' => JSON_PRETTY_PRINT]]],
                [ResponseInterface::class, function () {
                }]
            ]);

        $factoryFactory = new ProblemDetailsResponseFactoryFactory();
        $factory = $factoryFactory($this->container);

        $jsonFlags = (new \ReflectionObject($factory))->getProperty('jsonFlags');
        $jsonFlags->setAccessible(true);

        $this->assertInstanceOf(ProblemDetailsResponseFactory::class, $factory);
        $this->assertSame(JSON_PRETTY_PRINT,  $jsonFlags->getValue($factory));
    }

    public function testUsesDefaultTypesSettingFromConfigWhenPresent() : void
    {
        $expectedDefaultTypes = [
            404 => 'https://example.com/problem-details/error/not-found',
        ];

        $this->container->method('has')->with('config')->willReturn(true);

        $this->container
            ->method('get')
            ->willReturnMap([
                ['config', ['problem-details' => ['default_types_map' => $expectedDefaultTypes]]],
                [ResponseInterface::class, function () {
                }]
            ]);

        $factoryFactory = new ProblemDetailsResponseFactoryFactory();
        $factory = $factoryFactory($this->container);

        $defaultTypesMap = (new \ReflectionObject($factory))->getProperty('defaultTypesMap');
        $defaultTypesMap->setAccessible(true);

        $this->assertInstanceOf(ProblemDetailsResponseFactory::class, $factory);
        $this->assertSame($expectedDefaultTypes, $defaultTypesMap->getValue($factory));
    }
}
