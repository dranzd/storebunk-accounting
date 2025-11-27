<?php

declare(strict_types=1);

namespace Dranzd\StorebunkAccounting\Application\Query\Handler;

use Dranzd\Common\Cqrs\Application\Query\Handler;
use Dranzd\Common\Cqrs\Application\Query\Result;
use Dranzd\Common\Cqrs\Domain\Message\Query;
use Dranzd\StorebunkAccounting\Application\Query\GetAccountBalanceQuery;
use Dranzd\StorebunkAccounting\Application\Query\QueryResult;
use Dranzd\StorebunkAccounting\Domain\Port\LedgerReadModelInterface;

/**
 * Get Account Balance Handler
 *
 * Handles the GetAccountBalanceQuery by querying the ledger read model.
 *
 * @package Dranzd\StorebunkAccounting\Application\Query\Handler
 */
final class GetAccountBalanceHandler implements Handler
{
    public function __construct(
        private readonly LedgerReadModelInterface $ledgerReadModel
    ) {
    }

    /**
     * Handle the query
     *
     * @param Query $query The query to handle
     * @return Result The query result
     */
    public function handle(Query $query): Result
    {
        /** @var GetAccountBalanceQuery $query */
        $balance = $this->ledgerReadModel->getAccountBalance(
            $query->getTenantId(),
            $query->getAccountId()
        );

        return QueryResult::success($balance);
    }
}
