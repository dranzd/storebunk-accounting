<?php

declare(strict_types=1);

namespace Dranzd\StorebunkAccounting\Infrastructure\Persistence\EventStore;

use Dranzd\StorebunkAccounting\Domain\Accounting\JournalEntry;
use Dranzd\StorebunkAccounting\Domain\Port\JournalEntryRepositoryInterface;
use RuntimeException;

/**
 * Event-Sourced Journal Entry Repository
 *
 * Persists journal entries using event sourcing.
 * Events are the source of truth - aggregates are reconstituted from events.
 *
 * @package Dranzd\StorebunkAccounting\Infrastructure\Persistence\EventStore
 */
final class EventSourcedJournalEntryRepository implements JournalEntryRepositoryInterface
{
    public function __construct(
        private readonly InMemoryEventStore $eventStore
    ) {
    }

    /**
     * Save a journal entry by persisting its recorded events
     */
    final public function save(JournalEntry $journalEntry): void
    {
        $events = $journalEntry->popRecordedEvents();

        if (empty($events)) {
            return; // Nothing to save
        }

        $streamId = $this->getStreamId($journalEntry->getId());
        $this->eventStore->appendToStream($streamId, $events);
    }

    /**
     * Load a journal entry by reconstituting it from events
     */
    final public function load(string $id): JournalEntry
    {
        $streamId = $this->getStreamId($id);
        $events = $this->eventStore->readStream($streamId);

        if (empty($events)) {
            throw new RuntimeException("Journal entry not found: {$id}");
        }

        // Create a dummy instance to call reconstitute on
        // The trait's reconstitute method creates a new instance internally
        $dummy = JournalEntry::create(
            'temp',
            new \DateTime(),
            'temp',
            [
                \Dranzd\StorebunkAccounting\Domain\Accounting\JournalLine::create(
                    'a',
                    1.0,
                    \Dranzd\StorebunkAccounting\Domain\Accounting\Side::Debit
                ),
                \Dranzd\StorebunkAccounting\Domain\Accounting\JournalLine::create(
                    'b',
                    1.0,
                    \Dranzd\StorebunkAccounting\Domain\Accounting\Side::Credit
                ),
            ]
        );

        /** @var JournalEntry $entry */
        $entry = $dummy->reconstituteFromHistory($events);

        return $entry;
    }

    /**
     * Check if a journal entry exists
     */
    final public function exists(string $id): bool
    {
        $streamId = $this->getStreamId($id);
        return $this->eventStore->streamExists($streamId);
    }

    /**
     * Get the event stream ID for a journal entry
     */
    private function getStreamId(string $id): string
    {
        return "journal-entry-{$id}";
    }
}
