<?php

declare(strict_types=1);

namespace Dranzd\StorebunkAccounting\Domain\Accounting\Events;

use DateTimeImmutable;

/**
 * Base Domain Event Interface
 *
 * All domain events must implement this interface.
 * Events represent things that have happened in the domain.
 *
 * @package Dranzd\StorebunkAccounting\Domain\Accounting\Events
 */
interface DomainEvent
{
    /**
     * Get the unique event ID
     */
    public function getEventId(): string;

    /**
     * Get the aggregate ID this event belongs to
     */
    public function getAggregateId(): string;

    /**
     * Get when the event occurred
     */
    public function getOccurredAt(): DateTimeImmutable;

    /**
     * Get the event payload as an array
     */
    public function toArray(): array;
}
