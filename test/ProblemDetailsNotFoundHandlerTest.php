<?php

/**
 * @see       https://github.com/mezzio/mezzio-problem-details for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-problem-details/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-problem-details/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\ProblemDetails;

use Mezzio\ProblemDetails\ProblemDetailsNotFoundHandler;
use Mezzio\ProblemDetails\ProblemDetailsResponseFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ProblemDetailsNotFoundHandlerTest extends TestCase
{
    use ProblemDetailsAssertionsTrait;

    public function setUp()
    {
        $this->responseFactory = $this->prophesize(ProblemDetailsResponseFactory::class);
    }

    public function acceptHeaders() : array
    {
        return [
            'application/json' => ['application/json', 'application/problem+json'],
            'application/xml'  => ['application/xml', 'application/problem+xml'],
        ];
    }

    /**
     * @dataProvider acceptHeaders
     */
    public function testResponseFactoryPassedInConstructorGeneratesTheReturnedResponse(string $acceptHeader) : void
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn('POST');
        $request->getHeaderLine('Accept')->willReturn($acceptHeader);
        $request->getUri()->willReturn('https://example.com/foo');

        $response = $this->prophesize(ResponseInterface::class);

        $this->responseFactory->createResponse(
            Argument::that([$request, 'reveal']),
            404,
            'Cannot POST https://example.com/foo!'
        )->will([$response, 'reveal']);

        $notFoundHandler = new ProblemDetailsNotFoundHandler($this->responseFactory->reveal());

        $this->assertSame(
            $response->reveal(),
            $notFoundHandler->process($request->reveal(), $this->prophesize(RequestHandlerInterface::class)->reveal())
        );
    }

    public function testHandlerIsCalledIfAcceptHeaderIsUnacceptable() : void
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn('POST');
        $request->getHeaderLine('Accept')->willReturn('text/html');
        $request->getUri()->willReturn('https://example.com/foo');

        $response = $this->prophesize(ResponseInterface::class);

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle($request->reveal())->will([$response, 'reveal']);

        $notFoundHandler = new ProblemDetailsNotFoundHandler($this->responseFactory->reveal());

        $this->assertSame(
            $response->reveal(),
            $notFoundHandler->process($request->reveal(), $handler->reveal())
        );
    }
}
