<?php

declare(strict_types=1);

namespace Dranzd\StorebunkAccounting\Application\Command\Handler;

use Dranzd\Common\Cqrs\Application\Command\Handler;
use Dranzd\Common\Cqrs\Domain\Message\Command;
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
final class PostJournalEntryHandler implements Handler
{
    public function __construct(
        private readonly JournalEntryRepositoryInterface $journalEntryRepository
    ) {
    }

    /**
     * Handle the command
     *
     * @param Command $command The command to handle
     * @throws \RuntimeException If journal entry not found or cannot be posted
     */
    public function handle(Command $command): void
    {
        /** @var PostJournalEntryCommand $command */
        // Load aggregate from event store
        $entry = $this->journalEntryRepository->load($command->getEntryId());

        // Post the entry (records EntryPosted event)
        $entry->post();

        // Persist events
        $this->journalEntryRepository->save($entry);
    }
}
