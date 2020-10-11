<?php

/**
 * @see       https://github.com/mezzio/mezzio-problem-details for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-problem-details/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-problem-details/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\ProblemDetails;

use Mezzio\ProblemDetails\ProblemDetailsNotFoundHandler;
use Mezzio\ProblemDetails\ProblemDetailsNotFoundHandlerFactory;
use Mezzio\ProblemDetails\ProblemDetailsResponseFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;

class ProblemDetailsNotFoundHandlerFactoryTest extends TestCase
{
    protected function setUp() : void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->factory = new ProblemDetailsNotFoundHandlerFactory();
    }

    public function testRaisesExceptionWhenProblemDetailsResponseFactoryServiceIsNotAvailable()
    {
        $e = new RuntimeException();
        $this->container
            ->method('get')
            ->with(ProblemDetailsResponseFactory::class)
            ->willThrowException($e);

        $this->expectException(RuntimeException::class);
        $this->factory->__invoke($this->container);
    }

    public function testCreatesNotFoundHandlerUsingResponseFactoryService() : void
    {
        $responseFactory = $this->createMock(ProblemDetailsResponseFactory::class);
        $this->container
            ->method('get')
            ->with(ProblemDetailsResponseFactory::class)
            ->willReturn($responseFactory);

        $notFoundHandler = ($this->factory)($this->container);

        $r = (new \ReflectionObject($notFoundHandler))->getProperty('responseFactory');
        $r->setAccessible(true);

        $this->assertInstanceOf(ProblemDetailsNotFoundHandler::class, $notFoundHandler);
        $this->assertSame($responseFactory, $r->getValue($notFoundHandler));
    }
}
