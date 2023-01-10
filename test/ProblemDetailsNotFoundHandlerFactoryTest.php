<?php

declare(strict_types=1);

namespace MezzioTest\ProblemDetails;

use Mezzio\ProblemDetails\ProblemDetailsNotFoundHandler;
use Mezzio\ProblemDetails\ProblemDetailsNotFoundHandlerFactory;
use Mezzio\ProblemDetails\ProblemDetailsResponseFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionObject;
use RuntimeException;

class ProblemDetailsNotFoundHandlerFactoryTest extends TestCase
{
    /** @var ContainerInterface&MockObject */
    private ContainerInterface $container;

    private ProblemDetailsNotFoundHandlerFactory $factory;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->factory   = new ProblemDetailsNotFoundHandlerFactory();
    }

    public function testRaisesExceptionWhenProblemDetailsResponseFactoryServiceIsNotAvailable(): void
    {
        $e = new RuntimeException();
        $this->container
            ->method('get')
            ->with(ProblemDetailsResponseFactory::class)
            ->willThrowException($e);

        $this->expectException(RuntimeException::class);
        $this->factory->__invoke($this->container);
    }

    public function testCreatesNotFoundHandlerUsingResponseFactoryService(): void
    {
        $responseFactory = $this->createMock(ProblemDetailsResponseFactory::class);
        $this->container
            ->method('get')
            ->with(ProblemDetailsResponseFactory::class)
            ->willReturn($responseFactory);

        $notFoundHandler = ($this->factory)($this->container);

        $r = (new ReflectionObject($notFoundHandler))->getProperty('responseFactory');
        $r->setAccessible(true);

        self::assertInstanceOf(ProblemDetailsNotFoundHandler::class, $notFoundHandler);
        self::assertSame($responseFactory, $r->getValue($notFoundHandler));
    }
}
