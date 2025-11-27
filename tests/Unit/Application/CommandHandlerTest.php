<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use DateTime;
use Dranzd\StorebunkAccounting\Application\Command\CreateAccountCommand;
use Dranzd\StorebunkAccounting\Application\Command\CreateJournalEntryCommand;
use Dranzd\StorebunkAccounting\Application\Command\Handler\CreateAccountHandler;
use Dranzd\StorebunkAccounting\Application\Command\Handler\CreateJournalEntryHandler;
use Dranzd\StorebunkAccounting\Application\Command\Handler\PostJournalEntryHandler;
use Dranzd\StorebunkAccounting\Application\Command\PostJournalEntryCommand;
use Dranzd\StorebunkAccounting\Domain\Accounting\Account\Type;
use Dranzd\StorebunkAccounting\Infrastructure\Persistence\EventStore\EventSourcedJournalEntryRepository;
use Dranzd\StorebunkAccounting\Infrastructure\Persistence\EventStore\InMemoryEventStore;
use Dranzd\StorebunkAccounting\Infrastructure\Persistence\Repository\InMemoryAccountRepository;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CommandHandlerTest extends TestCase
{
    private InMemoryAccountRepository $accountRepository;
    private InMemoryEventStore $eventStore;
    private EventSourcedJournalEntryRepository $journalEntryRepository;

    protected function setUp(): void
    {
        $this->accountRepository = new InMemoryAccountRepository();
        $this->eventStore = new InMemoryEventStore();
        $this->journalEntryRepository = new EventSourcedJournalEntryRepository($this->eventStore);
    }

    public function test_create_account_handler(): void
    {
        $handler = new CreateAccountHandler($this->accountRepository);

        $command = CreateAccountCommand::create(
            'cash',
            'Cash',
            Type::Asset
        );

        $handler->handle($command);

        // Verify account was created
        $account = $this->accountRepository->findById('cash');
        $this->assertNotNull($account);
        $this->assertEquals('Cash', $account->getName());
        $this->assertEquals(Type::Asset, $account->getType());
    }

    public function test_create_journal_entry_handler(): void
    {
        // Setup: Create accounts first
        $this->accountRepository->save(
            \Dranzd\StorebunkAccounting\Domain\Accounting\Account\Account::create('cash', 'Cash', Type::Asset)
        );
        $this->accountRepository->save(
            \Dranzd\StorebunkAccounting\Domain\Accounting\Account\Account::create('sales', 'Sales', Type::Revenue)
        );

        $handler = new CreateJournalEntryHandler(
            $this->journalEntryRepository,
            $this->accountRepository
        );

        $command = CreateJournalEntryCommand::create(
            'JE-001',
            new DateTime('2025-11-19'),
            'Cash sale',
            [
                ['accountId' => 'cash', 'amount' => 500.00, 'side' => 'debit'],
                ['accountId' => 'sales', 'amount' => 500.00, 'side' => 'credit'],
            ]
        );

        $handler->handle($command);

        // Verify entry was created
        $entry = $this->journalEntryRepository->load('JE-001');
        $this->assertEquals('JE-001', $entry->getId());
        $this->assertEquals('Cash sale', $entry->getDescription());
        $this->assertCount(2, $entry->getLines());
    }

    public function test_create_journal_entry_handler_validates_accounts_exist(): void
    {
        $handler = new CreateJournalEntryHandler(
            $this->journalEntryRepository,
            $this->accountRepository
        );

        $command = CreateJournalEntryCommand::create(
            'JE-001',
            new DateTime('2025-11-19'),
            'Cash sale',
            [
                ['accountId' => 'nonexistent', 'amount' => 500.00, 'side' => 'debit'],
                ['accountId' => 'sales', 'amount' => 500.00, 'side' => 'credit'],
            ]
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Account not found: nonexistent');

        $handler->handle($command);
    }

    public function test_post_journal_entry_handler(): void
    {
        // Setup: Create accounts and entry
        $this->accountRepository->save(
            \Dranzd\StorebunkAccounting\Domain\Accounting\Account\Account::create('cash', 'Cash', Type::Asset)
        );
        $this->accountRepository->save(
            \Dranzd\StorebunkAccounting\Domain\Accounting\Account\Account::create('sales', 'Sales', Type::Revenue)
        );

        $createHandler = new CreateJournalEntryHandler(
            $this->journalEntryRepository,
            $this->accountRepository
        );

        $createCommand = CreateJournalEntryCommand::create(
            'JE-001',
            new DateTime('2025-11-19'),
            'Cash sale',
            [
                ['accountId' => 'cash', 'amount' => 500.00, 'side' => 'debit'],
                ['accountId' => 'sales', 'amount' => 500.00, 'side' => 'credit'],
            ]
        );

        $createHandler->handle($createCommand);

        // Post the entry
        $postHandler = new PostJournalEntryHandler($this->journalEntryRepository);
        $postCommand = PostJournalEntryCommand::create('JE-001');

        $postHandler->handle($postCommand);

        // Verify entry was posted
        $entry = $this->journalEntryRepository->load('JE-001');
        $this->assertEquals(
            \Dranzd\StorebunkAccounting\Domain\Accounting\Journal\Status::Posted,
            $entry->getStatus()
        );
        $this->assertNotNull($entry->getPostedAt());
    }
}
