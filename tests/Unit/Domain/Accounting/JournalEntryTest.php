<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Accounting;

use DateTime;
use Dranzd\StorebunkAccounting\Domain\Accounting\EntryStatus;
use Dranzd\StorebunkAccounting\Domain\Accounting\JournalEntry;
use Dranzd\StorebunkAccounting\Domain\Accounting\JournalLine;
use Dranzd\StorebunkAccounting\Domain\Accounting\Side;
use Dranzd\StorebunkAccounting\Domain\Accounting\Events\JournalEntryCreated;
use Dranzd\StorebunkAccounting\Domain\Accounting\Events\JournalEntryPosted;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class JournalEntryTest extends TestCase
{
    public function test_can_create_balanced_journal_entry(): void
    {
        $entry = JournalEntry::create(
            'JE-001',
            new DateTime('2025-11-19'),
            'Cash sale',
            [
                JournalLine::create('cash', 500.00, Side::Debit),
                JournalLine::create('sales', 500.00, Side::Credit),
            ]
        );

        $this->assertEquals('JE-001', $entry->getId());
        $this->assertEquals('Cash sale', $entry->getDescription());
        $this->assertEquals(EntryStatus::Draft, $entry->getStatus());
        $this->assertCount(2, $entry->getLines());
        $this->assertNull($entry->getPostedAt());
    }

    public function test_creating_entry_emits_journal_entry_created_event(): void
    {
        $entry = JournalEntry::create(
            'JE-001',
            new DateTime('2025-11-19'),
            'Cash sale',
            [
                JournalLine::create('cash', 500.00, Side::Debit),
                JournalLine::create('sales', 500.00, Side::Credit),
            ]
        );

        $events = $entry->getUncommittedEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(JournalEntryCreated::class, $events[0]);
        $this->assertEquals('JE-001', $events[0]->getJournalEntryId());
    }

    public function test_cannot_create_entry_with_empty_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Journal entry ID cannot be empty');

        JournalEntry::create(
            '',
            new DateTime('2025-11-19'),
            'Cash sale',
            [
                JournalLine::create('cash', 500.00, Side::Debit),
                JournalLine::create('sales', 500.00, Side::Credit),
            ]
        );
    }

    public function test_cannot_create_entry_with_empty_description(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Journal entry description cannot be empty');

        JournalEntry::create(
            'JE-001',
            new DateTime('2025-11-19'),
            '',
            [
                JournalLine::create('cash', 500.00, Side::Debit),
                JournalLine::create('sales', 500.00, Side::Credit),
            ]
        );
    }

    public function test_cannot_create_entry_with_less_than_two_lines(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Journal entry must have at least 2 lines');

        JournalEntry::create(
            'JE-001',
            new DateTime('2025-11-19'),
            'Invalid entry',
            [
                JournalLine::create('cash', 500.00, Side::Debit),
            ]
        );
    }

    public function test_cannot_create_unbalanced_entry(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Journal entry must balance');

        JournalEntry::create(
            'JE-001',
            new DateTime('2025-11-19'),
            'Unbalanced entry',
            [
                JournalLine::create('cash', 500.00, Side::Debit),
                JournalLine::create('sales', 300.00, Side::Credit),
            ]
        );
    }

    public function test_can_create_entry_with_multiple_debits_and_credits(): void
    {
        $entry = JournalEntry::create(
            'JE-001',
            new DateTime('2025-11-19'),
            'Complex transaction',
            [
                JournalLine::create('cash', 300.00, Side::Debit),
                JournalLine::create('bank', 200.00, Side::Debit),
                JournalLine::create('sales', 400.00, Side::Credit),
                JournalLine::create('fees', 100.00, Side::Credit),
            ]
        );

        $this->assertCount(4, $entry->getLines());
        $this->assertEquals(EntryStatus::Draft, $entry->getStatus());
    }

    public function test_can_post_draft_entry(): void
    {
        $entry = JournalEntry::create(
            'JE-001',
            new DateTime('2025-11-19'),
            'Cash sale',
            [
                JournalLine::create('cash', 500.00, Side::Debit),
                JournalLine::create('sales', 500.00, Side::Credit),
            ]
        );

        $entry->clearUncommittedEvents(); // Clear creation event
        $entry->post();

        $this->assertEquals(EntryStatus::Posted, $entry->getStatus());
        $this->assertNotNull($entry->getPostedAt());
    }

    public function test_posting_entry_emits_journal_entry_posted_event(): void
    {
        $entry = JournalEntry::create(
            'JE-001',
            new DateTime('2025-11-19'),
            'Cash sale',
            [
                JournalLine::create('cash', 500.00, Side::Debit),
                JournalLine::create('sales', 500.00, Side::Credit),
            ]
        );

        $entry->clearUncommittedEvents();
        $entry->post();

        $events = $entry->getUncommittedEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(JournalEntryPosted::class, $events[0]);
        $this->assertEquals('JE-001', $events[0]->getJournalEntryId());
    }

    public function test_cannot_post_already_posted_entry(): void
    {
        $entry = JournalEntry::create(
            'JE-001',
            new DateTime('2025-11-19'),
            'Cash sale',
            [
                JournalLine::create('cash', 500.00, Side::Debit),
                JournalLine::create('sales', 500.00, Side::Credit),
            ]
        );

        $entry->post();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Journal entry cannot be posted in current status: posted');

        $entry->post();
    }

    public function test_can_reconstitute_entry_from_events(): void
    {
        $createdEvent = new JournalEntryCreated(
            'evt-123',
            'JE-001',
            new DateTime('2025-11-19'),
            'Cash sale',
            [
                ['accountId' => 'cash', 'amount' => 500.00, 'side' => 'debit'],
                ['accountId' => 'sales', 'amount' => 500.00, 'side' => 'credit'],
            ],
            new \DateTimeImmutable()
        );

        $entry = JournalEntry::reconstitute([$createdEvent]);

        $this->assertEquals('JE-001', $entry->getId());
        $this->assertEquals('Cash sale', $entry->getDescription());
        $this->assertEquals(EntryStatus::Draft, $entry->getStatus());
        $this->assertCount(2, $entry->getLines());
    }

    public function test_can_reconstitute_posted_entry_from_events(): void
    {
        $createdEvent = new JournalEntryCreated(
            'evt-123',
            'JE-001',
            new DateTime('2025-11-19'),
            'Cash sale',
            [
                ['accountId' => 'cash', 'amount' => 500.00, 'side' => 'debit'],
                ['accountId' => 'sales', 'amount' => 500.00, 'side' => 'credit'],
            ],
            new \DateTimeImmutable()
        );

        $postedEvent = new JournalEntryPosted(
            'evt-456',
            'JE-001',
            new \DateTimeImmutable(),
            new \DateTimeImmutable()
        );

        $entry = JournalEntry::reconstitute([$createdEvent, $postedEvent]);

        $this->assertEquals(EntryStatus::Posted, $entry->getStatus());
        $this->assertNotNull($entry->getPostedAt());
    }

    public function test_can_clear_uncommitted_events(): void
    {
        $entry = JournalEntry::create(
            'JE-001',
            new DateTime('2025-11-19'),
            'Cash sale',
            [
                JournalLine::create('cash', 500.00, Side::Debit),
                JournalLine::create('sales', 500.00, Side::Credit),
            ]
        );

        $this->assertCount(1, $entry->getUncommittedEvents());

        $entry->clearUncommittedEvents();

        $this->assertCount(0, $entry->getUncommittedEvents());
    }
}
