<?php

declare(strict_types=1);

namespace Dranzd\StorebunkAccounting\Application\Query;

/**
 * Get Account Balance Query
 *
 * Query to retrieve the current balance of an account.
 *
 * @package Dranzd\StorebunkAccounting\Application\Query
 */
final readonly class GetAccountBalanceQuery
{
    /**
     * @param string $tenantId The tenant ID
     * @param string $accountId The account ID
     */
    public function __construct(
        public string $tenantId,
        public string $accountId
    ) {
    }
}
