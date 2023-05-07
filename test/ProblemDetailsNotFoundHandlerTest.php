<?php

declare(strict_types=1);

namespace MezzioTest\ProblemDetails;

use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Stream;
use Laminas\Diactoros\Uri;
use Mezzio\ProblemDetails\ProblemDetailsNotFoundHandler;
use Mezzio\ProblemDetails\ProblemDetailsResponseFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ProblemDetailsNotFoundHandlerTest extends TestCase
{
    use ProblemDetailsAssertionsTrait;

    /** @var ProblemDetailsResponseFactory&MockObject */
    private $responseFactory;

    protected function setUp(): void
    {
        $this->responseFactory = $this->createMock(ProblemDetailsResponseFactory::class);
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function acceptHeaders(): array
    {
        return [
            'application/json' => ['application/json', 'application/problem+json'],
            'application/xml'  => ['application/xml', 'application/problem+xml'],
        ];
    }

    #[DataProvider('acceptHeaders')]
    public function testResponseFactoryPassedInConstructorGeneratesTheReturnedResponse(string $acceptHeader): void
    {
        $request = new ServerRequest(
            [],
            [],
            new Uri('https://example.com/foo'),
            'POST',
            new Stream('php://memory'),
            ['Accept' => $acceptHeader]
        );

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
        $request = new ServerRequest(
            [],
            [],
            new Uri('https://example.com/foo'),
            'POST',
            new Stream('php://memory'),
            ['Accept' => 'text/html']
        );

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
