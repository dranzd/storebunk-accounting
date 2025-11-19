<?php

declare(strict_types=1);

namespace Dranzd\StorebunkAccounting\Infrastructure\Persistence\Repository;

use Dranzd\StorebunkAccounting\Domain\Accounting\Account;
use Dranzd\StorebunkAccounting\Domain\Port\AccountRepositoryInterface;

/**
 * In-Memory Account Repository
 *
 * Simple in-memory storage for accounts.
 * For MVP - no persistence across restarts.
 *
 * @package Dranzd\StorebunkAccounting\Infrastructure\Persistence\Repository
 */
final class InMemoryAccountRepository implements AccountRepositoryInterface
{
    /**
     * @var array<string, Account>
     */
    private array $accounts = [];

    /**
     * Save an account
     */
    final public function save(Account $account): void
    {
        $this->accounts[$account->getId()] = $account;
    }

    /**
     * Find an account by ID
     */
    final public function findById(string $id): ?Account
    {
        return $this->accounts[$id] ?? null;
    }

    /**
     * Check if an account exists
     */
    final public function exists(string $id): bool
    {
        return isset($this->accounts[$id]);
    }

    /**
     * Get all accounts
     *
     * @return Account[]
     */
    final public function findAll(): array
    {
        return array_values($this->accounts);
    }

    /**
     * Clear all accounts (for testing)
     */
    final public function clear(): void
    {
        $this->accounts = [];
    }
}
