<?php

/**
 * @see       https://github.com/mezzio/mezzio-problem-details for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-problem-details/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-problem-details/blob/master/LICENSE.md New BSD License
 */

namespace MezzioTest\ProblemDetails\TestAsset;

use RuntimeException as BaseRuntimeException;
use Throwable;

class RuntimeException extends BaseRuntimeException
{
    /**
     * @param string $message
     * @param mixed $code Mimic PHP internal exceptions, and allow any code.
     * @param Throwable $previous
     */
    public function __construct(string $message, $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->code = $code;
    }
}
