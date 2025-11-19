<?php

declare(strict_types=1);

namespace Dranzd\StorebunkAccounting\Application\Service;

use RuntimeException;

/**
 * Simple Synchronous Command Bus
 *
 * Routes commands to their handlers.
 * For MVP - simple array-based routing, synchronous execution.
 *
 * @package Dranzd\StorebunkAccounting\Application\Service
 */
final class CommandBus
{
    /**
     * @var array<string, object>
     */
    private array $handlers = [];

    /**
     * Register a command handler
     *
     * @param string $commandClass The command class name
     * @param object $handler The handler instance
     */
    final public function register(string $commandClass, object $handler): void
    {
        $this->handlers[$commandClass] = $handler;
    }

    /**
     * Dispatch a command to its handler
     *
     * @param object $command The command to dispatch
     * @throws RuntimeException If no handler registered
     */
    final public function dispatch(object $command): void
    {
        $commandClass = get_class($command);

        if (!isset($this->handlers[$commandClass])) {
            throw new RuntimeException("No handler registered for command: {$commandClass}");
        }

        $handler = $this->handlers[$commandClass];
        $handler->handle($command);
    }
}
