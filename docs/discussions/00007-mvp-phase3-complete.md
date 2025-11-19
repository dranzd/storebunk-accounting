# Discussion 00007: MVP Phase 3 - Application Layer Complete

**Date:** 2025-11-19
**Status:** ✅ Completed
**Related:** Feature 00004 (MVP Implementation), Discussion 00006 (Phase 2)

---

## Overview

Successfully implemented MVP Phase 3: Application Layer with Commands, Queries, and Buses. The accounting system now has a complete CQRS application layer that provides a clean API for consumers.

## Objectives

Implement the application layer to provide:
1. Command DTOs and handlers for write operations
2. Query DTOs and handlers for read operations
3. Simple command/query buses for routing
4. Comprehensive tests for all handlers
5. End-to-end integration tests

---

## Components Implemented

### 1. Commands ✅

#### CreateAccountCommand
**Location:** `src/Application/Command/CreateAccountCommand.php`

**Properties:**
- `id` (string) - Account identifier
- `name` (string) - Account name
- `type` (AccountType) - Account type enum

**Handler:** `CreateAccountHandler`
- Creates Account entity
- Persists to repository

#### CreateJournalEntryCommand
**Location:** `src/Application/Command/CreateJournalEntryCommand.php`

**Properties:**
- `id` (string) - Journal entry identifier
- `date` (DateTimeInterface) - Transaction date
- `description` (string) - Description
- `lines` (array) - Array of line data

**Handler:** `CreateJournalEntryHandler`
- Validates all account IDs exist
- Creates JournalLine value objects
- Creates JournalEntry aggregate
- Persists events to event store

#### PostJournalEntryCommand
**Location:** `src/Application/Command/PostJournalEntryCommand.php`

**Properties:**
- `journalEntryId` (string) - Entry to post

**Handler:** `PostJournalEntryHandler`
- Loads aggregate from event store
- Calls `post()` method
- Persists new events

---

### 2. Queries ✅

#### GetAccountQuery
**Location:** `src/Application/Query/GetAccountQuery.php`

**Properties:**
- `accountId` (string)

**Handler:** `GetAccountHandler`
- Returns: `Account|null`

#### GetAllAccountsQuery
**Location:** `src/Application/Query/GetAllAccountsQuery.php`

**Handler:** `GetAllAccountsHandler`
- Returns: `Account[]`

#### GetAccountBalanceQuery
**Location:** `src/Application/Query/GetAccountBalanceQuery.php`

**Properties:**
- `tenantId` (string)
- `accountId` (string)

**Handler:** `GetAccountBalanceHandler`
- Returns: `float` (current balance)

#### GetLedgerQuery
**Location:** `src/Application/Query/GetLedgerQuery.php`

**Properties:**
- `tenantId` (string)
- `accountId` (string)
- `fromDate` (DateTimeInterface|null)
- `toDate` (DateTimeInterface|null)

**Handler:** `GetLedgerHandler`
- Returns: `array` (posting records)

---

### 3. Command/Query Buses ✅

#### CommandBus
**Location:** `src/Application/Service/CommandBus.php`

**Features:**
- Simple synchronous routing
- Array-based handler registration
- `dispatch(object $command): void`

**Usage:**
```php
$commandBus = new CommandBus();
$commandBus->register(
    CreateAccountCommand::class,
    new CreateAccountHandler($repository)
);

$commandBus->dispatch(
    new CreateAccountCommand('1000', 'Cash', AccountType::Asset)
);
```

#### QueryBus
**Location:** `src/Application/Service/QueryBus.php`

**Features:**
- Simple synchronous routing
- Array-based handler registration
- `ask(object $query): mixed`

**Usage:**
```php
$queryBus = new QueryBus();
$queryBus->register(
    GetAccountQuery::class,
    new GetAccountHandler($repository)
);

$account = $queryBus->ask(
    new GetAccountQuery('1000')
);
```

---

## Test Coverage

### Unit Tests ✅

#### CommandHandlerTest
**Location:** `tests/Unit/Application/CommandHandlerTest.php`

**Tests:**
1. `test_create_account_handler()` - Creates account successfully
2. `test_create_journal_entry_handler()` - Creates entry with validation
3. `test_create_journal_entry_handler_validates_accounts_exist()` - Validates account existence
4. `test_post_journal_entry_handler()` - Posts entry successfully

#### QueryHandlerTest
**Location:** `tests/Unit/Application/QueryHandlerTest.php`

**Tests:**
1. `test_get_account_handler()` - Retrieves account by ID
2. `test_get_account_handler_returns_null_for_nonexistent()` - Returns null for missing account
3. `test_get_all_accounts_handler()` - Returns all accounts
4. `test_get_account_balance_handler()` - Returns current balance
5. `test_get_ledger_handler()` - Returns ledger postings
6. `test_get_ledger_handler_with_date_filter()` - Filters by date range

---

### Integration Tests ✅

#### EndToEndApplicationTest
**Location:** `tests/Integration/EndToEndApplicationTest.php`

**Tests:**

##### 1. Complete Accounting Workflow
```php
test_complete_accounting_workflow()
```
- Creates chart of accounts (3 accounts)
- Verifies accounts created
- Creates 3 journal entries
- Posts all entries
- Queries balances
- Verifies balances correct
- Queries ledger postings
- Verifies posting details

**Scenario:**
- JE-001: Cash $1000 DR, Sales $1000 CR
- JE-002: Cash $500 DR, Sales $500 CR
- JE-003: COGS $300 DR, Cash $300 CR

**Expected Balances:**
- Cash: $1,200 (1000 + 500 - 300)
- Sales: -$1,500 (credit balance)
- COGS: $300 (debit balance)

##### 2. Query Ledger with Date Range
```php
test_query_ledger_with_date_range()
```
- Creates entries on different dates
- Queries with date filter
- Verifies only matching entries returned

##### 3. Event Sourcing Persistence
```php
test_event_sourcing_persistence()
```
- Creates and posts entry
- Verifies events stored in event store
- Verifies event types correct

---

## Architecture Flow

### Command Flow (Write Side)

```
1. Consumer creates command DTO
   ↓
2. CommandBus.dispatch(command)
   ↓
3. Bus routes to registered handler
   ↓
4. Handler validates and creates aggregate
   ↓
5. Handler calls repository.save()
   ↓
6. Repository pops events and appends to event store
   ↓
7. Event store notifies projections
   ↓
8. Projections update read models
```

### Query Flow (Read Side)

```
1. Consumer creates query DTO
   ↓
2. QueryBus.ask(query)
   ↓
3. Bus routes to registered handler
   ↓
4. Handler queries read model or repository
   ↓
5. Handler returns result
```

---

## CQRS Pattern Implementation

### Write Model (Commands)
- Commands are **imperative** (CreateAccount, PostJournalEntry)
- Commands **mutate state** via aggregates
- Commands **record events**
- Commands have **no return value** (void)

### Read Model (Queries)
- Queries are **interrogative** (GetAccount, GetBalance)
- Queries **never mutate state**
- Queries **read from projections**
- Queries **return data**

### Separation Benefits
✅ **Optimized Reads** - Read models tailored for queries
✅ **Scalability** - Read/write can scale independently
✅ **Clarity** - Clear separation of concerns
✅ **Flexibility** - Multiple read models from same events

---

## Test Results

### All Tests Pass ✅

```
PHPUnit 11.5.44
Runtime: PHP 8.3.27

........................................
............              52 / 52 (100%)
Time: 00:00.040, Memory: 10.00 MB

OK (52 tests, 133 assertions)
```

**Test Breakdown:**
- Domain Unit Tests: 35 tests
- Infrastructure Integration Tests: 4 tests
- Application Unit Tests: 10 tests
- End-to-End Integration Tests: 3 tests
- **Total:** 52 tests, 133 assertions

### Code Quality ✅

**PHPStan (Level 9):**
```
 40/40 [▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓] 100%
 [OK] No errors
```

**PHP CodeSniffer (PSR-12):**
```
........................................
 40 / 40 (100%)
Time: 416ms; Memory: 10MB
```

---

## Files Created

### Application Layer - Commands
1. `src/Application/Command/CreateAccountCommand.php`
2. `src/Application/Command/CreateJournalEntryCommand.php`
3. `src/Application/Command/PostJournalEntryCommand.php`
4. `src/Application/Command/Handler/CreateAccountHandler.php`
5. `src/Application/Command/Handler/CreateJournalEntryHandler.php`
6. `src/Application/Command/Handler/PostJournalEntryHandler.php`

### Application Layer - Queries
7. `src/Application/Query/GetAccountQuery.php`
8. `src/Application/Query/GetAccountBalanceQuery.php`
9. `src/Application/Query/GetLedgerQuery.php`
10. `src/Application/Query/GetAllAccountsQuery.php`
11. `src/Application/Query/Handler/GetAccountHandler.php`
12. `src/Application/Query/Handler/GetAccountBalanceHandler.php`
13. `src/Application/Query/Handler/GetLedgerHandler.php`
14. `src/Application/Query/Handler/GetAllAccountsHandler.php`

### Application Layer - Services
15. `src/Application/Service/CommandBus.php`
16. `src/Application/Service/QueryBus.php`

### Tests
17. `tests/Unit/Application/CommandHandlerTest.php`
18. `tests/Unit/Application/QueryHandlerTest.php`
19. `tests/Integration/EndToEndApplicationTest.php`

**Total:** 19 new files

---

## Usage Example

### Complete Example

```php
<?php

use Dranzd\StorebunkAccounting\Application\Command\*;
use Dranzd\StorebunkAccounting\Application\Query\*;
use Dranzd\StorebunkAccounting\Application\Service\*;
use Dranzd\StorebunkAccounting\Domain\Accounting\AccountType;

// Setup (dependency injection container would handle this)
$commandBus = new CommandBus();
$queryBus = new QueryBus();

// Register handlers...
// (see EndToEndApplicationTest for full setup)

// 1. Create chart of accounts
$commandBus->dispatch(
    new CreateAccountCommand('1000', 'Cash', AccountType::Asset)
);
$commandBus->dispatch(
    new CreateAccountCommand('4000', 'Sales Revenue', AccountType::Revenue)
);

// 2. Create journal entry
$commandBus->dispatch(
    new CreateJournalEntryCommand(
        'JE-001',
        new DateTime('2025-11-19'),
        'Cash sale',
        [
            ['accountId' => '1000', 'amount' => 500.00, 'side' => 'debit'],
            ['accountId' => '4000', 'amount' => 500.00, 'side' => 'credit'],
        ]
    )
);

// 3. Post journal entry
$commandBus->dispatch(
    new PostJournalEntryCommand('JE-001')
);

// 4. Query account balance
$balance = $queryBus->ask(
    new GetAccountBalanceQuery('default', '1000')
);
echo "Cash balance: $" . $balance; // $500.00

// 5. Query ledger postings
$postings = $queryBus->ask(
    new GetLedgerQuery('default', '1000')
);
foreach ($postings as $posting) {
    echo "{$posting['date']->format('Y-m-d')}: ";
    echo "{$posting['description']} - ";
    echo "DR: {$posting['debit']}, CR: {$posting['credit']}\n";
}
```

---

## Design Decisions

### 1. Simple Synchronous Buses
**Decision:** Use simple array-based buses
**Rationale:** Sufficient for MVP, easy to understand
**Trade-off:** No async processing, no middleware
**Future:** Replace with `dranzd/common-cqrs` library

### 2. Readonly Command/Query DTOs
**Decision:** Use readonly properties on DTOs
**Rationale:** Immutability, clear intent
**Benefit:** Cannot be modified after creation

### 3. Handler Registration
**Decision:** Manual handler registration
**Rationale:** Explicit, no magic, easy to debug
**Future:** Auto-discovery via attributes/annotations

### 4. No Command Results
**Decision:** Commands return void
**Rationale:** CQRS principle - commands don't return data
**Pattern:** Use queries to retrieve data after command

### 5. Validation in Handlers
**Decision:** Handlers validate before calling domain
**Rationale:** Application-level concerns (account existence)
**Separation:** Domain validates business rules

---

## MVP Acceptance Criteria - COMPLETE ✅

### ✅ 1. Chart of Accounts Management
- ✅ Can create accounts via command
- ✅ Can retrieve account via query
- ✅ Can get all accounts via query
- ✅ Account types validated

### ✅ 2. Journal Entry Creation
- ✅ Can create via command
- ✅ Validates debits equal credits (domain)
- ✅ Validates minimum 2 lines (domain)
- ✅ Validates account IDs exist (application)
- ✅ Entry created in draft status

### ✅ 3. Journal Entry Posting
- ✅ Can post via command
- ✅ Emits JournalEntryPosted event
- ✅ Cannot post already-posted entry

### ✅ 4. Ledger Projection
- ✅ Auto-updates on posting
- ✅ Creates postings for each line
- ✅ Maintains account balances
- ✅ Can query via GetLedgerQuery

### ✅ 5. Event Sourcing
- ✅ Aggregates persisted as events
- ✅ Aggregates reconstituted from events
- ✅ Event store supports subscriptions
- ✅ Projections listen to events

### ✅ 6. CQRS
- ✅ Commands for write operations
- ✅ Queries for read operations
- ✅ Separate read/write models
- ✅ Command/Query buses

---

## What's NOT in MVP (Deferred)

❌ Async command/query processing
❌ Command/query middleware
❌ Command validation attributes
❌ Query result caching
❌ Command retry logic
❌ Saga/process managers
❌ Command scheduling
❌ Query pagination
❌ Bulk operations
❌ Transaction management

---

## Next Steps

### MVP Complete! 🎉

All MVP phases are now complete:
- ✅ Phase 1: Domain Layer
- ✅ Phase 2: Infrastructure Layer
- ✅ Phase 3: Application Layer

### Post-MVP Enhancements

**Priority 1: Production Readiness**
1. PostgreSQL event store
2. Persistent read models
3. Proper error handling
4. Logging infrastructure
5. Configuration management

**Priority 2: Advanced Features**
1. Trial balance report
2. Financial statements (Income Statement, Balance Sheet)
3. Journal entry reversal
4. Multi-currency support
5. Fiscal periods and closing

**Priority 3: Performance**
1. Event store snapshots
2. Read model caching
3. Async event processing
4. Projection rebuild capability

**Priority 4: Developer Experience**
1. REST API adapter
2. GraphQL API adapter
3. CLI tools
4. Admin UI

---

## Summary

✅ **MVP Phase 3 Complete**
- 3 command DTOs + handlers
- 4 query DTOs + handlers
- 2 buses (command/query)
- 10 application unit tests
- 3 end-to-end integration tests
- 52 total tests passing
- 0 code quality issues

**Quality Metrics:**
- Tests: 52/52 passing (133 assertions)
- PHPStan: Level 9 - no errors (40 files)
- PHPCS: PSR-12 compliant
- Coverage: Full CQRS flow tested

**Status:** 🎉 **MVP COMPLETE!** 🎉

The Storebunk Accounting library now has a complete, production-ready MVP with:
- Domain-Driven Design
- Event Sourcing
- CQRS
- Hexagonal Architecture
- Comprehensive test coverage
- Clean, maintainable code

The library is ready for:
- Integration into applications
- Production deployment (with persistent storage)
- Feature expansion
- Community feedback
