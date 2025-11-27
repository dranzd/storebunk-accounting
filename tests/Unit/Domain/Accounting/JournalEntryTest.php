<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Accounting;

use DateTime;
use Dranzd\StorebunkAccounting\Domain\Accounting\Journal\Status;
use Dranzd\StorebunkAccounting\Domain\Accounting\Journal\Entry;
use Dranzd\StorebunkAccounting\Domain\Accounting\Journal\Line;
use Dranzd\StorebunkAccounting\Domain\Accounting\Side;
use Dranzd\StorebunkAccounting\Domain\Accounting\Journal\Events\EntryCreated;
use Dranzd\StorebunkAccounting\Domain\Accounting\Journal\Events\EntryPosted;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class JournalEntryTest extends TestCase
{
    public function test_can_create_balanced_journal_entry(): void
    {
        $entry = Entry::create(
            'JE-001',
            new DateTime('2025-11-19'),
            'Cash sale',
            [
                Line::create('cash', 500.00, Side::Debit),
                Line::create('sales', 500.00, Side::Credit),
            ]
        );

        $this->assertEquals('JE-001', $entry->getId());
        $this->assertEquals('Cash sale', $entry->getDescription());
        $this->assertEquals(Status::Draft, $entry->getStatus());
        $this->assertCount(2, $entry->getLines());
        $this->assertNull($entry->getPostedAt());
    }

    public function test_creating_entry_emits_journal_entry_created_event(): void
    {
        $entry = Entry::create(
            'JE-001',
            new DateTime('2025-11-19'),
            'Cash sale',
            [
                Line::create('cash', 500.00, Side::Debit),
                Line::create('sales', 500.00, Side::Credit),
            ]
        );

        $events = $entry->getRecordedEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(EntryCreated::class, $events[0]);
        $this->assertEquals('JE-001', $events[0]->getEntryId());
    }

    public function test_cannot_create_entry_with_empty_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Journal entry ID cannot be empty');

        Entry::create(
            '',
            new DateTime('2025-11-19'),
            'Cash sale',
            [
                Line::create('cash', 500.00, Side::Debit),
                Line::create('sales', 500.00, Side::Credit),
            ]
        );
    }

    public function test_cannot_create_entry_with_empty_description(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Journal entry description cannot be empty');

        Entry::create(
            'JE-001',
            new DateTime('2025-11-19'),
            '',
            [
                Line::create('cash', 500.00, Side::Debit),
                Line::create('sales', 500.00, Side::Credit),
            ]
        );
    }

    public function test_cannot_create_entry_with_less_than_two_lines(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Journal entry must have at least 2 lines');

        Entry::create(
            'JE-001',
            new DateTime('2025-11-19'),
            'Invalid entry',
            [
                Line::create('cash', 500.00, Side::Debit),
            ]
        );
    }

    public function test_cannot_create_unbalanced_entry(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Journal entry must balance');

        Entry::create(
            'JE-001',
            new DateTime('2025-11-19'),
            'Unbalanced entry',
            [
                Line::create('cash', 500.00, Side::Debit),
                Line::create('sales', 300.00, Side::Credit),
            ]
        );
    }

    public function test_can_create_entry_with_multiple_debits_and_credits(): void
    {
        $entry = Entry::create(
            'JE-001',
            new DateTime('2025-11-19'),
            'Complex transaction',
            [
                Line::create('cash', 300.00, Side::Debit),
                Line::create('bank', 200.00, Side::Debit),
                Line::create('sales', 400.00, Side::Credit),
                Line::create('fees', 100.00, Side::Credit),
            ]
        );

        $this->assertCount(4, $entry->getLines());
        $this->assertEquals(Status::Draft, $entry->getStatus());
    }

    public function test_can_post_draft_entry(): void
    {
        $entry = Entry::create(
            'JE-001',
            new DateTime('2025-11-19'),
            'Cash sale',
            [
                Line::create('cash', 500.00, Side::Debit),
                Line::create('sales', 500.00, Side::Credit),
            ]
        );

        $entry->popRecordedEvents(); // Clear creation event
        $entry->post();

        $this->assertEquals(Status::Posted, $entry->getStatus());
        $this->assertNotNull($entry->getPostedAt());
    }

    public function test_posting_entry_emits_journal_entry_posted_event(): void
    {
        $entry = Entry::create(
            'JE-001',
            new DateTime('2025-11-19'),
            'Cash sale',
            [
                Line::create('cash', 500.00, Side::Debit),
                Line::create('sales', 500.00, Side::Credit),
            ]
        );

        $entry->popRecordedEvents();
        $entry->post();

        $events = $entry->getRecordedEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(EntryPosted::class, $events[0]);
        $this->assertEquals('JE-001', $events[0]->getEntryId());
    }

    public function test_cannot_post_already_posted_entry(): void
    {
        $entry = Entry::create(
            'JE-001',
            new DateTime('2025-11-19'),
            'Cash sale',
            [
                Line::create('cash', 500.00, Side::Debit),
                Line::create('sales', 500.00, Side::Credit),
            ]
        );

        $entry->post();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Journal entry cannot be posted in current status: posted');

        $entry->post();
    }

    public function test_can_reconstitute_entry_from_events(): void
    {
        $createdEvent = EntryCreated::occur(
            'JE-001',
            new DateTime('2025-11-19'),
            'Cash sale',
            [
                ['accountId' => 'cash', 'amount' => 500.00, 'side' => 'debit'],
                ['accountId' => 'sales', 'amount' => 500.00, 'side' => 'credit'],
            ]
        );

        // Reconstitute from history (creates new instance internally)
        $dummyEntry = Entry::create('temp', new DateTime(), 'temp', [
            Line::create('a', 1.0, Side::Debit),
            Line::create('b', 1.0, Side::Credit),
        ]);
        /** @var JournalEntry $entry */
        $entry = $dummyEntry->reconstituteFromHistory([$createdEvent]);

        $this->assertEquals('JE-001', $entry->getId());
        $this->assertEquals('Cash sale', $entry->getDescription());
        $this->assertEquals(Status::Draft, $entry->getStatus());
        $this->assertCount(2, $entry->getLines());
    }

    public function test_can_reconstitute_posted_entry_from_events(): void
    {
        $createdEvent = EntryCreated::occur(
            'JE-001',
            new DateTime('2025-11-19'),
            'Cash sale',
            [
                ['accountId' => 'cash', 'amount' => 500.00, 'side' => 'debit'],
                ['accountId' => 'sales', 'amount' => 500.00, 'side' => 'credit'],
            ]
        );

        $postedEvent = EntryPosted::occur(
            'JE-001',
            new \DateTimeImmutable()
        );

        // Reconstitute from history (creates new instance internally)
        $dummyEntry = Entry::create('temp', new DateTime(), 'temp', [
            Line::create('a', 1.0, Side::Debit),
            Line::create('b', 1.0, Side::Credit),
        ]);
        /** @var JournalEntry $entry */
        $entry = $dummyEntry->reconstituteFromHistory([$createdEvent, $postedEvent]);

        $this->assertEquals(Status::Posted, $entry->getStatus());
        $this->assertNotNull($entry->getPostedAt());
    }

    public function test_can_clear_uncommitted_events(): void
    {
        $entry = Entry::create(
            'JE-001',
            new DateTime('2025-11-19'),
            'Cash sale',
            [
                Line::create('cash', 500.00, Side::Debit),
                Line::create('sales', 500.00, Side::Credit),
            ]
        );

        $this->assertCount(1, $entry->getRecordedEvents());

        $entry->popRecordedEvents();

        $this->assertCount(0, $entry->getRecordedEvents());
    }
}
