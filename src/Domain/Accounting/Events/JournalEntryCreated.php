<?php

declare(strict_types=1);

namespace Dranzd\StorebunkAccounting\Domain\Accounting\Events;

use DateTimeImmutable;
use DateTimeInterface;

/**
 * Journal Entry Created Event
 *
 * Emitted when a new journal entry is created in draft state.
 *
 * @package Dranzd\StorebunkAccounting\Domain\Accounting\Events
 */
final readonly class JournalEntryCreated implements DomainEvent
{
    public function __construct(
        private string $eventId,
        private string $journalEntryId,
        private DateTimeInterface $date,
        private string $description,
        private array $lines,
        private DateTimeImmutable $occurredAt
    ) {
    }

    public function getEventId(): string
    {
        return $this->eventId;
    }

    public function getAggregateId(): string
    {
        return $this->journalEntryId;
    }

    public function getJournalEntryId(): string
    {
        return $this->journalEntryId;
    }

    public function getDate(): DateTimeInterface
    {
        return $this->date;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getLines(): array
    {
        return $this->lines;
    }

    public function getOccurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function toArray(): array
    {
        return [
            'eventId' => $this->eventId,
            'journalEntryId' => $this->journalEntryId,
            'date' => $this->date->format('Y-m-d'),
            'description' => $this->description,
            'lines' => $this->lines,
            'occurredAt' => $this->occurredAt->format(DateTimeInterface::ATOM),
        ];
    }
}
