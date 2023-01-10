<?php

declare(strict_types=1);

namespace MezzioTest\ProblemDetails\Exception;

use Exception;
use Mezzio\ProblemDetails\Exception\CommonProblemDetailsExceptionTrait;
use Mezzio\ProblemDetails\Exception\ProblemDetailsExceptionInterface;
use PHPUnit\Framework\TestCase;

use function json_decode;
use function json_encode;

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
        self::assertEquals($this->status, $this->exception->getStatus());
        self::assertEquals($this->detail, $this->exception->getDetail());
        self::assertEquals($this->title, $this->exception->getTitle());
        self::assertEquals($this->type, $this->exception->getType());
        self::assertEquals($this->additional, $this->exception->getAdditionalData());
    }

    public function testCanCastDetailsToArray(): void
    {
        self::assertEquals([
            'status' => $this->status,
            'detail' => $this->detail,
            'title'  => $this->title,
            'type'   => $this->type,
            'foo'    => 'bar',
        ], $this->exception->toArray());
    }

    public function testIsJsonSerializable(): void
    {
        $problem = json_decode(json_encode($this->exception), true);
        self::assertIsArray($problem);

        self::assertEquals([
            'status' => $this->status,
            'detail' => $this->detail,
            'title'  => $this->title,
            'type'   => $this->type,
            'foo'    => 'bar',
        ], $problem);
    }
}
