<?php

declare(strict_types=1);

namespace Dranzd\StorebunkAccounting\Application\Query\Handler;

use Dranzd\Common\Cqrs\Application\Query\Handler;
use Dranzd\Common\Cqrs\Application\Query\Result;
use Dranzd\Common\Cqrs\Domain\Message\Query;
use Dranzd\StorebunkAccounting\Application\Query\GetAllAccountsQuery;
use Dranzd\StorebunkAccounting\Application\Query\QueryResult;
use Dranzd\StorebunkAccounting\Domain\Port\AccountRepositoryInterface;

/**
 * Get All Accounts Handler
 *
 * Handles the GetAllAccountsQuery by querying the account repository.
 *
 * @package Dranzd\StorebunkAccounting\Application\Query\Handler
 */
final class GetAllAccountsHandler implements Handler
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
        /** @var GetAllAccountsQuery $query */
        $accounts = $this->accountRepository->findAll();

        return QueryResult::success($accounts);
    }
}
