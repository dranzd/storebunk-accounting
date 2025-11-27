<?php

declare(strict_types=1);

namespace Dranzd\StorebunkAccounting\Application\Query\Handler;

use Dranzd\StorebunkAccounting\Application\Query\GetAccountQuery;
use Dranzd\StorebunkAccounting\Domain\Accounting\Account\Account;
use Dranzd\StorebunkAccounting\Domain\Port\AccountRepositoryInterface;

/**
 * Get Account Handler
 *
 * Handles the GetAccountQuery by querying the account repository.
 *
 * @package Dranzd\StorebunkAccounting\Application\Query\Handler
 */
final class GetAccountHandler
{
    public function __construct(
        private readonly AccountRepositoryInterface $accountRepository
    ) {
    }

    /**
     * Handle the query
     *
     * @return Account|null The account or null if not found
     */
    final public function handle(GetAccountQuery $query): ?Account
    {
        return $this->accountRepository->findById($query->accountId);
    }
}
