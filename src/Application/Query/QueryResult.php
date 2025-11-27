<?php

declare(strict_types=1);

namespace Dranzd\StorebunkAccounting\Application\Query;

use Dranzd\Common\Cqrs\Application\Query\Result;

/**
 * Query Result Implementation
 *
 * Standard implementation of the Result interface for query responses.
 *
 * @package Dranzd\StorebunkAccounting\Application\Query
 */
final class QueryResult implements Result
{
    /**
     * @param mixed $data The query result data
     * @param bool $success Whether the query was successful
     * @param array<string, mixed> $metadata Additional metadata
     */
    private function __construct(
        private readonly mixed $data,
        private readonly bool $success = true,
        private readonly array $metadata = []
    ) {
    }

    /**
     * Create a successful result
     *
     * @param mixed $data The query result data
     * @param array<string, mixed> $metadata Optional metadata
     */
    public static function success(mixed $data, array $metadata = []): self
    {
        return new self($data, true, $metadata);
    }

    /**
     * Create a failed result
     *
     * @param string $errorMessage The error message
     * @param array<string, mixed> $metadata Optional metadata
     */
    public static function failure(string $errorMessage, array $metadata = []): self
    {
        return new self(
            null,
            false,
            array_merge($metadata, ['error' => $errorMessage])
        );
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'data' => $this->data,
            'metadata' => $this->metadata,
        ];
    }
}
