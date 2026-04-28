<?php

declare(strict_types=1);

namespace MezzioTest\ProblemDetails\Exception;

use Exception;
use Mezzio\ProblemDetails\Exception\CommonProblemDetailsExceptionTrait;
use Mezzio\ProblemDetails\Exception\ProblemDetailsExceptionInterface;
use PHPUnit\Framework\TestCase;

use function json_decode;
use function json_encode;

use const JSON_THROW_ON_ERROR;

final class ProblemDetailsExceptionInterfaceTest extends TestCase
{
    private int $status    = 403;
    private string $detail = 'You are not authorized to do that';
    private string $title  = 'Unauthorized';
    private string $type   = 'https://httpstatus.es/403';
    /** @var array<string, string> */
    private array $additional = [
        'foo' => 'bar',
    ];
    private ProblemDetailsExceptionInterface $exception;

    protected function setUp(): void
    {
        $this->exception = new class (
            $this->status,
            $this->detail,
            $this->title,
            $this->type,
            $this->additional
        ) extends Exception implements ProblemDetailsExceptionInterface {
            use CommonProblemDetailsExceptionTrait;

            /** @param array<string, mixed> $additional */
            public function __construct(int $status, string $detail, string $title, string $type, array $additional)
            {
                $this->status     = $status;
                $this->detail     = $detail;
                $this->title      = $title;
                $this->type       = $type;
                $this->additional = $additional;
            }
        };
    }

    public function testCanPullDetailsIndividually(): void
    {
        $this->assertSame($this->status, $this->exception->getStatus());
        $this->assertSame($this->detail, $this->exception->getDetail());
        $this->assertSame($this->title, $this->exception->getTitle());
        $this->assertSame($this->type, $this->exception->getType());
        $this->assertEquals($this->additional, $this->exception->getAdditionalData());
    }

    public function testCanCastDetailsToArray(): void
    {
        $this->assertSame([
            'foo'    => 'bar',
            'status' => $this->status,
            'detail' => $this->detail,
            'title'  => $this->title,
            'type'   => $this->type,
        ], $this->exception->toArray());
    }

    public function testIsJsonSerializable(): void
    {
        $problem = json_decode(json_encode($this->exception, JSON_THROW_ON_ERROR), true, JSON_THROW_ON_ERROR);
        $this->assertIsArray($problem);

        $this->assertSame([
            'foo'    => 'bar',
            'status' => $this->status,
            'detail' => $this->detail,
            'title'  => $this->title,
            'type'   => $this->type,
        ], $problem);
    }
}
