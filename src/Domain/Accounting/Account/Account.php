<?php

declare(strict_types=1);

namespace Dranzd\StorebunkAccounting\Domain\Accounting\Account;

use InvalidArgumentException;

/**
 * Account Entity
 *
 * Represents a single account in the chart of accounts.
 * Accounts are the fundamental building blocks where all transactions are recorded.
 *
 * @package Dranzd\StorebunkAccounting\Domain\Accounting\Account
 */
final class Account
{
    /**
     * @param string $id Unique identifier for the account
     * @param string $name Human-readable account name
     * @param AccountType $type The type of account (Asset, Liability, etc.)
     */
    private function __construct(
        private readonly string $id,
        private string $name,
        private readonly AccountType $type
    ) {
        $this->validate();
    }

    /**
     * Create a new account
     *
     * @throws InvalidArgumentException If validation fails
     */
    final public static function create(string $id, string $name, AccountType $type): self
    {
        return new self($id, $name, $type);
    }

    /**
     * Get the account ID
     */
    final public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get the account name
     */
    final public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the account type
     */
    final public function getType(): AccountType
    {
        return $this->type;
    }

    /**
     * Update the account name
     *
     * @throws InvalidArgumentException If name is empty
     */
    final public function updateName(string $name): void
    {
        if (empty(trim($name))) {
            throw new InvalidArgumentException('Account name cannot be empty');
        }

        $this->name = $name;
    }

    /**
     * Validate the account
     *
     * @throws InvalidArgumentException
     */
    private function validate(): void
    {
        if (empty($this->id)) {
            throw new InvalidArgumentException('Account ID cannot be empty');
        }

        if (empty(trim($this->name))) {
            throw new InvalidArgumentException('Account name cannot be empty');
        }
    }

    /**
     * Check equality with another account
     */
    final public function equals(self $other): bool
    {
        return $this->id === $other->id;
    }

    /**
     * Convert to array representation
     */
    final public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type->value,
        ];
    }
}
