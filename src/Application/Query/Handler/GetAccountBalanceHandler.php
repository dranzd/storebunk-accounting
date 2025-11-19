<?php

declare(strict_types=1);

namespace Dranzd\StorebunkAccounting\Application\Query\Handler;

use Dranzd\StorebunkAccounting\Application\Query\GetAccountBalanceQuery;
use Dranzd\StorebunkAccounting\Domain\Port\LedgerReadModelInterface;

/**
 * Get Account Balance Handler
 *
 * Handles the GetAccountBalanceQuery by querying the ledger read model.
 *
 * @package Dranzd\StorebunkAccounting\Application\Query\Handler
 */
final class GetAccountBalanceHandler
{
    public function __construct(
        private readonly LedgerReadModelInterface $ledgerReadModel
    ) {
    }

    /**
     * Handle the query
     *
     * @return float The account balance
     */
    final public function handle(GetAccountBalanceQuery $query): float
    {
        return $this->ledgerReadModel->getAccountBalance(
            $query->tenantId,
            $query->accountId
        );
    }
}
