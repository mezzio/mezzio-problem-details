<?php

declare(strict_types=1);

namespace MezzioTest\ProblemDetails\TestAsset;

use RuntimeException as BaseRuntimeException;
use Throwable;

class RuntimeException extends BaseRuntimeException
{
    /**
     * @param mixed $code Mimic PHP internal exceptions, and allow any code.
     */
    public function __construct(string $message, $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->code = $code;
    }
}
