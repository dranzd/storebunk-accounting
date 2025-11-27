<?php

declare(strict_types=1);

namespace Dranzd\StorebunkAccounting\Application\Query\Handler;

use Dranzd\Common\Cqrs\Application\Query\Handler;
use Dranzd\Common\Cqrs\Application\Query\Result;
use Dranzd\Common\Cqrs\Domain\Message\Query;
use Dranzd\StorebunkAccounting\Application\Query\GetLedgerQuery;
use Dranzd\StorebunkAccounting\Application\Query\QueryResult;
use Dranzd\StorebunkAccounting\Domain\Port\LedgerReadModelInterface;

/**
 * Get Ledger Handler
 *
 * Handles the GetLedgerQuery by querying the ledger read model.
 *
 * @package Dranzd\StorebunkAccounting\Application\Query\Handler
 */
final class GetLedgerHandler implements Handler
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
        /** @var GetLedgerQuery $query */
        $postings = $this->ledgerReadModel->getLedgerPostings(
            $query->getTenantId(),
            $query->getAccountId(),
            $query->getFromDate(),
            $query->getToDate()
        );

        return QueryResult::success($postings);
    }
}
