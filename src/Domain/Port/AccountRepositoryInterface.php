<?php

declare(strict_types=1);

namespace Dranzd\StorebunkAccounting\Domain\Port;

use Dranzd\StorebunkAccounting\Domain\Accounting\Account\Account;

/**
 * Account Repository Interface
 *
 * Port for persisting and retrieving account entities.
 * Accounts are part of the chart of accounts.
 *
 * @package Dranzd\StorebunkAccounting\Domain\Port
 */
interface AccountRepositoryInterface
{
    /**
     * Save an account
     *
     * @param Account $account The account to save
     * @throws \RuntimeException If persistence fails
     */
    public function save(Account $account): void;

    /**
     * Find an account by ID
     *
     * @param string $id The account ID
     * @return Account|null The account or null if not found
     */
    public function findById(string $id): ?Account;

    /**
     * Check if an account exists
     *
     * @param string $id The account ID
     * @return bool True if exists, false otherwise
     */
    public function exists(string $id): bool;

    /**
     * Get all accounts
     *
     * @return Account[] Array of all accounts
     */
    public function findAll(): array;
}
