<?php

declare(strict_types=1);

namespace Dranzd\StorebunkAccounting\Application\Query\Handler;

use Dranzd\Common\Cqrs\Application\Query\Handler;
use Dranzd\Common\Cqrs\Application\Query\Result;
use Dranzd\Common\Cqrs\Domain\Message\Query;
use Dranzd\StorebunkAccounting\Application\Query\GetAccountQuery;
use Dranzd\StorebunkAccounting\Application\Query\QueryResult;
use Dranzd\StorebunkAccounting\Domain\Port\AccountRepositoryInterface;

/**
 * Get Account Handler
 *
 * Handles the GetAccountQuery by querying the account repository.
 *
 * @package Dranzd\StorebunkAccounting\Application\Query\Handler
 */
final class GetAccountHandler implements Handler
{
    public function __construct(
        private readonly AccountRepositoryInterface $accountRepository
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
        /** @var GetAccountQuery $query */
        $account = $this->accountRepository->findById($query->getAccountId());

        return QueryResult::success($account);
    }
}
