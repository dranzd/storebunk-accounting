<?php

declare(strict_types=1);

namespace Dranzd\StorebunkAccounting\Application\Command\Handler;

use Dranzd\Common\Cqrs\Application\Command\Handler;
use Dranzd\Common\Cqrs\Domain\Message\Command;
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
final class CreateAccountHandler implements Handler
{
    public function __construct(
        private readonly AccountRepositoryInterface $accountRepository
    ) {
    }

    /**
     * Handle the command
     *
     * @param Command $command The command to handle
     * @throws \InvalidArgumentException If validation fails
     */
    public function handle(Command $command): void
    {
        /** @var CreateAccountCommand $command */
        // Create account entity
        $account = Account::create(
            $command->getAccountId(),
            $command->getName(),
            $command->getType()
        );

        // Persist to repository
        $this->accountRepository->save($account);
    }
}
