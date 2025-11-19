<?php

declare(strict_types=1);

namespace Dranzd\StorebunkAccounting\Domain\Accounting\Events;

use DateTimeImmutable;
use DateTimeInterface;

/**
 * Journal Entry Posted Event
 *
 * Emitted when a journal entry is posted to the ledger.
 * This triggers the ledger projection to update account balances.
 *
 * @package Dranzd\StorebunkAccounting\Domain\Accounting\Events
 */
final readonly class JournalEntryPosted implements DomainEvent
{
    public function __construct(
        private string $eventId,
        private string $journalEntryId,
        private DateTimeImmutable $postedAt,
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

    public function getPostedAt(): DateTimeImmutable
    {
        return $this->postedAt;
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
            'postedAt' => $this->postedAt->format(DateTimeInterface::ATOM),
            'occurredAt' => $this->occurredAt->format(DateTimeInterface::ATOM),
        ];
    }
}
