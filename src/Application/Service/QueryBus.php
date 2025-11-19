<?php

declare(strict_types=1);

namespace Dranzd\StorebunkAccounting\Application\Service;

use RuntimeException;

/**
 * Simple Synchronous Query Bus
 *
 * Routes queries to their handlers.
 * For MVP - simple array-based routing, synchronous execution.
 *
 * @package Dranzd\StorebunkAccounting\Application\Service
 */
final class QueryBus
{
    /**
     * @var array<string, object>
     */
    private array $handlers = [];

    /**
     * Register a query handler
     *
     * @param string $queryClass The query class name
     * @param object $handler The handler instance
     */
    final public function register(string $queryClass, object $handler): void
    {
        $this->handlers[$queryClass] = $handler;
    }

    /**
     * Dispatch a query to its handler
     *
     * @param object $query The query to dispatch
     * @return mixed The query result
     * @throws RuntimeException If no handler registered
     */
    final public function ask(object $query): mixed
    {
        $queryClass = get_class($query);

        if (!isset($this->handlers[$queryClass])) {
            throw new RuntimeException("No handler registered for query: {$queryClass}");
        }

        $handler = $this->handlers[$queryClass];
        return $handler->handle($query);
    }
}
