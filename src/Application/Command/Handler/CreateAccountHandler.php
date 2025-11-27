<?php

declare(strict_types=1);

namespace Dranzd\StorebunkAccounting\Application\Command\Handler;

use Dranzd\StorebunkAccounting\Application\Command\CreateAccountCommand;
use Dranzd\StorebunkAccounting\Domain\Accounting\Account\Account;
use Dranzd\StorebunkAccounting\Domain\Port\AccountRepositoryInterface;

/**
 * Create Account Handler
 *
 * Handles the CreateAccountCommand by:
 * 1. Creating the account entity
 * 2. Persisting to the repository
 *
 * @package Dranzd\StorebunkAccounting\Application\Command\Handler
 */
final class CreateAccountHandler
{
    public function __construct(
        private readonly AccountRepositoryInterface $accountRepository
    ) {
    }

    /**
     * Handle the command
     *
     * @throws \InvalidArgumentException If validation fails
     */
    final public function handle(CreateAccountCommand $command): void
    {
        // Create account entity
        $account = Account::create(
            $command->id,
            $command->name,
            $command->type
        );

        // Persist to repository
        $this->accountRepository->save($account);
    }
}
