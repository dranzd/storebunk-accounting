<?php

declare(strict_types=1);

namespace Dranzd\StorebunkAccounting\Infrastructure\Persistence\Projection;

use Dranzd\StorebunkAccounting\Domain\Accounting\Events\JournalEntryPosted;
use Dranzd\StorebunkAccounting\Domain\Accounting\JournalEntry;
use Dranzd\StorebunkAccounting\Domain\Port\JournalEntryRepositoryInterface;
use Dranzd\StorebunkAccounting\Infrastructure\Persistence\ReadModel\InMemoryLedgerReadModel;

/**
 * Ledger Projection
 *
 * Listens to JournalEntryPosted events and updates the ledger read model.
 * Creates posting records for each line in the journal entry.
 *
 * @package Dranzd\StorebunkAccounting\Infrastructure\Persistence\Projection
 */
final class LedgerProjection
{
    public function __construct(
        private readonly InMemoryLedgerReadModel $readModel,
        private readonly JournalEntryRepositoryInterface $journalEntryRepository
    ) {
    }

    /**
     * Handle JournalEntryPosted event
     *
     * Loads the journal entry and creates ledger postings for each line.
     */
    final public function onJournalEntryPosted(JournalEntryPosted $event): void
    {
        // Load the journal entry to get its lines
        $journalEntry = $this->journalEntryRepository->load($event->getJournalEntryId());

        // For MVP, we'll use a default tenant ID
        // In production, this would come from event metadata
        $tenantId = 'default';

        // Create a posting for each line
        foreach ($journalEntry->getLines() as $line) {
            $debit = $line->getSide()->value === 'debit' ? $line->getAmount() : null;
            $credit = $line->getSide()->value === 'credit' ? $line->getAmount() : null;

            $this->readModel->addPosting(
                tenantId: $tenantId,
                accountId: $line->getAccountId(),
                entryId: $journalEntry->getId(),
                date: $journalEntry->getDate(),
                description: $journalEntry->getDescription(),
                debit: $debit,
                credit: $credit
            );
        }
    }
}
