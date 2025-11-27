<?php

declare(strict_types=1);

namespace Dranzd\StorebunkAccounting\Domain\Accounting\Journal\Events;

use DateTimeImmutable;
use Dranzd\Common\EventSourcing\Domain\EventSourcing\AbstractAggregateEvent;

/**
 * Entry Posted Event
 *
 * Emitted when a journal entry is posted to the ledger.
 * This triggers the ledger projection to update account balances.
 *
 * @package Dranzd\StorebunkAccounting\Domain\Accounting\Journal\Events
 */
final class EntryPosted extends AbstractAggregateEvent
{
    private string $entryId;
    private DateTimeImmutable $postedAt;

    private function __construct(
        string $entryId,
        DateTimeImmutable $postedAt
    ) {
        parent::__construct();
        $this->entryId = $entryId;
        $this->postedAt = $postedAt;
    }

    public static function occur(
        string $entryId,
        DateTimeImmutable $postedAt
    ): self {
        return new self($entryId, $postedAt);
    }

    public static function expectedMessageName(): string
    {
        return 'storebunk.accounting.journal_entry.posted';
    }


    public function getEntryId(): string
    {
        return $this->entryId;
    }

    public function getPostedAt(): DateTimeImmutable
    {
        return $this->postedAt;
    }

    public function toPayload(): array
    {
        return [
            'entryId' => $this->entryId,
            'postedAt' => $this->postedAt->format('Y-m-d H:i:s'),
        ];
    }
}
