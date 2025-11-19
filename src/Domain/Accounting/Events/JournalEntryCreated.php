<?php

declare(strict_types=1);

namespace Dranzd\StorebunkAccounting\Domain\Accounting\Events;

use DateTimeInterface;
use Dranzd\Common\EventSourcing\Domain\EventSourcing\AbstractAggregateEvent;

/**
 * Journal Entry Created Event
 *
 * Emitted when a new journal entry is created in draft state.
 *
 * @package Dranzd\StorebunkAccounting\Domain\Accounting\Events
 */
final class JournalEntryCreated extends AbstractAggregateEvent
{
    private string $journalEntryId;
    private DateTimeInterface $date;
    private string $description;
    private array $lines;

    private function __construct(
        string $journalEntryId,
        DateTimeInterface $date,
        string $description,
        array $lines
    ) {
        parent::__construct();
        $this->journalEntryId = $journalEntryId;
        $this->date = $date;
        $this->description = $description;
        $this->lines = $lines;
    }

    public static function occur(
        string $journalEntryId,
        DateTimeInterface $date,
        string $description,
        array $lines
    ): self {
        return new self($journalEntryId, $date, $description, $lines);
    }

    public static function expectedMessageName(): string
    {
        return 'storebunk.accounting.journal_entry.created';
    }


    public function getJournalEntryId(): string
    {
        return $this->journalEntryId;
    }

    public function getDate(): DateTimeInterface
    {
        return $this->date;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getLines(): array
    {
        return $this->lines;
    }

    public function toPayload(): array
    {
        return [
            'journalEntryId' => $this->journalEntryId,
            'date' => $this->date->format('Y-m-d H:i:s'),
            'description' => $this->description,
            'lines' => $this->lines,
        ];
    }
}
