<?php

declare(strict_types=1);

namespace MezzioTest\ProblemDetails;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\StreamInterface;
use Throwable;

use function array_walk_recursive;
use function assert;
use function is_array;
use function json_decode;
use function json_encode;
use function simplexml_load_string;
use function sprintf;
use function var_export;

trait ProblemDetailsAssertionsTrait
{
    /**
     * @param array<string, mixed> $expected
     * @param array<array-key, mixed> $details
     */
    public function assertProblemDetails(array $expected, array $details): void
    {
        /** @psalm-var mixed $value */
        foreach ($expected as $key => $value) {
            self::assertArrayHasKey(
                $key,
                $details,
                sprintf('Did not find key %s in problem details', $key),
            );

            self::assertEquals($value, $details[$key], sprintf(
                'Did not find expected value for "%s" key of details; expected "%s", received "%s"',
                $key,
                var_export($value, true),
                var_export($details[$key], true),
            ));
        }
    }

    public function assertExceptionDetails(Throwable $e, array $details): void
    {
        self::assertArrayHasKey('class', $details);
        self::assertSame($e::class, $details['class']);
        self::assertArrayHasKey('code', $details);
        self::assertSame($e->getCode(), (int) $details['code']);
        self::assertArrayHasKey('message', $details);
        self::assertSame($e->getMessage(), $details['message']);
        self::assertArrayHasKey('file', $details);
        self::assertSame($e->getFile(), $details['file']);
        self::assertArrayHasKey('line', $details);
        self::assertSame($e->getLine(), (int) $details['line']);

        // PHP does some odd things when creating the trace; individual items
        // may be objects, but once copied, they are arrays. This makes direct
        // comparison impossible; thus, only testing for correct type.
        self::assertArrayHasKey('trace', $details);
        self::assertIsArray($details['trace']);
    }

    /**
     * @param StreamInterface&MockObject $stream
     */
    public function prepareResponsePayloadAssertions(
        string $contentType,
        MockObject $stream,
        callable $assertion
    ): void {
        if ('application/problem+json' === $contentType) {
            $this->preparePayloadForJsonResponse($stream, $assertion);
            return;
        }

        if ('application/problem+xml' === $contentType) {
            $this->preparePayloadForXmlResponse($stream, $assertion);
            return;
        }
    }

    /**
     * @param StreamInterface&MockObject $stream
     */
    public function preparePayloadForJsonResponse(MockObject $stream, callable $assertion): void
    {
        $stream
            ->expects(self::any())
            ->method('write')
            ->with(self::callback(static function ($body) use ($assertion): bool {
                Assert::assertIsString($body);
                Assert::assertJson($body);
                $data = json_decode($body, true);
                Assert::assertIsArray($data);
                $assertion($data);
                return true;
            }));
    }

    /**
     * @param StreamInterface&MockObject $stream
     */
    public function preparePayloadForXmlResponse(MockObject $stream, callable $assertion): void
    {
        $stream
            ->expects(self::any())
            ->method('write')
            ->with(self::callback(function ($body) use ($assertion): bool {
                Assert::assertIsString($body);
                $data = $this->deserializeXmlPayload($body);
                $assertion($data);
                return true;
            }));
    }

    public function deserializeXmlPayload(string $xml): array
    {
        $xml     = simplexml_load_string($xml);
        $json    = json_encode($xml);
        $payload = json_decode($json, true);
        assert(is_array($payload));

        // Ensure ints and floats are properly represented
        array_walk_recursive($payload, static function (mixed &$item): void {
            if ((string) (int) $item === $item) {
                $item = (int) $item;
                return;
            }

            if ((string) (float) $item === $item) {
                $item = (float) $item;
                return;
            }
        });

        return $payload;
    }
}
