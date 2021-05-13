<?php

declare(strict_types=1);

namespace Mezzio\ProblemDetails\Exception;

use RuntimeException;

class InvalidResponseBodyException extends RuntimeException implements ExceptionInterface
{
}
