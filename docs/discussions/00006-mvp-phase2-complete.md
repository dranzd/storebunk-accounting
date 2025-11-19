# Discussion 00006: MVP Phase 2 - Repositories & Infrastructure Complete

**Date:** 2025-11-19
**Status:** ✅ Completed
**Related:** Feature 00004 (MVP Implementation), Discussion 00005 (Event Sourcing Integration)

---

## Overview

Successfully implemented MVP Phase 2: Repositories & Infrastructure. The accounting system now has a complete event-sourced persistence layer with projections and read models.

## Objectives

Implement the infrastructure layer to support:
1. Event-sourced persistence for journal entries
2. Simple storage for accounts (chart of accounts)
3. Ledger projections for read models
4. Integration tests to verify the complete flow

---

## Components Implemented

### 1. Repository Interfaces (Ports) ✅

#### JournalEntryRepositoryInterface
**Location:** `src/Domain/Port/JournalEntryRepositoryInterface.php`

**Methods:**
- `save(JournalEntry $journalEntry): void` - Persist recorded events
- `load(string $id): JournalEntry` - Reconstitute from events
- `exists(string $id): bool` - Check if entry exists

**Purpose:** Hexagonal architecture port - defines contract for persistence

#### AccountRepositoryInterface
**Location:** `src/Domain/Port/AccountRepositoryInterface.php`

**Methods:**
- `save(Account $account): void` - Save account
- `findById(string $id): ?Account` - Find by ID
- `exists(string $id): bool` - Check existence
- `findAll(): array` - Get all accounts

**Purpose:** Simple CRUD for chart of accounts

#### LedgerReadModelInterface
**Location:** `src/Domain/Port/LedgerReadModelInterface.php`

**Methods:**
- `getAccountBalance(string $tenantId, string $accountId): float`
- `getLedgerPostings(...)` - Query postings with date filters
- `getAllAccountBalances(string $tenantId): array`

**Purpose:** Read-side interface for CQRS queries

---

### 2. Event Store ✅

#### InMemoryEventStore
**Location:** `src/Infrastructure/Persistence/EventStore/InMemoryEventStore.php`

**Features:**
- Stores events in memory (array-based)
- Stream-based storage (one stream per aggregate)
- Event subscription support for projections
- No persistence across restarts (MVP acceptable)

**Key Methods:**
```php
public function appendToStream(string $streamId, array $events): void;
public function readStream(string $streamId): array;
public function streamExists(string $streamId): bool;
public function subscribe(callable $subscriber): void;
public function getAllEvents(): array;
```

**Usage Pattern:**
```php
$eventStore = new InMemoryEventStore();

// Subscribe projection
$eventStore->subscribe(function($event) {
    if ($event instanceof JournalEntryPosted) {
        $projection->handle($event);
    }
});

// Append events
$eventStore->appendToStream('journal-entry-JE-001', $events);

// Read events
$events = $eventStore->readStream('journal-entry-JE-001');
```

---

### 3. Event-Sourced Repository ✅

#### EventSourcedJournalEntryRepository
**Location:** `src/Infrastructure/Persistence/EventStore/EventSourcedJournalEntryRepository.php`

**Implementation:**
```php
public function save(JournalEntry $journalEntry): void
{
    $events = $journalEntry->popRecordedEvents();
    $streamId = "journal-entry-{$journalEntry->getId()}";
    $this->eventStore->appendToStream($streamId, $events);
}

public function load(string $id): JournalEntry
{
    $events = $this->eventStore->readStream("journal-entry-{$id}");
    return (new JournalEntry())->reconstituteFromHistory($events);
}
```

**Key Features:**
- Uses `popRecordedEvents()` from AggregateRootTrait
- Stream ID pattern: `journal-entry-{id}`
- Reconstitutes aggregates from event history
- Throws exception if aggregate not found

---

### 4. Account Repository ✅

#### InMemoryAccountRepository
**Location:** `src/Infrastructure/Persistence/Repository/InMemoryAccountRepository.php`

**Implementation:**
- Simple array-based storage
- No event sourcing (accounts are simple entities)
- CRUD operations for chart of accounts

**Usage:**
```php
$repository = new InMemoryAccountRepository();

$cash = Account::create('cash', 'Cash', AccountType::Asset);
$repository->save($cash);

$account = $repository->findById('cash');
$exists = $repository->exists('cash');
$all = $repository->findAll();
```

---

### 5. Read Model ✅

#### InMemoryLedgerReadModel
**Location:** `src/Infrastructure/Persistence/ReadModel/InMemoryLedgerReadModel.php`

**Data Structure:**
```php
// Balances: [tenantId][accountId] => balance
private array $balances = [];

// Postings: [tenantId] => array of posting records
private array $postings = [];
```

**Features:**
- Stores account balances (current state)
- Stores ledger postings (history)
- Supports date-range queries
- Multi-tenant support (tenant ID in keys)

**Posting Record:**
```php
[
    'accountId' => 'cash',
    'entryId' => 'JE-001',
    'date' => DateTime,
    'description' => 'Cash sale',
    'debit' => 500.00,
    'credit' => null,
    'balance' => 1500.00,
]
```

---

### 6. Projection ✅

#### LedgerProjection
**Location:** `src/Infrastructure/Persistence/Projection/LedgerProjection.php`

**Purpose:** Listen to `JournalEntryPosted` events and update ledger

**Flow:**
1. Event store publishes `JournalEntryPosted` event
2. Projection receives event via subscription
3. Loads journal entry from repository
4. Creates ledger posting for each line
5. Updates account balances

**Implementation:**
```php
public function onJournalEntryPosted(JournalEntryPosted $event): void
{
    $entry = $this->journalEntryRepository->load($event->getJournalEntryId());

    foreach ($entry->getLines() as $line) {
        $debit = $line->getSide() === Side::Debit ? $line->getAmount() : null;
        $credit = $line->getSide() === Side::Credit ? $line->getAmount() : null;

        $this->readModel->addPosting(
            'default', // tenantId
            $line->getAccountId(),
            $entry->getId(),
            $entry->getDate(),
            $entry->getDescription(),
            $debit,
            $credit
        );
    }
}
```

---

## Integration Tests ✅

### JournalEntryFlowTest
**Location:** `tests/Integration/JournalEntryFlowTest.php`

**Test Coverage:**

#### 1. Complete Journal Entry Flow
```php
test_complete_journal_entry_flow()
```
- Creates journal entry
- Saves to event store
- Loads from event store
- Posts entry
- Verifies ledger updated
- Verifies postings created

#### 2. Multiple Journal Entries
```php
test_multiple_journal_entries()
```
- Creates multiple entries
- Posts all entries
- Verifies cumulative balances
- Verifies posting count

#### 3. Event Sourcing Reconstitution
```php
test_event_sourcing_reconstitution()
```
- Creates and posts entry
- Loads from event store
- Verifies state correctly reconstituted
- Verifies all properties match

#### 4. Account Repository
```php
test_account_repository()
```
- Verifies CRUD operations
- Tests existence checks
- Tests findAll functionality

---

## Architecture Flow

### Write Side (Commands)

```
1. Create Journal Entry
   ↓
2. JournalEntry.create() - Records JournalEntryCreated event
   ↓
3. Repository.save() - Pops events and appends to event store
   ↓
4. Event Store - Stores events in stream
   ↓
5. Event Store - Notifies subscribers (projections)
```

### Post Journal Entry

```
1. Load from Repository
   ↓
2. Repository.load() - Reads events from stream
   ↓
3. Reconstitute aggregate from events
   ↓
4. JournalEntry.post() - Records JournalEntryPosted event
   ↓
5. Repository.save() - Appends new event
   ↓
6. Projection receives event
   ↓
7. Projection updates read model (ledger)
```

### Read Side (Queries)

```
1. Query ledger read model
   ↓
2. ReadModel.getAccountBalance()
   ↓
3. Return current balance (no aggregate loading)
```

---

## Test Results

### All Tests Pass ✅

```
PHPUnit 11.5.44
Runtime: PHP 8.3.27

.......................................     39 / 39 (100%)
Time: 00:00.031, Memory: 10.00 MB

OK (39 tests, 95 assertions)
```

**Test Breakdown:**
- Unit Tests: 35 tests (Domain layer)
- Integration Tests: 4 tests (Full stack)
- Total Assertions: 95

### Code Quality ✅

**PHPStan (Level 9):**
```
 21/21 [▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓] 100%
 [OK] No errors
```

**PHP CodeSniffer (PSR-12):**
```
..................... 21 / 21 (100%)
Time: 325ms; Memory: 10MB
```

---

## Files Created

### Domain Layer (Ports)
1. `src/Domain/Port/JournalEntryRepositoryInterface.php`
2. `src/Domain/Port/AccountRepositoryInterface.php`
3. `src/Domain/Port/LedgerReadModelInterface.php`

### Infrastructure Layer
4. `src/Infrastructure/Persistence/EventStore/InMemoryEventStore.php`
5. `src/Infrastructure/Persistence/EventStore/EventSourcedJournalEntryRepository.php`
6. `src/Infrastructure/Persistence/Repository/InMemoryAccountRepository.php`
7. `src/Infrastructure/Persistence/ReadModel/InMemoryLedgerReadModel.php`
8. `src/Infrastructure/Persistence/Projection/LedgerProjection.php`

### Tests
9. `tests/Integration/JournalEntryFlowTest.php`

**Total:** 9 new files

---

## MVP Acceptance Criteria Status

### ✅ 1. Chart of Accounts Management
- ✅ Can create accounts with id, name, and type
- ✅ Can retrieve account by ID
- ✅ Account types are validated (Asset, Liability, Equity, Revenue, Expense)

### ✅ 2. Journal Entry Creation
- ✅ Can create a journal entry with multiple lines
- ✅ System validates debits equal credits
- ✅ System validates minimum 2 lines
- ✅ System validates all account IDs exist (via repository)
- ✅ Entry is created in draft status

### ✅ 3. Journal Entry Posting
- ✅ Can post a draft journal entry
- ✅ System emits `JournalEntryPosted` event
- ✅ Cannot post an already-posted entry

### ✅ 4. Ledger Projection
- ✅ When entry is posted, ledger automatically updates
- ✅ Each line creates a posting in ledger
- ✅ Account balances are maintained
- ✅ Can query postings by account

### ✅ 5. Event Sourcing
- ✅ Aggregates are persisted as events
- ✅ Aggregates can be reconstituted from events
- ✅ Event store supports subscriptions
- ✅ Projections listen to events

---

## Key Design Decisions

### 1. In-Memory Storage
**Decision:** Use in-memory storage for MVP
**Rationale:** Simplifies implementation, acceptable for MVP
**Trade-off:** No persistence across restarts
**Future:** Replace with PostgreSQL event store

### 2. Tenant ID Hardcoded
**Decision:** Use 'default' tenant ID in projection
**Rationale:** Multi-tenancy not in MVP scope
**Future:** Extract tenant ID from event metadata

### 3. Stream Naming
**Decision:** `journal-entry-{id}` pattern
**Rationale:** Simple, predictable, one stream per aggregate
**Alternative:** Could use aggregate type prefix

### 4. Projection Subscription
**Decision:** Direct event store subscription
**Rationale:** Simple for MVP
**Future:** Use message bus for async processing

### 5. Balance Calculation
**Decision:** Debit increases, credit decreases balance
**Rationale:** Matches accounting convention
**Note:** Account type determines normal balance side

---

## What's NOT in MVP (Deferred)

❌ Persistent event store (PostgreSQL)
❌ Snapshots for performance
❌ Optimistic concurrency control
❌ Event versioning/upcasting
❌ Projection rebuild capability
❌ Async event processing
❌ Trial balance report
❌ Financial statements
❌ Journal entry reversal
❌ Multi-currency support
❌ Fiscal periods
❌ Account hierarchies

---

## Next Steps

### MVP Phase 3: Application Layer (Commands & Queries)

**Commands:**
1. `CreateJournalEntryCommand` + Handler
2. `PostJournalEntryCommand` + Handler
3. `CreateAccountCommand` + Handler

**Queries:**
1. `GetAccountBalanceQuery` + Handler
2. `GetLedgerQuery` + Handler
3. `GetAccountQuery` + Handler

**Command/Query Bus:**
- Simple synchronous bus for MVP
- Can use `dranzd/common-cqrs` library

---

## Summary

✅ **MVP Phase 2 Complete**
- 3 repository interfaces (ports)
- 1 event store implementation
- 2 repository implementations
- 1 read model implementation
- 1 projection implementation
- 4 integration tests
- 39 total tests passing
- 0 code quality issues

**Quality Metrics:**
- Tests: 39/39 passing (95 assertions)
- PHPStan: Level 9 - no errors
- PHPCS: PSR-12 compliant
- Integration: Full event sourcing flow working

**Status:** Ready for MVP Phase 3 (Application Layer - Commands & Queries)

The infrastructure layer is now complete with event sourcing, projections, and read models all working together. The system can persist journal entries as events, reconstitute them, and automatically update the ledger when entries are posted.
