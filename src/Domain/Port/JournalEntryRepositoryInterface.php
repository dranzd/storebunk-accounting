<?php

declare(strict_types=1);

namespace Dranzd\StorebunkAccounting\Domain\Port;

use Dranzd\StorebunkAccounting\Domain\Accounting\JournalEntry;

/**
 * Journal Entry Repository Interface
 *
 * Port for persisting and retrieving journal entry aggregates.
 * Implementations should use event sourcing to store and reconstitute aggregates.
 *
 * @package Dranzd\StorebunkAccounting\Domain\Port
 */
interface JournalEntryRepositoryInterface
{
    /**
     * Save a journal entry aggregate
     *
     * Persists all recorded events from the aggregate to the event store.
     *
     * @param JournalEntry $journalEntry The aggregate to save
     * @throws \RuntimeException If persistence fails
     */
    public function save(JournalEntry $journalEntry): void;

    /**
     * Load a journal entry aggregate by ID
     *
     * Reconstitutes the aggregate from its event history.
     *
     * @param string $id The journal entry ID
     * @return JournalEntry The reconstituted aggregate
     * @throws \RuntimeException If aggregate not found or reconstitution fails
     */
    public function load(string $id): JournalEntry;

    /**
     * Check if a journal entry exists
     *
     * @param string $id The journal entry ID
     * @return bool True if exists, false otherwise
     */
    public function exists(string $id): bool;
}
