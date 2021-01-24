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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ProblemDetailsNotFoundHandlerTest extends TestCase
{
    use ProblemDetailsAssertionsTrait;

    protected function setUp(): void
    {
        $this->responseFactory = $this->createMock(ProblemDetailsResponseFactory::class);
    }

    public function acceptHeaders(): array
    {
        return [
            'application/json' => ['application/json', 'application/problem+json'],
            'application/xml'  => ['application/xml', 'application/problem+xml'],
        ];
    }

    /**
     * @dataProvider acceptHeaders
     */
    public function testResponseFactoryPassedInConstructorGeneratesTheReturnedResponse(string $acceptHeader): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getMethod')->willReturn('POST');
        $request->method('getHeaderLine')->with('Accept')->willReturn($acceptHeader);
        $request->method('getUri')->willReturn('https://example.com/foo');

        $response = $this->createMock(ResponseInterface::class);

        $this->responseFactory
            ->method('createResponse')
            ->with(
                $request,
                404,
                'Cannot POST https://example.com/foo!'
            )->willReturn($response);

        $notFoundHandler = new ProblemDetailsNotFoundHandler($this->responseFactory);

        $this->assertSame(
            $response,
            $notFoundHandler->process($request, $this->createMock(RequestHandlerInterface::class))
        );
    }

    public function testHandlerIsCalledIfAcceptHeaderIsUnacceptable(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getMethod')->willReturn('POST');
        $request->method('getHeaderLine')->with('Accept')->willReturn('text/html');
        $request->method('getUri')->willReturn('https://example.com/foo');

        $response = $this->createMock(ResponseInterface::class);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->with($request)->willReturn($response);

        $notFoundHandler = new ProblemDetailsNotFoundHandler($this->responseFactory);

        $this->assertSame(
            $response,
            $notFoundHandler->process($request, $handler)
        );
    }
}
