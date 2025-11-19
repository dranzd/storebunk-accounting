<?php

declare(strict_types=1);

namespace Dranzd\StorebunkAccounting\Infrastructure\Persistence\EventStore;

use Dranzd\Common\EventSourcing\Domain\EventSourcing\AggregateEvent;

/**
 * In-Memory Event Store
 *
 * Simple event store implementation for MVP.
 * Stores events in memory - no persistence across restarts.
 *
 * @package Dranzd\StorebunkAccounting\Infrastructure\Persistence\EventStore
 */
final class InMemoryEventStore
{
    /**
     * @var array<string, array<int, AggregateEvent>>
     */
    private array $streams = [];

    /**
     * @var array<callable>
     */
    private array $subscribers = [];

    /**
     * Append events to a stream
     *
     * @param string $streamId The stream identifier (usually aggregate ID)
     * @param array<int, AggregateEvent> $events Events to append
     */
    final public function appendToStream(string $streamId, array $events): void
    {
        if (!isset($this->streams[$streamId])) {
            $this->streams[$streamId] = [];
        }

        foreach ($events as $event) {
            $this->streams[$streamId][] = $event;

            // Notify subscribers
            foreach ($this->subscribers as $subscriber) {
                $subscriber($event);
            }
        }
    }

    /**
     * Read all events from a stream
     *
     * @param string $streamId The stream identifier
     * @return array<int, AggregateEvent> Array of events
     */
    final public function readStream(string $streamId): array
    {
        return $this->streams[$streamId] ?? [];
    }

    /**
     * Check if a stream exists
     *
     * @param string $streamId The stream identifier
     * @return bool True if stream exists
     */
    final public function streamExists(string $streamId): bool
    {
        return isset($this->streams[$streamId]) && count($this->streams[$streamId]) > 0;
    }

    /**
     * Subscribe to all events
     *
     * @param callable $subscriber Callback function that receives events
     */
    final public function subscribe(callable $subscriber): void
    {
        $this->subscribers[] = $subscriber;
    }

    /**
     * Get all events across all streams (for projections)
     *
     * @return array<int, AggregateEvent> All events in order
     */
    final public function getAllEvents(): array
    {
        $allEvents = [];

        foreach ($this->streams as $events) {
            foreach ($events as $event) {
                $allEvents[] = $event;
            }
        }

        return $allEvents;
    }

    /**
     * Clear all streams (for testing)
     */
    final public function clear(): void
    {
        $this->streams = [];
    }
}
