<?php

/**
 * @see       https://github.com/mezzio/mezzio-problem-details for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-problem-details/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-problem-details/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\ProblemDetails;

use Mezzio\ProblemDetails\ProblemDetailsMiddleware;
use Mezzio\ProblemDetails\ProblemDetailsMiddlewareFactory;
use Mezzio\ProblemDetails\ProblemDetailsResponseFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ProblemDetailsMiddlewareFactoryTest extends TestCase
{
    protected function setUp() : void
    {
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->factory = new ProblemDetailsMiddlewareFactory();
    }

    public function testCreatesMiddlewareWithoutResponseFactoryIfServiceDoesNotExist() : void
    {
        $this->container->has(ProblemDetailsResponseFactory::class)->willReturn(false);
        $this->container->has(\Zend\ProblemDetails\ProblemDetailsResponseFactory::class)->willReturn(false);
        $this->container->get(ProblemDetailsResponseFactory::class)->shouldNotBeCalled();
        $this->container->get(\Zend\ProblemDetails\ProblemDetailsResponseFactory::class)->shouldNotBeCalled();

        $middleware = ($this->factory)($this->container->reveal());

        $this->assertInstanceOf(ProblemDetailsMiddleware::class, $middleware);
        $this->assertAttributeInstanceOf(
            ProblemDetailsResponseFactory::class,
            'responseFactory',
            $middleware
        );
    }

    public function testCreatesMiddlewareUsingResponseFactoryService() : void
    {
        $responseFactory = $this->prophesize(ProblemDetailsResponseFactory::class)->reveal();
        $this->container->has(ProblemDetailsResponseFactory::class)->willReturn(true);
        $this->container->get(ProblemDetailsResponseFactory::class)->willReturn($responseFactory);

        $middleware = ($this->factory)($this->container->reveal());

        $this->assertInstanceOf(ProblemDetailsMiddleware::class, $middleware);
        $this->assertAttributeSame(
            $responseFactory,
            'responseFactory',
            $middleware
        );
    }
}
