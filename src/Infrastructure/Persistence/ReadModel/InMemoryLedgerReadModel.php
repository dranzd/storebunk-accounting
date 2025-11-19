<?php

declare(strict_types=1);

namespace Dranzd\StorebunkAccounting\Infrastructure\Persistence\ReadModel;

use DateTimeInterface;
use Dranzd\StorebunkAccounting\Domain\Port\LedgerReadModelInterface;

/**
 * In-Memory Ledger Read Model
 *
 * Simple in-memory storage for ledger postings and account balances.
 * Updated by projections when events are processed.
 *
 * @package Dranzd\StorebunkAccounting\Infrastructure\Persistence\ReadModel
 */
final class InMemoryLedgerReadModel implements LedgerReadModelInterface
{
    /**
     * @var array<string, array<string, float>> [tenantId][accountId] => balance
     */
    private array $balances = [];

    /**
     * @var array<string, array<int, array>> [tenantId] => array of postings
     */
    private array $postings = [];

    /**
     * Add a ledger posting
     *
     * @param string $tenantId
     * @param string $accountId
     * @param string $entryId
     * @param DateTimeInterface $date
     * @param string $description
     * @param float|null $debit
     * @param float|null $credit
     */
    final public function addPosting(
        string $tenantId,
        string $accountId,
        string $entryId,
        DateTimeInterface $date,
        string $description,
        ?float $debit,
        ?float $credit
    ): void {
        if (!isset($this->postings[$tenantId])) {
            $this->postings[$tenantId] = [];
        }

        // Update balance
        $currentBalance = $this->getAccountBalance($tenantId, $accountId);
        $newBalance = $currentBalance + ($debit ?? 0.0) - ($credit ?? 0.0);

        $this->setAccountBalance($tenantId, $accountId, $newBalance);

        // Add posting
        $this->postings[$tenantId][] = [
            'accountId' => $accountId,
            'entryId' => $entryId,
            'date' => $date,
            'description' => $description,
            'debit' => $debit,
            'credit' => $credit,
            'balance' => $newBalance,
        ];
    }

    /**
     * Set account balance
     */
    final public function setAccountBalance(string $tenantId, string $accountId, float $balance): void
    {
        if (!isset($this->balances[$tenantId])) {
            $this->balances[$tenantId] = [];
        }

        $this->balances[$tenantId][$accountId] = $balance;
    }

    /**
     * Get account balance
     */
    final public function getAccountBalance(string $tenantId, string $accountId): float
    {
        return $this->balances[$tenantId][$accountId] ?? 0.0;
    }

    /**
     * Get ledger postings for an account
     */
    final public function getLedgerPostings(
        string $tenantId,
        string $accountId,
        ?DateTimeInterface $fromDate = null,
        ?DateTimeInterface $toDate = null
    ): array {
        if (!isset($this->postings[$tenantId])) {
            return [];
        }

        $filtered = array_filter(
            $this->postings[$tenantId],
            function (array $posting) use ($accountId, $fromDate, $toDate) {
                if ($posting['accountId'] !== $accountId) {
                    return false;
                }

                if ($fromDate && $posting['date'] < $fromDate) {
                    return false;
                }

                if ($toDate && $posting['date'] > $toDate) {
                    return false;
                }

                return true;
            }
        );

        return array_values($filtered);
    }

    /**
     * Get all account balances for a tenant
     */
    final public function getAllAccountBalances(string $tenantId): array
    {
        return $this->balances[$tenantId] ?? [];
    }

    /**
     * Clear all data (for testing)
     */
    final public function clear(): void
    {
        $this->balances = [];
        $this->postings = [];
    }
}
