<?php

declare(strict_types=1);

namespace Tests\Integration;

use DateTime;
use Dranzd\StorebunkAccounting\Application\Command\CreateAccountCommand;
use Dranzd\StorebunkAccounting\Application\Command\CreateJournalEntryCommand;
use Dranzd\StorebunkAccounting\Application\Command\Handler\CreateAccountHandler;
use Dranzd\StorebunkAccounting\Application\Command\Handler\CreateJournalEntryHandler;
use Dranzd\StorebunkAccounting\Application\Command\Handler\PostJournalEntryHandler;
use Dranzd\StorebunkAccounting\Application\Command\PostJournalEntryCommand;
use Dranzd\StorebunkAccounting\Application\Query\GetAccountBalanceQuery;
use Dranzd\StorebunkAccounting\Application\Query\GetAccountQuery;
use Dranzd\StorebunkAccounting\Application\Query\GetAllAccountsQuery;
use Dranzd\StorebunkAccounting\Application\Query\GetLedgerQuery;
use Dranzd\StorebunkAccounting\Application\Query\Handler\GetAccountBalanceHandler;
use Dranzd\StorebunkAccounting\Application\Query\Handler\GetAccountHandler;
use Dranzd\StorebunkAccounting\Application\Query\Handler\GetAllAccountsHandler;
use Dranzd\StorebunkAccounting\Application\Query\Handler\GetLedgerHandler;
use Dranzd\Common\Cqrs\Application\Command\Bus as CommandBusInterface;
use Dranzd\Common\Cqrs\Application\Query\Bus as QueryBusInterface;
use Dranzd\Common\Cqrs\Infrastructure\Bus\SimpleCommandBus;
use Dranzd\Common\Cqrs\Infrastructure\Bus\SimpleQueryBus;
use Dranzd\Common\Cqrs\Infrastructure\HandlerRegistry\InMemoryHandlerRegistry;
use Dranzd\StorebunkAccounting\Domain\Accounting\Account\Type;
use Dranzd\StorebunkAccounting\Infrastructure\Persistence\EventStore\EventSourcedJournalEntryRepository;
use Dranzd\StorebunkAccounting\Infrastructure\Persistence\EventStore\InMemoryEventStore;
use Dranzd\StorebunkAccounting\Infrastructure\Persistence\Projection\LedgerProjection;
use Dranzd\StorebunkAccounting\Infrastructure\Persistence\ReadModel\InMemoryLedgerReadModel;
use Dranzd\StorebunkAccounting\Infrastructure\Persistence\Repository\InMemoryAccountRepository;
use PHPUnit\Framework\TestCase;

/**
 * End-to-End Application Test
 *
 * Tests the complete application flow using command/query bus.
 * This simulates how the library would be used by consumers.
 */
final class EndToEndApplicationTest extends TestCase
{
    private CommandBusInterface $commandBus;
    private QueryBusInterface $queryBus;
    private InMemoryEventStore $eventStore;
    private InMemoryLedgerReadModel $ledgerReadModel;

    protected function setUp(): void
    {
        // Setup infrastructure
        $this->eventStore = new InMemoryEventStore();
        $accountRepository = new InMemoryAccountRepository();
        $journalEntryRepository = new EventSourcedJournalEntryRepository($this->eventStore);
        $this->ledgerReadModel = new InMemoryLedgerReadModel();

        // Setup projection
        $ledgerProjection = new LedgerProjection($this->ledgerReadModel, $journalEntryRepository);
        $this->eventStore->subscribe(function ($event) use ($ledgerProjection) {
            if ($event instanceof \Dranzd\StorebunkAccounting\Domain\Accounting\Journal\Events\EntryPosted) {
                $ledgerProjection->onEntryPosted($event);
            }
        });

        // Setup command bus with handler registry
        $commandRegistry = new InMemoryHandlerRegistry();
        $commandRegistry->register(
            CreateAccountCommand::class,
            fn($cmd) => (new CreateAccountHandler($accountRepository))->handle($cmd)
        );
        $commandRegistry->register(
            CreateJournalEntryCommand::class,
            fn($cmd) => (new CreateJournalEntryHandler($journalEntryRepository, $accountRepository))->handle($cmd)
        );
        $commandRegistry->register(
            PostJournalEntryCommand::class,
            fn($cmd) => (new PostJournalEntryHandler($journalEntryRepository))->handle($cmd)
        );
        $this->commandBus = new SimpleCommandBus($commandRegistry);

        // Setup query bus with handler registry
        $queryRegistry = new InMemoryHandlerRegistry();
        $queryRegistry->register(
            GetAccountQuery::class,
            fn($qry) => (new GetAccountHandler($accountRepository))->handle($qry)
        );
        $queryRegistry->register(
            GetAllAccountsQuery::class,
            fn($qry) => (new GetAllAccountsHandler($accountRepository))->handle($qry)
        );
        $queryRegistry->register(
            GetAccountBalanceQuery::class,
            fn($qry) => (new GetAccountBalanceHandler($this->ledgerReadModel))->handle($qry)
        );
        $queryRegistry->register(
            GetLedgerQuery::class,
            fn($qry) => (new GetLedgerHandler($this->ledgerReadModel))->handle($qry)
        );
        $this->queryBus = new SimpleQueryBus($queryRegistry);
    }

    public function test_complete_accounting_workflow(): void
    {
        // 1. Create chart of accounts
        $this->commandBus->dispatch(
            CreateAccountCommand::create('1000', 'Cash', Type::Asset)
        );
        $this->commandBus->dispatch(
            CreateAccountCommand::create('4000', 'Sales Revenue', Type::Revenue)
        );
        $this->commandBus->dispatch(
            CreateAccountCommand::create('5000', 'Cost of Goods Sold', Type::Expense)
        );

        // 2. Verify accounts were created
        $allAccounts = $this->queryBus->ask(GetAllAccountsQuery::create())->getData();
        $this->assertCount(3, $allAccounts);

        $cashAccount = $this->queryBus->ask(GetAccountQuery::create('1000'))->getData();
        $this->assertNotNull($cashAccount);
        $this->assertEquals('Cash', $cashAccount->getName());

        // 3. Create journal entries
        $this->commandBus->dispatch(
            CreateJournalEntryCommand::create(
                'JE-001',
                new DateTime('2025-11-19'),
                'Cash sale #1',
                [
                    ['accountId' => '1000', 'amount' => 1000.00, 'side' => 'debit'],
                    ['accountId' => '4000', 'amount' => 1000.00, 'side' => 'credit'],
                ]
            )
        );

        $this->commandBus->dispatch(
            CreateJournalEntryCommand::create(
                'JE-002',
                new DateTime('2025-11-20'),
                'Cash sale #2',
                [
                    ['accountId' => '1000', 'amount' => 500.00, 'side' => 'debit'],
                    ['accountId' => '4000', 'amount' => 500.00, 'side' => 'credit'],
                ]
            )
        );

        $this->commandBus->dispatch(
            CreateJournalEntryCommand::create(
                'JE-003',
                new DateTime('2025-11-21'),
                'Record COGS',
                [
                    ['accountId' => '5000', 'amount' => 300.00, 'side' => 'debit'],
                    ['accountId' => '1000', 'amount' => 300.00, 'side' => 'credit'],
                ]
            )
        );

        // 4. Post journal entries
        $this->commandBus->dispatch(PostJournalEntryCommand::create('JE-001'));
        $this->commandBus->dispatch(PostJournalEntryCommand::create('JE-002'));
        $this->commandBus->dispatch(PostJournalEntryCommand::create('JE-003'));

        // 5. Query account balances
        $cashBalance = $this->queryBus->ask(
            GetAccountBalanceQuery::create('default', '1000')
        )->getData();
        $salesBalance = $this->queryBus->ask(
            GetAccountBalanceQuery::create('default', '4000')
        )->getData();
        $cogsBalance = $this->queryBus->ask(
            GetAccountBalanceQuery::create('default', '5000')
        )->getData();

        // 6. Verify balances
        $this->assertEquals(1200.00, $cashBalance); // 1000 + 500 - 300
        $this->assertEquals(-1500.00, $salesBalance); // -(1000 + 500) credit
        $this->assertEquals(300.00, $cogsBalance); // 300 debit

        // 7. Query ledger postings
        $cashPostings = $this->queryBus->ask(
            GetLedgerQuery::create('default', '1000')
        )->getData();
        $this->assertCount(3, $cashPostings);

        // 8. Verify posting details
        $this->assertEquals('JE-001', $cashPostings[0]['entryId']);
        $this->assertEquals(1000.00, $cashPostings[0]['debit']);
        $this->assertNull($cashPostings[0]['credit']);

        $this->assertEquals('JE-003', $cashPostings[2]['entryId']);
        $this->assertNull($cashPostings[2]['debit']);
        $this->assertEquals(300.00, $cashPostings[2]['credit']);
    }

    public function test_query_ledger_with_date_range(): void
    {
        // Setup: Create accounts and entries
        $this->commandBus->dispatch(
            CreateAccountCommand::create('1000', 'Cash', Type::Asset)
        );
        $this->commandBus->dispatch(
            CreateAccountCommand::create('4000', 'Sales', Type::Revenue)
        );

        $this->commandBus->dispatch(
            CreateJournalEntryCommand::create(
                'JE-001',
                new DateTime('2025-11-19'),
                'Sale on Nov 19',
                [
                    ['accountId' => '1000', 'amount' => 100.00, 'side' => 'debit'],
                    ['accountId' => '4000', 'amount' => 100.00, 'side' => 'credit'],
                ]
            )
        );

        $this->commandBus->dispatch(
            CreateJournalEntryCommand::create(
                'JE-002',
                new DateTime('2025-11-21'),
                'Sale on Nov 21',
                [
                    ['accountId' => '1000', 'amount' => 200.00, 'side' => 'debit'],
                    ['accountId' => '4000', 'amount' => 200.00, 'side' => 'credit'],
                ]
            )
        );

        $this->commandBus->dispatch(PostJournalEntryCommand::create('JE-001'));
        $this->commandBus->dispatch(PostJournalEntryCommand::create('JE-002'));

        // Query with date filter
        $postings = $this->queryBus->ask(
            GetLedgerQuery::create(
                'default',
                '1000',
                new DateTime('2025-11-20'),
                new DateTime('2025-11-22')
            )
        )->getData();

        // Should only get JE-002
        $this->assertCount(1, $postings);
        $this->assertEquals('JE-002', $postings[0]['entryId']);
    }

    public function test_event_sourcing_persistence(): void
    {
        // Create account and entry
        $this->commandBus->dispatch(
            CreateAccountCommand::create('1000', 'Cash', Type::Asset)
        );
        $this->commandBus->dispatch(
            CreateAccountCommand::create('4000', 'Sales', Type::Revenue)
        );

        $this->commandBus->dispatch(
            CreateJournalEntryCommand::create(
                'JE-001',
                new DateTime('2025-11-19'),
                'Test entry',
                [
                    ['accountId' => '1000', 'amount' => 100.00, 'side' => 'debit'],
                    ['accountId' => '4000', 'amount' => 100.00, 'side' => 'credit'],
                ]
            )
        );

        $this->commandBus->dispatch(PostJournalEntryCommand::create('JE-001'));

        // Verify events were stored
        $events = $this->eventStore->readStream('journal-entry-JE-001');
        $this->assertCount(2, $events); // Created + Posted

        $this->assertInstanceOf(
            \Dranzd\StorebunkAccounting\Domain\Accounting\Journal\Events\EntryCreated::class,
            $events[0]
        );
        $this->assertInstanceOf(
            \Dranzd\StorebunkAccounting\Domain\Accounting\Journal\Events\EntryPosted::class,
            $events[1]
        );
    }
}
