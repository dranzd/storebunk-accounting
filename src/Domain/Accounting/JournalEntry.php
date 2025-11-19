<?php

declare(strict_types=1);

namespace Dranzd\StorebunkAccounting\Domain\Accounting;

use DateTimeImmutable;
use DateTimeInterface;
use Dranzd\Common\EventSourcing\Domain\EventSourcing\AggregateRoot;
use Dranzd\Common\EventSourcing\Domain\EventSourcing\AggregateRootTrait;
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
final class JournalEntry implements AggregateRoot
{
    use AggregateRootTrait;

    private string $id;
    private DateTimeInterface $date;
    private string $description;
    /** @var JournalLine[] */
    private array $lines = [];
    private EntryStatus $status;
    private ?DateTimeImmutable $postedAt = null;

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
        $entry->id = $id; // Set ID before recording events

        // Validate before creating event
        self::validateId($id);
        self::validateDescription($description);
        self::validateLines($lines);
        self::validateBalance($lines);

        // Record event using trait's recordThat method
        $entry->recordThat(
            JournalEntryCreated::occur(
                $id,
                $date,
                $description,
                array_map(fn(JournalLine $line) => $line->toArray(), $lines)
            )
        );

        return $entry;
    }

    /**
     * Get the aggregate root UUID (uses ID as UUID)
     */
    final public function getAggregateRootUuid(): string
    {
        return $this->id;
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

        // Record event using trait's recordThat method
        $this->recordThat(
            JournalEntryPosted::occur(
                $this->id,
                new DateTimeImmutable()
            )
        );
    }

    /**
     * Apply JournalEntryCreated event
     * Called automatically by AggregateRootTrait when event is recorded
     *
     * @phpstan-ignore-next-line Method is called dynamically by AggregateRootTrait
     */
    private function applyOnJournalEntryCreated(JournalEntryCreated $event): void
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
     * Called automatically by AggregateRootTrait when event is recorded
     *
     * @phpstan-ignore-next-line Method is called dynamically by AggregateRootTrait
     */
    private function applyOnJournalEntryPosted(JournalEntryPosted $event): void
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

    // Note: getRecordedEvents(), popRecordedEvents(), and reconstitute()
    // are provided by AggregateRootTrait

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
}
