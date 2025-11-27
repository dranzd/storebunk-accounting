<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use DateTime;
use Dranzd\StorebunkAccounting\Application\Query\GetAccountBalanceQuery;
use Dranzd\StorebunkAccounting\Application\Query\GetAccountQuery;
use Dranzd\StorebunkAccounting\Application\Query\GetAllAccountsQuery;
use Dranzd\StorebunkAccounting\Application\Query\GetLedgerQuery;
use Dranzd\StorebunkAccounting\Application\Query\Handler\GetAccountBalanceHandler;
use Dranzd\StorebunkAccounting\Application\Query\Handler\GetAccountHandler;
use Dranzd\StorebunkAccounting\Application\Query\Handler\GetAllAccountsHandler;
use Dranzd\StorebunkAccounting\Application\Query\Handler\GetLedgerHandler;
use Dranzd\StorebunkAccounting\Domain\Accounting\Account\Account;
use Dranzd\StorebunkAccounting\Domain\Accounting\Account\Type;
use Dranzd\StorebunkAccounting\Infrastructure\Persistence\ReadModel\InMemoryLedgerReadModel;
use Dranzd\StorebunkAccounting\Infrastructure\Persistence\Repository\InMemoryAccountRepository;
use PHPUnit\Framework\TestCase;

final class QueryHandlerTest extends TestCase
{
    private InMemoryAccountRepository $accountRepository;
    private InMemoryLedgerReadModel $ledgerReadModel;

    protected function setUp(): void
    {
        $this->accountRepository = new InMemoryAccountRepository();
        $this->ledgerReadModel = new InMemoryLedgerReadModel();

        // Setup test data
        $this->accountRepository->save(
            Account::create('cash', 'Cash', Type::Asset)
        );
        $this->accountRepository->save(
            Account::create('sales', 'Sales Revenue', Type::Revenue)
        );

        $this->ledgerReadModel->addPosting(
            'default',
            'cash',
            'JE-001',
            new DateTime('2025-11-19'),
            'Cash sale',
            500.00,
            null
        );
    }

    public function test_get_account_handler(): void
    {
        $handler = new GetAccountHandler($this->accountRepository);
        $query = GetAccountQuery::create('cash');

        $result = $handler->handle($query);
        $account = $result->getData();

        $this->assertNotNull($account);
        $this->assertEquals('cash', $account->getId());
        $this->assertEquals('Cash', $account->getName());
    }

    public function test_get_account_handler_returns_null_for_nonexistent(): void
    {
        $handler = new GetAccountHandler($this->accountRepository);
        $query = GetAccountQuery::create('nonexistent');

        $result = $handler->handle($query);
        $account = $result->getData();

        $this->assertNull($account);
    }

    public function test_get_all_accounts_handler(): void
    {
        $handler = new GetAllAccountsHandler($this->accountRepository);
        $query = GetAllAccountsQuery::create();

        $result = $handler->handle($query);
        $accounts = $result->getData();

        $this->assertCount(2, $accounts);
    }

    public function test_get_account_balance_handler(): void
    {
        $handler = new GetAccountBalanceHandler($this->ledgerReadModel);
        $query = GetAccountBalanceQuery::create('default', 'cash');

        $result = $handler->handle($query);
        $balance = $result->getData();

        $this->assertEquals(500.00, $balance);
    }

    public function test_get_ledger_handler(): void
    {
        $handler = new GetLedgerHandler($this->ledgerReadModel);
        $query = GetLedgerQuery::create('default', 'cash');

        $result = $handler->handle($query);
        $postings = $result->getData();

        $this->assertCount(1, $postings);
        $this->assertEquals('JE-001', $postings[0]['entryId']);
        $this->assertEquals(500.00, $postings[0]['debit']);
    }

    public function test_get_ledger_handler_with_date_filter(): void
    {
        $handler = new GetLedgerHandler($this->ledgerReadModel);
        $query = GetLedgerQuery::create(
            'default',
            'cash',
            new DateTime('2025-11-20'),
            null
        );

        $result = $handler->handle($query);
        $postings = $result->getData();

        // Should be empty because posting is on 2025-11-19
        $this->assertCount(0, $postings);
    }
}
