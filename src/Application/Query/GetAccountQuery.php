<?php

declare(strict_types=1);

namespace Dranzd\StorebunkAccounting\Application\Query;

/**
 * Get Account Query
 *
 * Query to retrieve an account from the chart of accounts.
 *
 * @package Dranzd\StorebunkAccounting\Application\Query
 */
final readonly class GetAccountQuery
{
    /**
     * @param string $accountId The account ID
     */
    public function __construct(
        public string $accountId
    ) {
    }
}
