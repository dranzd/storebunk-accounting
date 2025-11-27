<?php

declare(strict_types=1);

namespace Dranzd\StorebunkAccounting\Domain\Accounting\Account;

use Dranzd\StorebunkAccounting\Domain\Accounting\Side;

/**
 * Type Enum
 *
 * Represents the five fundamental account types in double-entry bookkeeping.
 *
 * @package Dranzd\StorebunkAccounting\Domain\Accounting\Account
 */
enum Type: string
{
    case Asset = 'asset';
    case Liability = 'liability';
    case Equity = 'equity';
    case Revenue = 'revenue';
    case Expense = 'expense';

    /**
     * Get the normal balance side for this account type
     *
     * Assets and Expenses normally have debit balances.
     * Liabilities, Equity, and Revenue normally have credit balances.
     */
    public function normalBalanceSide(): Side
    {
        return match ($this) {
            self::Asset, self::Expense => Side::Debit,
            self::Liability, self::Equity, self::Revenue => Side::Credit,
        };
    }

    /**
     * Check if this is a balance sheet account
     */
    public function isBalanceSheet(): bool
    {
        return match ($this) {
            self::Asset, self::Liability, self::Equity => true,
            self::Revenue, self::Expense => false,
        };
    }

    /**
     * Check if this is an income statement account
     */
    public function isIncomeStatement(): bool
    {
        return match ($this) {
            self::Revenue, self::Expense => true,
            self::Asset, self::Liability, self::Equity => false,
        };
    }
}
