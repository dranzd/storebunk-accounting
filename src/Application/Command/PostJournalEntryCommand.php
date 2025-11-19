<?php

declare(strict_types=1);

namespace Dranzd\StorebunkAccounting\Application\Command;

/**
 * Post Journal Entry Command
 *
 * Command to post a journal entry to the ledger.
 *
 * @package Dranzd\StorebunkAccounting\Application\Command
 */
final readonly class PostJournalEntryCommand
{
    /**
     * @param string $journalEntryId The ID of the journal entry to post
     */
    public function __construct(
        public string $journalEntryId
    ) {
    }
}
