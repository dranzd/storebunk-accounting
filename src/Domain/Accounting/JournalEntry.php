<?php

declare(strict_types=1);

namespace Dranzd\StorebunkAccounting\Domain\Accounting;

use DateTimeImmutable;
use DateTimeInterface;
use Dranzd\StorebunkAccounting\Domain\Accounting\Events\DomainEvent;
use Dranzd\StorebunkAccounting\Domain\Accounting\Events\JournalEntryCreated;
use Dranzd\StorebunkAccounting\Domain\Accounting\Events\JournalEntryPosted;
use InvalidArgumentException;
use RuntimeException;

/**
 * Journal Entry Aggregate Root
 *
 * The heart of the accounting system. A journal entry records a financial transaction
 * using double-entry bookkeeping principles.
 *
 * Invariants:
 * - Must have at least 2 lines
 * - Total debits must equal total credits
 * - All lines must reference existing accounts
 * - Cannot be modified after posting
 *
 * @package Dranzd\StorebunkAccounting\Domain\Accounting
 */
final class JournalEntry
{
    private string $id;
    private DateTimeInterface $date;
    private string $description;
    /** @var JournalLine[] */
    private array $lines = [];
    private EntryStatus $status;
    private ?DateTimeImmutable $postedAt = null;

    /** @var DomainEvent[] */
    private array $uncommittedEvents = [];

    private function __construct()
    {
        // Private constructor - use factory methods
    }

    /**
     * Create a new journal entry in draft state
     *
     * @param string $id Unique identifier
     * @param DateTimeInterface $date Transaction date
     * @param string $description Human-readable description
     * @param JournalLine[] $lines Array of journal lines
     *
     * @throws InvalidArgumentException If validation fails
     */
    final public static function create(
        string $id,
        DateTimeInterface $date,
        string $description,
        array $lines
    ): self {
        $entry = new self();

        // Validate before creating event
        self::validateId($id);
        self::validateDescription($description);
        self::validateLines($lines);
        self::validateBalance($lines);

        // Create event
        $event = new JournalEntryCreated(
            eventId: self::generateEventId(),
            journalEntryId: $id,
            date: $date,
            description: $description,
            lines: array_map(fn(JournalLine $line) => $line->toArray(), $lines),
            occurredAt: new DateTimeImmutable()
        );

        // Apply event to set state
        $entry->applyJournalEntryCreated($event);

        // Record event for persistence
        $entry->recordEvent($event);

        return $entry;
    }

    /**
     * Post the journal entry to the ledger
     *
     * @throws RuntimeException If entry cannot be posted
     */
    final public function post(): void
    {
        if (!$this->status->canPost()) {
            throw new RuntimeException('Journal entry cannot be posted in current status: ' . $this->status->value);
        }

        // Re-validate invariants before posting
        self::validateBalance($this->lines);

        $event = new JournalEntryPosted(
            eventId: self::generateEventId(),
            journalEntryId: $this->id,
            postedAt: new DateTimeImmutable(),
            occurredAt: new DateTimeImmutable()
        );

        $this->applyJournalEntryPosted($event);
        $this->recordEvent($event);
    }

    /**
     * Apply JournalEntryCreated event
     */
    private function applyJournalEntryCreated(JournalEntryCreated $event): void
    {
        $this->id = $event->getJournalEntryId();
        $this->date = $event->getDate();
        $this->description = $event->getDescription();
        $this->status = EntryStatus::Draft;

        // Reconstruct lines from event
        $this->lines = array_map(
            fn(array $lineData) => JournalLine::create(
                $lineData['accountId'],
                $lineData['amount'],
                Side::from($lineData['side'])
            ),
            $event->getLines()
        );
    }

    /**
     * Apply JournalEntryPosted event
     */
    private function applyJournalEntryPosted(JournalEntryPosted $event): void
    {
        $this->status = EntryStatus::Posted;
        $this->postedAt = $event->getPostedAt();
    }

    /**
     * Get the entry ID
     */
    final public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get the transaction date
     */
    final public function getDate(): DateTimeInterface
    {
        return $this->date;
    }

    /**
     * Get the description
     */
    final public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Get the lines
     *
     * @return JournalLine[]
     */
    final public function getLines(): array
    {
        return $this->lines;
    }

    /**
     * Get the status
     */
    final public function getStatus(): EntryStatus
    {
        return $this->status;
    }

    /**
     * Get when the entry was posted
     */
    final public function getPostedAt(): ?DateTimeImmutable
    {
        return $this->postedAt;
    }

    /**
     * Get uncommitted domain events
     *
     * @return DomainEvent[]
     */
    final public function getUncommittedEvents(): array
    {
        return $this->uncommittedEvents;
    }

    /**
     * Clear uncommitted events (after persistence)
     */
    final public function clearUncommittedEvents(): void
    {
        $this->uncommittedEvents = [];
    }

    /**
     * Reconstitute aggregate from events (for event sourcing)
     *
     * @param DomainEvent[] $events
     */
    final public static function reconstitute(array $events): self
    {
        $entry = new self();

        foreach ($events as $event) {
            $entry->apply($event);
        }

        return $entry;
    }

    /**
     * Apply a domain event to the aggregate
     */
    private function apply(DomainEvent $event): void
    {
        match (true) {
            $event instanceof JournalEntryCreated => $this->applyJournalEntryCreated($event),
            $event instanceof JournalEntryPosted => $this->applyJournalEntryPosted($event),
            default => throw new RuntimeException('Unknown event type: ' . get_class($event)),
        };
    }

    /**
     * Record an event for later persistence
     */
    private function recordEvent(DomainEvent $event): void
    {
        $this->uncommittedEvents[] = $event;
    }

    /**
     * Validate entry ID
     */
    private static function validateId(string $id): void
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Journal entry ID cannot be empty');
        }
    }

    /**
     * Validate description
     */
    private static function validateDescription(string $description): void
    {
        if (empty(trim($description))) {
            throw new InvalidArgumentException('Journal entry description cannot be empty');
        }
    }

    /**
     * Validate lines
     *
     * @param JournalLine[] $lines
     */
    private static function validateLines(array $lines): void
    {
        if (count($lines) < 2) {
            throw new InvalidArgumentException('Journal entry must have at least 2 lines');
        }

        foreach ($lines as $line) {
            if (!$line instanceof JournalLine) {
                throw new InvalidArgumentException('All lines must be instances of JournalLine');
            }
        }
    }

    /**
     * Validate that debits equal credits
     *
     * @param JournalLine[] $lines
     */
    private static function validateBalance(array $lines): void
    {
        $debits = 0.0;
        $credits = 0.0;

        foreach ($lines as $line) {
            if ($line->getSide() === Side::Debit) {
                $debits += $line->getAmount();
            } else {
                $credits += $line->getAmount();
            }
        }

        // Use epsilon for float comparison
        if (abs($debits - $credits) > 0.01) {
            throw new InvalidArgumentException(
                sprintf('Journal entry must balance. Debits: %.2f, Credits: %.2f', $debits, $credits)
            );
        }
    }

    /**
     * Generate a unique event ID
     */
    private static function generateEventId(): string
    {
        return 'evt-' . bin2hex(random_bytes(16));
    }
}
