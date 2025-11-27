<?php

declare(strict_types=1);

namespace Tests\Integration;

use DateTime;
use Dranzd\StorebunkAccounting\Domain\Accounting\Account\Account;
use Dranzd\StorebunkAccounting\Domain\Accounting\Account\Type;
use Dranzd\StorebunkAccounting\Domain\Accounting\Journal\Entry;
use Dranzd\StorebunkAccounting\Domain\Accounting\Journal\Line;
use Dranzd\StorebunkAccounting\Domain\Accounting\Side;
use Dranzd\StorebunkAccounting\Infrastructure\Persistence\EventStore\EventSourcedJournalEntryRepository;
use Dranzd\StorebunkAccounting\Infrastructure\Persistence\EventStore\InMemoryEventStore;
use Dranzd\StorebunkAccounting\Infrastructure\Persistence\Projection\LedgerProjection;
use Dranzd\StorebunkAccounting\Infrastructure\Persistence\ReadModel\InMemoryLedgerReadModel;
use Dranzd\StorebunkAccounting\Infrastructure\Persistence\Repository\InMemoryAccountRepository;
use PHPUnit\Framework\TestCase;

final class JournalEntryFlowTest extends TestCase
{
    private InMemoryEventStore $eventStore;
    private EventSourcedJournalEntryRepository $journalEntryRepository;
    private InMemoryAccountRepository $accountRepository;
    private InMemoryLedgerReadModel $ledgerReadModel;
    private LedgerProjection $ledgerProjection;

    protected function setUp(): void
    {
        // Set up infrastructure
        $this->eventStore = new InMemoryEventStore();
        $this->journalEntryRepository = new EventSourcedJournalEntryRepository($this->eventStore);
        $this->accountRepository = new InMemoryAccountRepository();
        $this->ledgerReadModel = new InMemoryLedgerReadModel();
        $this->ledgerProjection = new LedgerProjection(
            $this->ledgerReadModel,
            $this->journalEntryRepository
        );

        // Subscribe projection to events
        $this->eventStore->subscribe(function ($event) {
            if ($event instanceof \Dranzd\StorebunkAccounting\Domain\Accounting\Journal\Events\EntryPosted) {
                $this->ledgerProjection->onEntryPosted($event);
            }
        });

        // Create test accounts
        $cash = Account::create('cash', 'Cash', Type::Asset);
        $sales = Account::create('sales', 'Sales Revenue', Type::Revenue);

        $this->accountRepository->save($cash);
        $this->accountRepository->save($sales);
    }

    public function test_complete_journal_entry_flow(): void
    {
        // 1. Create a journal entry
        $entry = Entry::create(
            'JE-001',
            new DateTime('2025-11-19'),
            'Cash sale',
            [
                Line::create('cash', 500.00, Side::Debit),
                Line::create('sales', 500.00, Side::Credit),
            ]
        );

        // 2. Save the entry (persists events)
        $this->journalEntryRepository->save($entry);

        // 3. Verify entry can be loaded
        $loadedEntry = $this->journalEntryRepository->load('JE-001');
        $this->assertEquals('JE-001', $loadedEntry->getId());
        $this->assertEquals('Cash sale', $loadedEntry->getDescription());

        // 4. Post the entry
        $loadedEntry->post();
        $this->journalEntryRepository->save($loadedEntry);

        // 5. Verify ledger was updated
        $cashBalance = $this->ledgerReadModel->getAccountBalance('default', 'cash');
        $salesBalance = $this->ledgerReadModel->getAccountBalance('default', 'sales');

        $this->assertEquals(500.00, $cashBalance);
        $this->assertEquals(-500.00, $salesBalance); // Credit decreases balance

        // 6. Verify ledger postings
        $cashPostings = $this->ledgerReadModel->getLedgerPostings('default', 'cash');
        $this->assertCount(1, $cashPostings);
        $this->assertEquals('JE-001', $cashPostings[0]['entryId']);
        $this->assertEquals(500.00, $cashPostings[0]['debit']);
        $this->assertNull($cashPostings[0]['credit']);
    }

    public function test_multiple_journal_entries(): void
    {
        // Entry 1: Cash sale
        $entry1 = Entry::create(
            'JE-001',
            new DateTime('2025-11-19'),
            'Cash sale',
            [
                Line::create('cash', 500.00, Side::Debit),
                Line::create('sales', 500.00, Side::Credit),
            ]
        );
        $this->journalEntryRepository->save($entry1);
        $entry1->post();
        $this->journalEntryRepository->save($entry1);

        // Entry 2: Another cash sale
        $entry2 = Entry::create(
            'JE-002',
            new DateTime('2025-11-20'),
            'Another cash sale',
            [
                Line::create('cash', 300.00, Side::Debit),
                Line::create('sales', 300.00, Side::Credit),
            ]
        );
        $this->journalEntryRepository->save($entry2);
        $entry2->post();
        $this->journalEntryRepository->save($entry2);

        // Verify cumulative balances
        $cashBalance = $this->ledgerReadModel->getAccountBalance('default', 'cash');
        $salesBalance = $this->ledgerReadModel->getAccountBalance('default', 'sales');

        $this->assertEquals(800.00, $cashBalance);
        $this->assertEquals(-800.00, $salesBalance);

        // Verify posting count
        $cashPostings = $this->ledgerReadModel->getLedgerPostings('default', 'cash');
        $this->assertCount(2, $cashPostings);
    }

    public function test_event_sourcing_reconstitution(): void
    {
        // Create and save entry
        $entry = Entry::create(
            'JE-001',
            new DateTime('2025-11-19'),
            'Cash sale',
            [
                Line::create('cash', 500.00, Side::Debit),
                Line::create('sales', 500.00, Side::Credit),
            ]
        );
        $this->journalEntryRepository->save($entry);

        // Post it
        $entry->post();
        $this->journalEntryRepository->save($entry);

        // Load from event store
        $reconstituted = $this->journalEntryRepository->load('JE-001');

        // Verify state was correctly reconstituted
        $this->assertEquals('JE-001', $reconstituted->getId());
        $this->assertEquals('Cash sale', $reconstituted->getDescription());
        $this->assertEquals(\Dranzd\StorebunkAccounting\Domain\Accounting\Journal\Status::Posted, $reconstituted->getStatus());
        $this->assertNotNull($reconstituted->getPostedAt());
        $this->assertCount(2, $reconstituted->getLines());
    }

    public function test_account_repository(): void
    {
        // Verify accounts exist
        $cash = $this->accountRepository->findById('cash');
        $this->assertNotNull($cash);
        $this->assertEquals('Cash', $cash->getName());
        $this->assertEquals(Type::Asset, $cash->getType());

        // Verify exists check
        $this->assertTrue($this->accountRepository->exists('cash'));
        $this->assertFalse($this->accountRepository->exists('nonexistent'));

        // Verify find all
        $allAccounts = $this->accountRepository->findAll();
        $this->assertCount(2, $allAccounts);
    }
}
