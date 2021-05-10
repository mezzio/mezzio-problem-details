<?php

declare(strict_types=1);

namespace MezzioTest\ProblemDetails\Exception;

use Exception;
use Mezzio\ProblemDetails\Exception\CommonProblemDetailsExceptionTrait;
use Mezzio\ProblemDetails\Exception\ProblemDetailsExceptionInterface;
use PHPUnit\Framework\TestCase;

use function json_decode;
use function json_encode;

class ProblemDetailsExceptionInterfaceTest extends TestCase
{
    /** @var int */
    protected $status = 403;
    /** @var string */
    protected $detail = 'You are not authorized to do that';
    /** @var string */
    protected $title = 'Unauthorized';
    /** @var string */
    protected $type = 'https://httpstatus.es/403';
    /** @var string[] */
    protected $additional = [
        'foo' => 'bar',
    ];

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
        $this->assertEquals($this->status, $this->exception->getStatus());
        $this->assertEquals($this->detail, $this->exception->getDetail());
        $this->assertEquals($this->title, $this->exception->getTitle());
        $this->assertEquals($this->type, $this->exception->getType());
        $this->assertEquals($this->additional, $this->exception->getAdditionalData());
    }

    public function testCanCastDetailsToArray(): void
    {
        $this->assertEquals([
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

        $this->assertEquals([
            'status' => $this->status,
            'detail' => $this->detail,
            'title'  => $this->title,
            'type'   => $this->type,
            'foo'    => 'bar',
        ], $problem);
    }
}
