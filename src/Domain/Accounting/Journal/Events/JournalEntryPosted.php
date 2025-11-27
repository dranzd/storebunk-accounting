<?php

declare(strict_types=1);

namespace Dranzd\StorebunkAccounting\Domain\Accounting\Journal\Events;

use DateTimeImmutable;
use Dranzd\Common\EventSourcing\Domain\EventSourcing\AbstractAggregateEvent;

/**
 * Journal Entry Posted Event
 *
 * Emitted when a journal entry is posted to the ledger.
 * This triggers the ledger projection to update account balances.
 *
 * @package Dranzd\StorebunkAccounting\Domain\Accounting\Journal\Events
 */
final class JournalEntryPosted extends AbstractAggregateEvent
{
    private string $journalEntryId;
    private DateTimeImmutable $postedAt;

    private function __construct(
        string $journalEntryId,
        DateTimeImmutable $postedAt
    ) {
        parent::__construct();
        $this->journalEntryId = $journalEntryId;
        $this->postedAt = $postedAt;
    }

    public static function occur(
        string $journalEntryId,
        DateTimeImmutable $postedAt
    ): self {
        return new self($journalEntryId, $postedAt);
    }

    public static function expectedMessageName(): string
    {
        return 'storebunk.accounting.journal_entry.posted';
    }


    public function getJournalEntryId(): string
    {
        return $this->journalEntryId;
    }

    public function getPostedAt(): DateTimeImmutable
    {
        return $this->postedAt;
    }

    public function toPayload(): array
    {
        return [
            'journalEntryId' => $this->journalEntryId,
            'postedAt' => $this->postedAt->format('Y-m-d H:i:s'),
        ];
    }
}
