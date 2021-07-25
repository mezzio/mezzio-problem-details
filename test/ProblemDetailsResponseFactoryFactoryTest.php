<?php

declare(strict_types=1);

namespace MezzioTest\ProblemDetails;

use Mezzio\ProblemDetails\ProblemDetailsResponseFactory;
use Mezzio\ProblemDetails\ProblemDetailsResponseFactoryFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
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

class ProblemDetailsResponseFactoryFactoryTest extends TestCase
{
    /** @var ContainerInterface&MockObject */
    private $container;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
    }

    public function assertResponseFactoryReturns(ResponseInterface $expected, ProblemDetailsResponseFactory $factory)
    {
        $r = new ReflectionProperty($factory, 'responseFactory');
        $r->setAccessible(true);
        $responseFactory = $r->getValue($factory);
        self::assertInstanceOf(ResponseFactoryInterface::class, $responseFactory);
        self::assertSame($expected, $responseFactory->createResponse());
    }

    public function testLackOfResponseServiceResultsInException()
    {
        $factory = new ProblemDetailsResponseFactoryFactory();
        $e       = new RuntimeException();

        $this->container
            ->method('has')
            ->withConsecutive(['config'], [ResponseFactoryInterface::class])
            ->willReturn(false);

        $this->container
            ->method('get')
            ->with(ResponseInterface::class)
            ->willThrowException($e);

        $this->expectException(RuntimeException::class);
        $factory($this->container);
    }

    public function testNonCallableResponseServiceResultsInException()
    {
        $factory = new ProblemDetailsResponseFactoryFactory();

        $this->container
            ->method('has')
            ->withConsecutive(['config'], [ResponseFactoryInterface::class])
            ->willReturn(false);

        $this->container
            ->method('get')
            ->with(ResponseInterface::class)
            ->willReturn(new stdClass());

        $this->expectException(TypeError::class);
        $factory($this->container);
    }

    public function testLackOfConfigServiceResultsInFactoryUsingDefaults(): void
    {
        $this->container
            ->method('has')
            ->withConsecutive(['config'], [ResponseFactoryInterface::class])
            ->willReturn(false);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('withStatus')->willReturnSelf();
        $this->container
            ->method('get')
            ->with(ResponseInterface::class)
            ->willReturn(function () use ($response): ResponseInterface {
                return $response;
            });

        $factoryFactory = new ProblemDetailsResponseFactoryFactory();
        $factory        = $factoryFactory($this->container);

        $isDebug = (new ReflectionObject($factory))->getProperty('isDebug');
        $isDebug->setAccessible(true);

        $jsonFlags = (new ReflectionObject($factory))->getProperty('jsonFlags');
        $jsonFlags->setAccessible(true);

        self::assertSame(ProblemDetailsResponseFactory::EXCLUDE_THROWABLE_DETAILS, $isDebug->getValue($factory));
        self::assertSame(
            JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
            | JSON_PRESERVE_ZERO_FRACTION
            | JSON_PARTIAL_OUTPUT_ON_ERROR,
            $jsonFlags->getValue($factory)
        );

        $this->assertResponseFactoryReturns($response, $factory);
    }

    public function testUsesPrettyPrintFlagOnEnabledDebugMode(): void
    {
        $this->container
            ->method('has')
            ->withConsecutive(['config'], [ResponseFactoryInterface::class])
            ->willReturnOnConsecutiveCalls(true, false);

        $this->container
            ->method('get')
            ->willReturnMap([
                ['config', ['debug' => true]],
                [
                    ResponseInterface::class,
                    function () {
                    },
                ],
            ]);

        $factoryFactory = new ProblemDetailsResponseFactoryFactory();
        $factory        = $factoryFactory($this->container);

        $jsonFlags = (new ReflectionObject($factory))->getProperty('jsonFlags');
        $jsonFlags->setAccessible(true);

        self::assertSame(JSON_PRETTY_PRINT, $jsonFlags->getValue($factory) & JSON_PRETTY_PRINT);
    }

    public function testUsesDebugSettingFromConfigWhenPresent(): void
    {
        $this->container
            ->method('has')
            ->withConsecutive(['config'], [ResponseFactoryInterface::class])
            ->willReturnOnConsecutiveCalls(true, false);

        $this->container
            ->method('get')
            ->willReturnMap([
                ['config', ['debug' => true]],
                [
                    ResponseInterface::class,
                    function () {
                    },
                ],
            ]);

        $factoryFactory = new ProblemDetailsResponseFactoryFactory();
        $factory        = $factoryFactory($this->container);

        $isDebug = (new ReflectionObject($factory))->getProperty('isDebug');
        $isDebug->setAccessible(true);

        $exceptionDetailsInResponse = (new ReflectionObject($factory))->getProperty('exceptionDetailsInResponse');
        $exceptionDetailsInResponse->setAccessible(true);

        self::assertSame(ProblemDetailsResponseFactory::INCLUDE_THROWABLE_DETAILS, $isDebug->getValue($factory));
        self::assertSame(true, $exceptionDetailsInResponse->getValue($factory));
    }

    public function testUsesJsonFlagsSettingFromConfigWhenPresent(): void
    {
        $this->container
            ->method('has')
            ->withConsecutive(['config'], [ResponseFactoryInterface::class])
            ->willReturnOnConsecutiveCalls(true, false);

        $this->container
            ->method('get')
            ->willReturnMap([
                ['config', ['problem-details' => ['json_flags' => JSON_PRETTY_PRINT]]],
                [
                    ResponseInterface::class,
                    function () {
                    },
                ],
            ]);

        $factoryFactory = new ProblemDetailsResponseFactoryFactory();
        $factory        = $factoryFactory($this->container);

        $jsonFlags = (new ReflectionObject($factory))->getProperty('jsonFlags');
        $jsonFlags->setAccessible(true);

        self::assertSame(JSON_PRETTY_PRINT, $jsonFlags->getValue($factory));
    }

    public function testUsesDefaultTypesSettingFromConfigWhenPresent(): void
    {
        $expectedDefaultTypes = [
            404 => 'https://example.com/problem-details/error/not-found',
        ];

        $this->container
            ->method('has')
            ->withConsecutive(['config'], [ResponseFactoryInterface::class])
            ->willReturnOnConsecutiveCalls(true, false);

        $this->container
            ->method('get')
            ->willReturnMap([
                ['config', ['problem-details' => ['default_types_map' => $expectedDefaultTypes]]],
                [
                    ResponseInterface::class,
                    function () {
                    },
                ],
            ]);

        $factoryFactory = new ProblemDetailsResponseFactoryFactory();
        $factory        = $factoryFactory($this->container);

        $defaultTypesMap = (new ReflectionObject($factory))->getProperty('defaultTypesMap');
        $defaultTypesMap->setAccessible(true);

        self::assertSame($expectedDefaultTypes, $defaultTypesMap->getValue($factory));
    }
}
