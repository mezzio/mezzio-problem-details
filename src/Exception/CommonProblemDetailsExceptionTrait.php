<?php

declare(strict_types=1);

namespace Mezzio\ProblemDetails\Exception;

// phpcs:ignore SlevomatCodingStandard.Namespaces.UnusedUses.UnusedUse
use ReturnTypeWillChange;

use function array_merge;

/**
 * Common functionality for ProblemDetailsExceptionInterface implementations.
 *
 * Composing classes may set the following properties (each has a safe default,
 * so an instance constructed without setting them stays well-formed instead of
 * causing a TypeError in the typed getters):
 *
 * - status (int; default 500)
 * - detail (string; default '')
 * - title (string; default '' — ProblemDetailsResponseFactory derives it from status)
 * - type (string; default '' — ProblemDetailsResponseFactory derives it from status)
 * - additional (array; default [])
 */
trait CommonProblemDetailsExceptionTrait
{
    private int $status = 500;

    private string $detail = '';

    private string $title = '';

    private string $type = '';

    /** @var array<string, mixed> */
    private array $additional = [];

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDetail(): string
    {
        return $this->detail;
    }

    /** @return array<string, mixed> */
    public function getAdditionalData(): array
    {
        return $this->additional;
    }

    /**
     * Serialize the exception to an array of problem details.
     *
     * Likely useful for the JsonSerializable implementation, but also
     * for cases where the XML variant is desired.
     */
    public function toArray(): array
    {
        $problem = [
            'status' => $this->status,
            'detail' => $this->detail,
            'title'  => $this->title,
            'type'   => $this->type,
        ];

        if ($this->additional) {
            $problem = array_merge($this->additional, $problem);
        }

        return $problem;
    }

    /**
     * Allow serialization via json_encode().
     *
     * @return array
     */
    #[ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
