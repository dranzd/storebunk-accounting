<?php

declare(strict_types=1);

namespace Dranzd\StorebunkAccounting\Application\Query\Handler;

use Dranzd\StorebunkAccounting\Application\Query\GetAllAccountsQuery;
use Dranzd\StorebunkAccounting\Domain\Port\AccountRepositoryInterface;

/**
 * Get All Accounts Handler
 *
 * Handles the GetAllAccountsQuery by querying the account repository.
 *
 * @package Dranzd\StorebunkAccounting\Application\Query\Handler
 */
final class GetAllAccountsHandler
{
    public function __construct(
        private readonly AccountRepositoryInterface $accountRepository
    ) {
    }

    /**
     * Handle the query
     *
     * @return array Array of accounts
     */
    final public function handle(GetAllAccountsQuery $query): array
    {
        return $this->accountRepository->findAll();
    }
}
