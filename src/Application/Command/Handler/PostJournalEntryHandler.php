<?php

declare(strict_types=1);

namespace Dranzd\StorebunkAccounting\Application\Command\Handler;

use Dranzd\StorebunkAccounting\Application\Command\PostJournalEntryCommand;
use Dranzd\StorebunkAccounting\Domain\Port\JournalEntryRepositoryInterface;

/**
 * Post Journal Entry Handler
 *
 * Handles the PostJournalEntryCommand by:
 * 1. Loading the journal entry from the event store
 * 2. Calling the post() method
 * 3. Persisting the new events
 *
 * @package Dranzd\StorebunkAccounting\Application\Command\Handler
 */
final class PostJournalEntryHandler
{
    public function __construct(
        private readonly JournalEntryRepositoryInterface $journalEntryRepository
    ) {
    }

    /**
     * Handle the command
     *
     * @throws \RuntimeException If journal entry not found or cannot be posted
     */
    final public function handle(PostJournalEntryCommand $command): void
    {
        // Load aggregate from event store
        $journalEntry = $this->journalEntryRepository->load($command->journalEntryId);

        // Post the entry (records JournalEntryPosted event)
        $journalEntry->post();

        // Persist events
        $this->journalEntryRepository->save($journalEntry);
    }
}
