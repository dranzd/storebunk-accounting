<?php

declare(strict_types=1);

namespace Dranzd\StorebunkAccounting\Application\Command;

use DateTimeInterface;

/**
 * Create Journal Entry Command
 *
 * Command to create a new journal entry in draft state.
 *
 * @package Dranzd\StorebunkAccounting\Application\Command
 */
final readonly class CreateJournalEntryCommand
{
    /**
     * @param string $id Unique identifier for the journal entry
     * @param DateTimeInterface $date Transaction date
     * @param string $description Human-readable description
     * @param array<int, array{accountId: string, amount: float, side: string}> $lines Journal lines
     */
    public function __construct(
        public string $id,
        public DateTimeInterface $date,
        public string $description,
        public array $lines
    ) {
    }
}
