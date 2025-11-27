<?php

declare(strict_types=1);

namespace Dranzd\StorebunkAccounting\Application\Command\Handler;

use Dranzd\StorebunkAccounting\Application\Command\CreateJournalEntryCommand;
use Dranzd\StorebunkAccounting\Domain\Accounting\Journal\JournalEntry;
use Dranzd\StorebunkAccounting\Domain\Accounting\Journal\JournalLine;
use Dranzd\StorebunkAccounting\Domain\Accounting\Side;
use Dranzd\StorebunkAccounting\Domain\Port\AccountRepositoryInterface;
use Dranzd\StorebunkAccounting\Domain\Port\JournalEntryRepositoryInterface;
use InvalidArgumentException;

/**
 * Create Journal Entry Handler
 *
 * Handles the CreateJournalEntryCommand by:
 * 1. Validating all account IDs exist
 * 2. Creating the journal entry aggregate
 * 3. Persisting events to the event store
 *
 * @package Dranzd\StorebunkAccounting\Application\Command\Handler
 */
final class CreateJournalEntryHandler
{
    public function __construct(
        private readonly JournalEntryRepositoryInterface $journalEntryRepository,
        private readonly AccountRepositoryInterface $accountRepository
    ) {
    }

    /**
     * Handle the command
     *
     * @throws InvalidArgumentException If validation fails
     */
    final public function handle(CreateJournalEntryCommand $command): void
    {
        // Validate all account IDs exist
        foreach ($command->lines as $lineData) {
            if (!$this->accountRepository->exists($lineData['accountId'])) {
                throw new InvalidArgumentException(
                    "Account not found: {$lineData['accountId']}"
                );
            }
        }

        // Create journal lines
        $lines = array_map(
            fn(array $lineData) => JournalLine::create(
                $lineData['accountId'],
                $lineData['amount'],
                Side::from($lineData['side'])
            ),
            $command->lines
        );

        // Create journal entry aggregate
        $journalEntry = JournalEntry::create(
            $command->id,
            $command->date,
            $command->description,
            $lines
        );

        // Persist events
        $this->journalEntryRepository->save($journalEntry);
    }
}
