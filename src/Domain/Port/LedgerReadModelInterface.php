<?php

declare(strict_types=1);

namespace Dranzd\StorebunkAccounting\Domain\Port;

/**
 * Ledger Read Model Interface
 *
 * Port for querying ledger postings and account balances.
 * This is the read side of CQRS - projections update this model.
 *
 * @package Dranzd\StorebunkAccounting\Domain\Port
 */
interface LedgerReadModelInterface
{
    /**
     * Get account balance
     *
     * @param string $tenantId The tenant ID
     * @param string $accountId The account ID
     * @return float The current balance
     */
    public function getAccountBalance(string $tenantId, string $accountId): float;

    /**
     * Get ledger postings for an account
     *
     * @param string $tenantId The tenant ID
     * @param string $accountId The account ID
     * @param \DateTimeInterface|null $fromDate Optional start date
     * @param \DateTimeInterface|null $toDate Optional end date
     * @return array Array of posting data
     */
    public function getLedgerPostings(
        string $tenantId,
        string $accountId,
        ?\DateTimeInterface $fromDate = null,
        ?\DateTimeInterface $toDate = null
    ): array;

    /**
     * Get all account balances for a tenant
     *
     * @param string $tenantId The tenant ID
     * @return array Array of account balances [accountId => balance]
     */
    public function getAllAccountBalances(string $tenantId): array;
}
