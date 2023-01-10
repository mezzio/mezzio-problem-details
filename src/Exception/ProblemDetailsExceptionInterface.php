<?php

declare(strict_types=1);

namespace Mezzio\ProblemDetails\Exception;

use JsonSerializable;
use Throwable;

/**
 * Defines an exception type for generating Problem Details.
 */
interface ProblemDetailsExceptionInterface extends JsonSerializable, Throwable
{
    public function getStatus(): int;

    public function getType(): string;

    public function getTitle(): string;

    public function getDetail(): string;

    /** @return array<string, mixed> */
    public function getAdditionalData(): array;

    /**
     * Serialize the exception to an array of problem details.
     *
     * Likely useful for the JsonSerializable implementation, but also
     * for cases where the XML variant is desired.
     */
    public function toArray(): array;
}
