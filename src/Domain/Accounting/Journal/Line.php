<?php

declare(strict_types=1);

namespace Dranzd\StorebunkAccounting\Domain\Accounting\Journal;

use Dranzd\StorebunkAccounting\Domain\Accounting\Side;
use InvalidArgumentException;

/**
 * Line Value Object
 *
 * Represents a single line in a journal entry.
 * Each line affects one account with either a debit or credit.
 *
 * @package Dranzd\StorebunkAccounting\Domain\Accounting\Journal
 */
final readonly class Line
{
    /**
     * @param string $accountId Reference to the account being affected
     * @param float $amount Transaction amount (always positive)
     * @param Side $side Whether this is a debit or credit
     */
    private function __construct(
        private readonly string $accountId,
        private readonly float $amount,
        private readonly Side $side
    ) {
        $this->validate();
    }

    /**
     * Create a new journal line
     *
     * @throws InvalidArgumentException If validation fails
     */
    public static function create(string $accountId, float $amount, Side $side): self
    {
        return new self($accountId, $amount, $side);
    }

    /**
     * Get the account ID
     */
    final public function getAccountId(): string
    {
        return $this->accountId;
    }

    /**
     * Get the amount
     */
    final public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * Get the side (debit or credit)
     */
    final public function getSide(): Side
    {
        return $this->side;
    }

    /**
     * Check equality with another journal line
     */
    final public function equals(self $other): bool
    {
        return $this->accountId === $other->accountId
            && abs($this->amount - $other->amount) < 0.01 // Float comparison with epsilon
            && $this->side === $other->side;
    }

    /**
     * Convert to array representation
     */
    final public function toArray(): array
    {
        return [
            'accountId' => $this->accountId,
            'amount' => $this->amount,
            'side' => $this->side->value,
        ];
    }

    /**
     * Validate the journal line
     *
     * @throws InvalidArgumentException
     */
    private function validate(): void
    {
        if (empty($this->accountId)) {
            throw new InvalidArgumentException('Account ID cannot be empty');
        }

        if ($this->amount <= 0) {
            throw new InvalidArgumentException('Amount must be positive');
        }

        // Ensure amount has reasonable precision (2 decimal places for currency)
        if (round($this->amount, 2) !== $this->amount) {
            throw new InvalidArgumentException('Amount must have at most 2 decimal places');
        }
    }
}
