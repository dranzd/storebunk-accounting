<?php

declare(strict_types=1);

namespace Dranzd\StorebunkAccounting\Domain\Accounting\Journal\Events;

use DateTimeInterface;
use Dranzd\Common\EventSourcing\Domain\EventSourcing\AbstractAggregateEvent;

/**
 * Entry Created Event
 *
 * Emitted when a new journal entry is created in draft state.
 *
 * @package Dranzd\StorebunkAccounting\Domain\Accounting\Journal\Events
 */
final class EntryCreated extends AbstractAggregateEvent
{
    private string $entryId;
    private DateTimeInterface $date;
    private string $description;
    private array $lines;

    private function __construct(
        string $entryId,
        DateTimeInterface $date,
        string $description,
        array $lines
    ) {
        parent::__construct();
        $this->entryId = $entryId;
        $this->date = $date;
        $this->description = $description;
        $this->lines = $lines;
    }

    public static function occur(
        string $entryId,
        DateTimeInterface $date,
        string $description,
        array $lines
    ): self {
        return new self($entryId, $date, $description, $lines);
    }

    public static function expectedMessageName(): string
    {
        return 'storebunk.accounting.journal_entry.created';
    }


    public function getEntryId(): string
    {
        return $this->entryId;
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
            'entryId' => $this->entryId,
            'date' => $this->date->format('Y-m-d H:i:s'),
            'description' => $this->description,
            'lines' => $this->lines,
        ];
    }
}
