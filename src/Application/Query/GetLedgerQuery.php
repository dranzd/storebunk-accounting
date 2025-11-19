<?php

declare(strict_types=1);

namespace Dranzd\StorebunkAccounting\Application\Query;

use DateTimeInterface;

/**
 * Get Ledger Query
 *
 * Query to retrieve ledger postings for an account.
 *
 * @package Dranzd\StorebunkAccounting\Application\Query
 */
final readonly class GetLedgerQuery
{
    /**
     * @param string $tenantId The tenant ID
     * @param string $accountId The account ID
     * @param DateTimeInterface|null $fromDate Optional start date
     * @param DateTimeInterface|null $toDate Optional end date
     */
    public function __construct(
        public string $tenantId,
        public string $accountId,
        public ?DateTimeInterface $fromDate = null,
        public ?DateTimeInterface $toDate = null
    ) {
    }
}
