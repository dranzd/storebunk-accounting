<?php

declare(strict_types=1);

namespace Dranzd\StorebunkAccounting\Domain\Accounting\Journal;

/**
 * Entry Status Enum
 *
 * Represents the lifecycle status of a journal entry.
 *
 * @package Dranzd\StorebunkAccounting\Domain\Accounting\Journal
 */
enum EntryStatus: string
{
    case Draft = 'draft';
    case Posted = 'posted';

    /**
     * Check if the entry can be modified
     */
    public function canModify(): bool
    {
        return $this === self::Draft;
    }

    /**
     * Check if the entry can be posted
     */
    public function canPost(): bool
    {
        return $this === self::Draft;
    }
}
