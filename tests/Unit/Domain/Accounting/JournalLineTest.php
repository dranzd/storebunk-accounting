<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Accounting;

use Dranzd\StorebunkAccounting\Domain\Accounting\Journal\JournalLine;
use Dranzd\StorebunkAccounting\Domain\Accounting\Side;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class JournalLineTest extends TestCase
{
    public function test_can_create_debit_line(): void
    {
        $line = JournalLine::create('cash', 100.00, Side::Debit);

        $this->assertEquals('cash', $line->getAccountId());
        $this->assertEquals(100.00, $line->getAmount());
        $this->assertEquals(Side::Debit, $line->getSide());
    }

    public function test_can_create_credit_line(): void
    {
        $line = JournalLine::create('sales', 100.00, Side::Credit);

        $this->assertEquals('sales', $line->getAccountId());
        $this->assertEquals(100.00, $line->getAmount());
        $this->assertEquals(Side::Credit, $line->getSide());
    }

    public function test_cannot_create_line_with_empty_account_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Account ID cannot be empty');

        JournalLine::create('', 100.00, Side::Debit);
    }

    public function test_cannot_create_line_with_zero_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount must be positive');

        JournalLine::create('cash', 0.00, Side::Debit);
    }

    public function test_cannot_create_line_with_negative_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount must be positive');

        JournalLine::create('cash', -100.00, Side::Debit);
    }

    public function test_cannot_create_line_with_more_than_two_decimal_places(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount must have at most 2 decimal places');

        JournalLine::create('cash', 100.123, Side::Debit);
    }

    public function test_can_create_line_with_two_decimal_places(): void
    {
        $line = JournalLine::create('cash', 100.99, Side::Debit);

        $this->assertEquals(100.99, $line->getAmount());
    }

    public function test_lines_with_same_values_are_equal(): void
    {
        $line1 = JournalLine::create('cash', 100.00, Side::Debit);
        $line2 = JournalLine::create('cash', 100.00, Side::Debit);

        $this->assertTrue($line1->equals($line2));
    }

    public function test_lines_with_different_accounts_are_not_equal(): void
    {
        $line1 = JournalLine::create('cash', 100.00, Side::Debit);
        $line2 = JournalLine::create('bank', 100.00, Side::Debit);

        $this->assertFalse($line1->equals($line2));
    }

    public function test_lines_with_different_amounts_are_not_equal(): void
    {
        $line1 = JournalLine::create('cash', 100.00, Side::Debit);
        $line2 = JournalLine::create('cash', 200.00, Side::Debit);

        $this->assertFalse($line1->equals($line2));
    }

    public function test_lines_with_different_sides_are_not_equal(): void
    {
        $line1 = JournalLine::create('cash', 100.00, Side::Debit);
        $line2 = JournalLine::create('cash', 100.00, Side::Credit);

        $this->assertFalse($line1->equals($line2));
    }

    public function test_can_convert_line_to_array(): void
    {
        $line = JournalLine::create('cash', 100.50, Side::Debit);

        $array = $line->toArray();

        $this->assertEquals([
            'accountId' => 'cash',
            'amount' => 100.50,
            'side' => 'debit',
        ], $array);
    }
}
