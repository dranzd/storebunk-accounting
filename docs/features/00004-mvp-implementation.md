# Feature 00004: MVP Implementation - Minimum Viable Accounting Library

**Status:** Planning
**Priority:** Critical
**Related:** Discussion 00002, Feature 00001, Feature 00003
**Goal:** Build the absolute minimum required for a functional accounting library

---

## Executive Summary

This document defines the **Minimum Viable Product (MVP)** for the Storebunk Accounting library based on the core requirements identified in Discussion 00002. The MVP focuses on the three essential components that make something "accounting":

1. **Chart of Accounts** - Where to record transactions
2. **Journal Entries** - What to record (the heart of accounting)
3. **Ledger Projection** - How to view balances (read model)

Everything else (trial balance, financial statements, multi-currency, fiscal periods, reversals, etc.) is deferred to post-MVP phases.

---

## Core Principle

> **If you can register accounts, create balanced journal entries, post them, and query account balances — you have a working accounting system.**

This MVP delivers exactly that, nothing more.

---

## 1. Scope: What's IN the MVP

### 1.1 Domain Objects

#### Account (Entity)
**Purpose:** Represents a single account in the chart of accounts

**Properties:**
- `id` (string) - Unique identifier
- `name` (string) - Account name (e.g., "Cash", "Sales Revenue")
- `type` (enum) - Asset | Liability | Equity | Revenue | Expense

**Invariants:**
- ID must be unique
- Name must not be empty
- Type must be one of the five valid types

**Methods:**
- `create(id, name, type)` - Factory method
- `equals(Account $other)` - Equality check

---

#### JournalEntry (Aggregate Root)
**Purpose:** The heart of accounting - records financial transactions

**Properties:**
- `id` (string) - Unique identifier
- `date` (DateTimeInterface) - Transaction date
- `description` (string) - Human-readable description
- `lines` (array<JournalLine>) - Collection of debit/credit lines
- `status` (enum) - draft | posted
- `postedAt` (DateTimeInterface|null) - When posted

**Invariants:**
- Must have at least 2 lines
- Total debits must equal total credits
- All lines must reference existing accounts
- Cannot modify after posting

**Methods:**
- `create(id, date, description, lines[])` - Factory method
- `post()` - Mark as posted (validates invariants)
- `getUncommittedEvents()` - Get domain events
- `apply(DomainEvent $event)` - Apply event to state

---

#### JournalLine (Value Object)
**Purpose:** Represents a single line in a journal entry

**Properties:**
- `accountId` (string) - Reference to account
- `amount` (float) - Transaction amount (always positive)
- `side` (enum) - debit | credit

**Invariants:**
- Amount must be positive
- Side must be debit or credit
- Account ID must not be empty

**Methods:**
- Immutable value object
- `equals(JournalLine $other)` - Equality check

---

### 1.2 Domain Events

#### JournalEntryCreated
**Emitted when:** A new journal entry is created in draft state

**Payload:**
```json
{
  "id": "JE-001",
  "date": "2025-11-19",
  "description": "Cash sale",
  "lines": [
    {"accountId": "cash", "amount": 500, "side": "debit"},
    {"accountId": "sales", "amount": 500, "side": "credit"}
  ],
  "metadata": {
    "tenantId": "T-1",
    "actorId": "user-7",
    "occurredAt": "2025-11-19T10:30:00Z"
  }
}
```

---

#### JournalEntryPosted
**Emitted when:** A journal entry is posted to the ledger

**Payload:**
```json
{
  "id": "JE-001",
  "postedAt": "2025-11-19T10:30:00Z",
  "metadata": {
    "tenantId": "T-1",
    "actorId": "user-7",
    "occurredAt": "2025-11-19T10:30:00Z"
  }
}
```

---

### 1.3 Commands & Handlers

#### CreateJournalEntry Command
**Purpose:** Create a new journal entry in draft state

**Properties:**
- `date` (DateTimeInterface)
- `description` (string)
- `lines` (array) - Array of line data

**Handler Responsibilities:**
1. Validate command data
2. Validate all account IDs exist
3. Create JournalEntry aggregate
4. Collect emitted events
5. Append events to event store

---

#### PostJournalEntry Command
**Purpose:** Post a journal entry to the ledger

**Properties:**
- `journalEntryId` (string)

**Handler Responsibilities:**
1. Load JournalEntry aggregate from event store
2. Call `post()` method
3. Collect emitted events
4. Append events to event store

---

### 1.4 Projections (Read Models)

#### LedgerAccount Projection
**Purpose:** Materialized view of account balances and posting history

**Read Model Schema:**
```sql
-- Ledger postings (detailed history)
CREATE TABLE ledger_postings (
    id BIGSERIAL PRIMARY KEY,
    tenant_id VARCHAR(50) NOT NULL,
    account_id VARCHAR(50) NOT NULL,
    entry_id VARCHAR(50) NOT NULL,
    date DATE NOT NULL,
    description TEXT,
    debit DECIMAL(19,4),
    credit DECIMAL(19,4),
    balance_after DECIMAL(19,4) NOT NULL,
    created_at TIMESTAMP NOT NULL,
    INDEX idx_tenant_account (tenant_id, account_id, date)
);

-- Account balances (current state)
CREATE TABLE account_balances (
    tenant_id VARCHAR(50) NOT NULL,
    account_id VARCHAR(50) NOT NULL,
    account_name VARCHAR(255) NOT NULL,
    account_type VARCHAR(50) NOT NULL,
    balance DECIMAL(19,4) NOT NULL DEFAULT 0,
    updated_at TIMESTAMP NOT NULL,
    PRIMARY KEY (tenant_id, account_id)
);
```

**Projection Handler:**
- Subscribes to `JournalEntryPosted` events
- For each line in the entry:
  - Insert into `ledger_postings`
  - Update `account_balances`
  - Calculate running balance

---

### 1.5 Query Services

#### GetAccountBalance Query
**Purpose:** Get current balance for an account

**Input:**
- `tenantId` (string)
- `accountId` (string)

**Output:**
```json
{
  "accountId": "cash",
  "accountName": "Cash",
  "accountType": "Asset",
  "balance": 1500.00,
  "asOf": "2025-11-19T10:30:00Z"
}
```

---

#### GetLedger Query
**Purpose:** Get posting history for an account

**Input:**
- `tenantId` (string)
- `accountId` (string)
- `fromDate` (optional)
- `toDate` (optional)

**Output:**
```json
{
  "accountId": "cash",
  "accountName": "Cash",
  "postings": [
    {
      "entryId": "JE-001",
      "date": "2025-11-19",
      "description": "Cash sale",
      "debit": 500.00,
      "credit": null,
      "balance": 1500.00
    }
  ]
}
```

---

### 1.6 Infrastructure (Minimum)

#### In-Memory Event Store
**Purpose:** Simple event persistence for MVP

**Interface:**
```php
interface EventStore
{
    public function appendToStream(string $streamId, array $events): void;
    public function readStream(string $streamId): array;
}
```

**Implementation:**
- Store events in memory (array)
- No persistence across restarts (acceptable for MVP)
- No optimistic concurrency (single-threaded MVP)

---

#### Simple Projection Runner
**Purpose:** Process events and update read models

**Responsibilities:**
- Subscribe to event store
- Call projection handlers for each event
- Track last processed position (in-memory)

---

## 2. Scope: What's OUT of the MVP

The following are explicitly **deferred** to post-MVP phases:

### Deferred to Phase 2+
- ❌ Trial balance report
- ❌ Income statement
- ❌ Balance sheet
- ❌ Cash flow statement
- ❌ Journal entry reversal
- ❌ Journal entry modification/deletion
- ❌ Special journal types (sales, purchase, cash receipts, cash disbursements)
- ❌ Multi-currency support
- ❌ Fiscal periods and period closing
- ❌ Account hierarchies (parent/child accounts)
- ❌ Opening balances
- ❌ Persistent event store (PostgreSQL)
- ❌ Snapshots
- ❌ Optimistic concurrency control
- ❌ Idempotency handling
- ❌ Event versioning
- ❌ Projection rebuild capability
- ❌ REST/gRPC API adapters
- ❌ Authentication/Authorization
- ❌ Audit logging beyond events

---

## 3. MVP Acceptance Criteria

The MVP is complete when:

✅ **1. Chart of Accounts Management**
- Can create accounts with id, name, and type
- Can retrieve account by ID
- Account types are validated (Asset, Liability, Equity, Revenue, Expense)

✅ **2. Journal Entry Creation**
- Can create a journal entry with multiple lines
- System validates debits equal credits
- System validates minimum 2 lines
- System validates all account IDs exist
- Entry is created in draft status

✅ **3. Journal Entry Posting**
- Can post a draft journal entry
- System emits `JournalEntryPosted` event
- Cannot post an already-posted entry

✅ **4. Ledger Projection**
- When entry is posted, ledger automatically updates
- Each line creates a posting in `ledger_postings`
- Account balances update in `account_balances`
- Running balance is calculated correctly

✅ **5. Balance Queries**
- Can query current balance for any account
- Can query posting history for any account
- Results are accurate and up-to-date

✅ **6. End-to-End Flow**
```php
// 1. Create accounts
$cash = Account::create('cash', 'Cash', AccountType::Asset);
$sales = Account::create('sales', 'Sales Revenue', AccountType::Revenue);

// 2. Create journal entry
$command = new CreateJournalEntry(
    date: new DateTime('2025-11-19'),
    description: 'Cash sale',
    lines: [
        ['accountId' => 'cash', 'amount' => 500, 'side' => 'debit'],
        ['accountId' => 'sales', 'amount' => 500, 'side' => 'credit']
    ]
);
$entryId = $commandBus->dispatch($command);

// 3. Post entry
$commandBus->dispatch(new PostJournalEntry($entryId));

// 4. Query balance
$balance = $queries->getAccountBalance('T-1', 'cash');
// Result: 500.00

// 5. Query ledger
$ledger = $queries->getLedger('T-1', 'cash');
// Result: 1 posting with debit of 500
```

---

## 4. Implementation Order

### Phase MVP-1: Domain Core (Week 1)
**Tasks:**
1. Implement `AccountType` enum
2. Implement `Side` enum (debit/credit)
3. Implement `JournalLine` value object
4. Implement `Account` entity
5. Implement `JournalEntry` aggregate with invariants
6. Write unit tests for all domain objects

**Deliverable:** Domain objects with 100% test coverage

---

### Phase MVP-2: Events & Event Store (Week 1)
**Tasks:**
1. Define `JournalEntryCreated` event
2. Define `JournalEntryPosted` event
3. Implement `InMemoryEventStore`
4. Implement event serialization
5. Write tests for event store

**Deliverable:** Working in-memory event store

---

### Phase MVP-3: Commands & Handlers (Week 2)
**Tasks:**
1. Implement `CreateJournalEntry` command
2. Implement `CreateJournalEntryHandler`
3. Implement `PostJournalEntry` command
4. Implement `PostJournalEntryHandler`
5. Implement simple command bus
6. Write integration tests

**Deliverable:** Commands produce events

---

### Phase MVP-4: Projections (Week 2)
**Tasks:**
1. Create read model schema (in-memory or SQLite)
2. Implement `LedgerProjection` handler
3. Implement projection runner
4. Write projection tests

**Deliverable:** Ledger updates when entries posted

---

### Phase MVP-5: Queries (Week 3)
**Tasks:**
1. Implement `GetAccountBalance` query
2. Implement `GetLedger` query
3. Implement query service
4. Write query tests

**Deliverable:** Can query balances and postings

---

### Phase MVP-6: Integration & Testing (Week 3)
**Tasks:**
1. End-to-end integration tests
2. Example usage scripts
3. Basic documentation
4. Performance validation

**Deliverable:** Working MVP ready for demo

---

## 5. File Structure (MVP)

```
/src
  /Domain
    /Accounting
      Account.php
      AccountType.php
      JournalEntry.php
      JournalLine.php
      Side.php
      /Events
        JournalEntryCreated.php
        JournalEntryPosted.php
  /Application
    /Commands
      CreateJournalEntry.php
      PostJournalEntry.php
    /Handlers
      CreateJournalEntryHandler.php
      PostJournalEntryHandler.php
    /Queries
      GetAccountBalance.php
      GetLedger.php
      LedgerQueryService.php
  /Infrastructure
    /EventStore
      InMemoryEventStore.php
    /Projections
      LedgerProjection.php
      ProjectionRunner.php
    /ReadModel
      InMemoryReadModel.php (or SQLiteReadModel.php)

/tests
  /Unit
    /Domain
      AccountTest.php
      JournalEntryTest.php
      JournalLineTest.php
  /Integration
    CreateJournalEntryTest.php
    PostJournalEntryTest.php
    LedgerProjectionTest.php
  /EndToEnd
    AccountingFlowTest.php
```

---

## 6. Success Metrics

The MVP is successful if:

1. **Functional:** All 6 acceptance criteria pass
2. **Tested:** 100% test coverage on domain, 80%+ overall
3. **Fast:** Can process 1000 journal entries in < 1 second
4. **Simple:** Total codebase < 2000 lines
5. **Documented:** README with working examples

---

## 7. Next Steps After MVP

Once MVP is complete and validated:

1. **Phase 2:** Add persistent event store (PostgreSQL)
2. **Phase 3:** Add trial balance and basic reports
3. **Phase 4:** Add journal entry reversal
4. **Phase 5:** Add multi-tenant isolation
5. **Phase 6:** Add idempotency and concurrency control

But **not before** the MVP is working and validated.

---

## 8. Risk Mitigation

### Risk: Scope creep
**Mitigation:** Strict adherence to "what's OUT" list. Any new feature request goes to backlog.

### Risk: Over-engineering
**Mitigation:** Use simplest possible implementation. In-memory is fine for MVP.

### Risk: Incomplete testing
**Mitigation:** Tests are part of definition of done for each phase.

### Risk: Domain model mismatch
**Mitigation:** Validate with accounting expert before Phase MVP-3.

---

## 9. MVP Demo Script

When MVP is complete, this demo should work:

```php
<?php
// Demo: Complete accounting flow

// 1. Setup
$accounts = new InMemoryAccountRepository();
$eventStore = new InMemoryEventStore();
$commandBus = new SimpleCommandBus($eventStore, $accounts);
$projectionRunner = new ProjectionRunner($eventStore);
$queries = new LedgerQueryService($projectionRunner->getReadModel());

// 2. Create chart of accounts
$accounts->save(Account::create('cash', 'Cash', AccountType::Asset));
$accounts->save(Account::create('sales', 'Sales Revenue', AccountType::Revenue));
$accounts->save(Account::create('cogs', 'Cost of Goods Sold', AccountType::Expense));
$accounts->save(Account::create('inventory', 'Inventory', AccountType::Asset));

// 3. Record a sale
$saleEntry = $commandBus->dispatch(new CreateJournalEntry(
    date: new DateTime('2025-11-19'),
    description: 'Cash sale - Product A',
    lines: [
        ['accountId' => 'cash', 'amount' => 500, 'side' => 'debit'],
        ['accountId' => 'sales', 'amount' => 500, 'side' => 'credit']
    ]
));

$commandBus->dispatch(new PostJournalEntry($saleEntry));

// 4. Record cost of goods sold
$cogsEntry = $commandBus->dispatch(new CreateJournalEntry(
    date: new DateTime('2025-11-19'),
    description: 'COGS for Product A',
    lines: [
        ['accountId' => 'cogs', 'amount' => 200, 'side' => 'debit'],
        ['accountId' => 'inventory', 'amount' => 200, 'side' => 'credit']
    ]
));

$commandBus->dispatch(new PostJournalEntry($cogsEntry));

// 5. Query results
echo "Cash balance: " . $queries->getAccountBalance('cash')->balance . "\n";
// Output: 500.00

echo "Sales balance: " . $queries->getAccountBalance('sales')->balance . "\n";
// Output: 500.00

echo "Inventory balance: " . $queries->getAccountBalance('inventory')->balance . "\n";
// Output: -200.00 (credit balance)

$cashLedger = $queries->getLedger('cash');
echo "Cash postings: " . count($cashLedger->postings) . "\n";
// Output: 1

// 6. Verify accounting equation
$assets = $queries->getAccountBalance('cash')->balance
        + $queries->getAccountBalance('inventory')->balance;
$revenue = $queries->getAccountBalance('sales')->balance;
$expenses = $queries->getAccountBalance('cogs')->balance;

echo "Assets: $assets\n";
echo "Revenue - Expenses: " . ($revenue - $expenses) . "\n";
echo "Balanced: " . ($assets == ($revenue - $expenses) ? 'YES' : 'NO') . "\n";
// Output: Balanced: YES
```

---

## 10. Definition of Done

MVP is **DONE** when:

- [ ] All code in `src/` follows PSR-12
- [ ] All tests pass (`./utils test`)
- [ ] PHPStan level 5 passes (`./utils phpstan`)
- [ ] Code coverage > 80%
- [ ] Demo script runs successfully
- [ ] README updated with MVP usage examples
- [ ] This document marked as **Implemented**

---

**Next Document:** Once MVP is complete, create `00005-phase2-persistent-storage.md`
