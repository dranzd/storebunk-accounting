<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Accounting;

use Dranzd\StorebunkAccounting\Domain\Accounting\Account;
use Dranzd\StorebunkAccounting\Domain\Accounting\AccountType;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class AccountTest extends TestCase
{
    public function test_can_create_account(): void
    {
        $account = Account::create('cash', 'Cash', AccountType::Asset);

        $this->assertEquals('cash', $account->getId());
        $this->assertEquals('Cash', $account->getName());
        $this->assertEquals(AccountType::Asset, $account->getType());
    }

    public function test_cannot_create_account_with_empty_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Account ID cannot be empty');

        Account::create('', 'Cash', AccountType::Asset);
    }

    public function test_cannot_create_account_with_empty_name(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Account name cannot be empty');

        Account::create('cash', '', AccountType::Asset);
    }

    public function test_cannot_create_account_with_whitespace_only_name(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Account name cannot be empty');

        Account::create('cash', '   ', AccountType::Asset);
    }

    public function test_can_update_account_name(): void
    {
        $account = Account::create('cash', 'Cash', AccountType::Asset);

        $account->updateName('Cash on Hand');

        $this->assertEquals('Cash on Hand', $account->getName());
    }

    public function test_cannot_update_account_name_to_empty(): void
    {
        $account = Account::create('cash', 'Cash', AccountType::Asset);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Account name cannot be empty');

        $account->updateName('');
    }

    public function test_accounts_with_same_id_are_equal(): void
    {
        $account1 = Account::create('cash', 'Cash', AccountType::Asset);
        $account2 = Account::create('cash', 'Different Name', AccountType::Asset);

        $this->assertTrue($account1->equals($account2));
    }

    public function test_accounts_with_different_ids_are_not_equal(): void
    {
        $account1 = Account::create('cash', 'Cash', AccountType::Asset);
        $account2 = Account::create('bank', 'Cash', AccountType::Asset);

        $this->assertFalse($account1->equals($account2));
    }

    public function test_can_convert_account_to_array(): void
    {
        $account = Account::create('cash', 'Cash', AccountType::Asset);

        $array = $account->toArray();

        $this->assertEquals([
            'id' => 'cash',
            'name' => 'Cash',
            'type' => 'asset',
        ], $array);
    }

    public function test_can_create_all_account_types(): void
    {
        $asset = Account::create('cash', 'Cash', AccountType::Asset);
        $liability = Account::create('loan', 'Loan Payable', AccountType::Liability);
        $equity = Account::create('capital', 'Owner Capital', AccountType::Equity);
        $revenue = Account::create('sales', 'Sales Revenue', AccountType::Revenue);
        $expense = Account::create('rent', 'Rent Expense', AccountType::Expense);

        $this->assertEquals(AccountType::Asset, $asset->getType());
        $this->assertEquals(AccountType::Liability, $liability->getType());
        $this->assertEquals(AccountType::Equity, $equity->getType());
        $this->assertEquals(AccountType::Revenue, $revenue->getType());
        $this->assertEquals(AccountType::Expense, $expense->getType());
    }
}
