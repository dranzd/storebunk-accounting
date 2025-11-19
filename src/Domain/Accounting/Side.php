<?php

declare(strict_types=1);

namespace Dranzd\StorebunkAccounting\Domain\Accounting;

/**
 * Side Enum
 *
 * Represents the two sides of a journal entry line: debit or credit.
 * In double-entry bookkeeping, every transaction affects at least two accounts,
 * with total debits equaling total credits.
 *
 * @package Dranzd\StorebunkAccounting\Domain\Accounting
 */
enum Side: string
{
    case Debit = 'debit';
    case Credit = 'credit';

    /**
     * Get the opposite side
     */
    public function opposite(): self
    {
        return match ($this) {
            self::Debit => self::Credit,
            self::Credit => self::Debit,
        };
    }
}
