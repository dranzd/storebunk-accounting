<?php

declare(strict_types=1);

namespace Dranzd\StorebunkAccounting\Application\Command;

use Dranzd\StorebunkAccounting\Domain\Accounting\Account\AccountType;

/**
 * Create Account Command
 *
 * Command to create a new account in the chart of accounts.
 *
 * @package Dranzd\StorebunkAccounting\Application\Command
 */
final readonly class CreateAccountCommand
{
    /**
     * @param string $id Unique identifier for the account
     * @param string $name Human-readable account name
     * @param AccountType $type The type of account
     */
    public function __construct(
        public string $id,
        public string $name,
        public AccountType $type
    ) {
    }
}
