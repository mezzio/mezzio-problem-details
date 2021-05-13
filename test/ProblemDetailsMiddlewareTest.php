<?php

declare(strict_types=1);

namespace MezzioTest\ProblemDetails;

use ErrorException;
use Mezzio\ProblemDetails\ProblemDetailsMiddleware;
use Mezzio\ProblemDetails\ProblemDetailsResponseFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function trigger_error;

use const E_USER_ERROR;

class ProblemDetailsMiddlewareTest extends TestCase
{
    use ProblemDetailsAssertionsTrait;

    /** @var ProblemDetailsMiddleware */
    private $middleware;

    /** @var ServerRequestInterface&MockObject */
    private $request;

    /** @var ProblemDetailsResponseFactory&MockObject */
    private $responseFactory;

    protected function setUp(): void
    {
        $this->request         = $this->createMock(ServerRequestInterface::class);
        $this->responseFactory = $this->createMock(ProblemDetailsResponseFactory::class);
        $this->middleware      = new ProblemDetailsMiddleware($this->responseFactory);
    }

    public function acceptHeaders(): array
    {
        return [
            'empty'                    => [''],
            'application/xml'          => ['application/xml'],
            'application/vnd.api+xml'  => ['application/vnd.api+xml'],
            'application/json'         => ['application/json'],
            'application/vnd.api+json' => ['application/vnd.api+json'],
        ];
    }

    public function testSuccessfulDelegationReturnsHandlerResponse(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $handler  = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->method('handle')
            ->with($this->request)
            ->willReturn($response);

        $result = $this->middleware->process($this->request, $handler);

        $this->assertSame($response, $result);
    }

    /**
     * @dataProvider acceptHeaders
     */
    public function testThrowableRaisedByHandlerResultsInProblemDetails(string $accept): void
    {
        $this->request
            ->method('getHeaderLine')
            ->with('Accept')
            ->willReturn($accept);

        $exception = new TestAsset\RuntimeException('Thrown!', 507);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->method('handle')
            ->with($this->request)
            ->willThrowException($exception);

        $expected = $this->createMock(ResponseInterface::class);
        $this->responseFactory
            ->method('createResponseFromThrowable')
            ->with($this->request, $exception)
            ->willReturn($expected);

        $result = $this->middleware->process($this->request, $handler);

        $this->assertSame($expected, $result);
    }

    /**
     * @dataProvider acceptHeaders
     */
    public function testMiddlewareRegistersErrorHandlerToConvertErrorsToProblemDetails(string $accept): void
    {
        $this->request
            ->method('getHeaderLine')
            ->with('Accept')
            ->willReturn($accept);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->method('handle')
            ->with($this->request)
            ->willReturnCallback(function () {
                trigger_error('Triggered error!', E_USER_ERROR);
            });

        $expected = $this->createMock(ResponseInterface::class);
        $this->responseFactory
            ->method('createResponseFromThrowable')
            ->with($this->request, $this->callback(function ($e) {
                $this->assertInstanceOf(ErrorException::class, $e);
                $this->assertEquals(E_USER_ERROR, $e->getSeverity());
                $this->assertEquals('Triggered error!', $e->getMessage());
                return true;
            }))
            ->willReturn($expected);

        $result = $this->middleware->process($this->request, $handler);

        $this->assertSame($expected, $result);
    }

    public function testRethrowsCaughtExceptionIfUnableToNegotiateAcceptHeader(): void
    {
        $this->request
            ->method('getHeaderLine')
            ->with('Accept')
            ->willReturn('text/html');

        $exception = new TestAsset\RuntimeException('Thrown!', 507);
        $handler   = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->method('handle')
            ->with($this->request)
            ->willThrowException($exception);

        $this->expectException(TestAsset\RuntimeException::class);
        $this->expectExceptionMessage('Thrown!');
        $this->expectExceptionCode(507);
        $this->middleware->process($this->request, $handler);
    }

    /**
     * @dataProvider acceptHeaders
     */
    public function testErrorHandlingTriggersListeners(string $accept): void
    {
        $this->request
            ->method('getHeaderLine')
            ->with('Accept')
            ->willReturn($accept);

        $exception = new TestAsset\RuntimeException('Thrown!', 507);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->method('handle')
            ->with($this->request)
            ->willThrowException($exception);

        $expected = $this->createMock(ResponseInterface::class);
        $this->responseFactory
            ->method('createResponseFromThrowable')
            ->with($this->request, $exception)
            ->willReturn($expected);

        $listener  = function ($error, $request, $response) use ($exception, $expected) {
            $this->assertSame($exception, $error, 'Listener did not receive same exception as was raised');
            $this->assertSame($this->request, $request, 'Listener did not receive same request');
            $this->assertSame($expected, $response, 'Listener did not receive same response');
        };
        $listener2 = clone $listener;
        $this->middleware->attachListener($listener);
        $this->middleware->attachListener($listener2);

        $result = $this->middleware->process($this->request, $handler);

        $this->assertSame($expected, $result);
    }
}
