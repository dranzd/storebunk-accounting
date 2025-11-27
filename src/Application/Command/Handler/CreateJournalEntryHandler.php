<?php

declare(strict_types=1);

namespace Dranzd\StorebunkAccounting\Application\Command\Handler;

use Dranzd\Common\Cqrs\Application\Command\Handler;
use Dranzd\Common\Cqrs\Domain\Message\Command;
use Dranzd\StorebunkAccounting\Application\Command\CreateJournalEntryCommand;
use Dranzd\StorebunkAccounting\Domain\Accounting\Journal\Entry;
use Dranzd\StorebunkAccounting\Domain\Accounting\Journal\Line;
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
final class CreateJournalEntryHandler implements Handler
{
    public function __construct(
        private readonly JournalEntryRepositoryInterface $journalEntryRepository,
        private readonly AccountRepositoryInterface $accountRepository
    ) {
    }

    /**
     * Handle the command
     *
     * @param Command $command The command to handle
     * @throws InvalidArgumentException If validation fails
     */
    public function handle(Command $command): void
    {
        assert($command instanceof CreateJournalEntryCommand);

        // Validate all account IDs exist
        foreach ($command->getLines() as $lineData) {
            if (!$this->accountRepository->exists($lineData['accountId'])) {
                throw new InvalidArgumentException(
                    "Account not found: {$lineData['accountId']}"
                );
            }
        }

        // Create journal lines
        $lines = array_map(
            fn(array $lineData) => Line::create(
                $lineData['accountId'],
                $lineData['amount'],
                Side::from($lineData['side'])
            ),
            $command->getLines()
        );

        // Create journal entry aggregate
        $journalEntry = Entry::create(
            $command->getEntryId(),
            $command->getDate(),
            $command->getDescription(),
            $lines
        );

        // Persist events
        $this->journalEntryRepository->save($journalEntry);
    }
}
