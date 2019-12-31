<?php

/**
 * @see       https://github.com/mezzio/mezzio-problem-details for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-problem-details/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-problem-details/blob/master/LICENSE.md New BSD License
 */

namespace MezzioTest\ProblemDetails;

use Mezzio\ProblemDetails\ProblemDetailsNotFoundHandler;
use Mezzio\ProblemDetails\ProblemDetailsNotFoundHandlerFactory;
use Mezzio\ProblemDetails\ProblemDetailsResponseFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ProblemDetailsNotFoundHandlerFactoryTest extends TestCase
{
    protected function setUp() : void
    {
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->factory = new ProblemDetailsNotFoundHandlerFactory();
    }

    public function testCreatesNotFoundHandlerWithoutResponseFactoryIfServiceDoesNotExist() : void
    {
        $this->container->has(ProblemDetailsResponseFactory::class)->willReturn(false);
        $this->container->has(\Zend\ProblemDetails\ProblemDetailsResponseFactory::class)->willReturn(false);
        $this->container->get(ProblemDetailsResponseFactory::class)->shouldNotBeCalled();
        $this->container->get(\Zend\ProblemDetails\ProblemDetailsResponseFactory::class)->shouldNotBeCalled();

        $notFoundHandler = ($this->factory)($this->container->reveal());

        $this->assertInstanceOf(ProblemDetailsNotFoundHandler::class, $notFoundHandler);
        $this->assertAttributeInstanceOf(
            ProblemDetailsResponseFactory::class,
            'responseFactory',
            $notFoundHandler
        );
    }

    public function testCreatesNotFoundHandlerUsingResponseFactoryService() : void
    {
        $responseFactory = $this->prophesize(ProblemDetailsResponseFactory::class)->reveal();
        $this->container->has(ProblemDetailsResponseFactory::class)->willReturn(true);
        $this->container->get(ProblemDetailsResponseFactory::class)->willReturn($responseFactory);

        $notFoundHandler = ($this->factory)($this->container->reveal());

        $this->assertInstanceOf(ProblemDetailsNotFoundHandler::class, $notFoundHandler);
        $this->assertAttributeSame(
            $responseFactory,
            'responseFactory',
            $notFoundHandler
        );
    }
}
