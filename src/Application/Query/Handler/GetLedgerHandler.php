<?php

declare(strict_types=1);

namespace Dranzd\StorebunkAccounting\Application\Query\Handler;

use Dranzd\StorebunkAccounting\Application\Query\GetLedgerQuery;
use Dranzd\StorebunkAccounting\Domain\Port\LedgerReadModelInterface;

/**
 * Get Ledger Handler
 *
 * Handles the GetLedgerQuery by querying the ledger read model.
 *
 * @package Dranzd\StorebunkAccounting\Application\Query\Handler
 */
final class GetLedgerHandler
{
    public function __construct(
        private readonly LedgerReadModelInterface $ledgerReadModel
    ) {
    }

    /**
     * Handle the query
     *
     * @return array Array of ledger postings
     */
    final public function handle(GetLedgerQuery $query): array
    {
        return $this->ledgerReadModel->getLedgerPostings(
            $query->tenantId,
            $query->accountId,
            $query->fromDate,
            $query->toDate
        );
    }
}
