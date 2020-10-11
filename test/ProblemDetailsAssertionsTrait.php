<?php

/**
 * @see       https://github.com/mezzio/mezzio-problem-details for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-problem-details/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-problem-details/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\ProblemDetails;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\StreamInterface;
use Throwable;

use function array_walk_recursive;
use function get_class;
use function json_decode;
use function json_encode;
use function simplexml_load_string;
use function sprintf;
use function var_export;

trait ProblemDetailsAssertionsTrait
{
    public function assertProblemDetails(array $expected, array $details) : void
    {
        foreach ($expected as $key => $value) {
            $this->assertArrayHasKey(
                $key,
                $details,
                sprintf('Did not find key %s in problem details', $key)
            );

            $this->assertEquals($value, $details[$key], sprintf(
                'Did not find expected value for "%s" key of details; expected "%s", received "%s"',
                $key,
                var_export($value, true),
                var_export($details[$key], true)
            ));
        }
    }

    public function assertExceptionDetails(Throwable $e, array $details) : void
    {
        $this->assertArrayHasKey('class', $details);
        $this->assertSame(get_class($e), $details['class']);
        $this->assertArrayHasKey('code', $details);
        $this->assertSame($e->getCode(), (int) $details['code']);
        $this->assertArrayHasKey('message', $details);
        $this->assertSame($e->getMessage(), $details['message']);
        $this->assertArrayHasKey('file', $details);
        $this->assertSame($e->getFile(), $details['file']);
        $this->assertArrayHasKey('line', $details);
        $this->assertSame($e->getLine(), (int) $details['line']);

        // PHP does some odd things when creating the trace; individual items
        // may be objects, but once copied, they are arrays. This makes direct
        // comparison impossible; thus, only testing for correct type.
        $this->assertArrayHasKey('trace', $details);
        $this->assertInternalType('array', $details['trace']);
    }

    /**
     * @param StreamInterface|MockObject $stream
     */
    public function prepareResponsePayloadAssertions(
        string $contentType,
        MockObject $stream,
        callable $assertion
    ) : void {
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
     * @param StreamInterface|MockObject $stream
     */
    public function preparePayloadForJsonResponse(MockObject $stream, callable $assertion) : void
    {
        $stream
            ->expects($this->any())
            ->method('write')
            ->with($this->callback(function ($body) use ($assertion) {
                Assert::assertIsString($body);
                $data = json_decode($body, true);
                $assertion($data);
                return $body;
            }));
    }

    /**
     * @param StreamInterface|MockObject $stream
     */
    public function preparePayloadForXmlResponse(MockObject $stream, callable $assertion) : void
    {
        $stream
            ->expects($this->any())
            ->method('write')
            ->with($this->callback(function ($body) use ($assertion) {
                Assert::assertIsString($body);
                $data = $this->deserializeXmlPayload($body);
                $assertion($data);
                return $body;
            }));
    }

    public function deserializeXmlPayload(string $xml) : array
    {
        $xml = simplexml_load_string($xml);
        $json = json_encode($xml);
        $payload = json_decode($json, true);

        // Ensure ints and floats are properly represented
        array_walk_recursive($payload, function (&$item) {
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
