# StoreBunk Accounting - Architecture Documentation

## Overview

This library implements the **Accounting Domain** for the StoreBunk Multi-Retail Platform using:

- **Domain-Driven Design (DDD)**
- **Event Sourcing (ES)**
- **Hexagonal Architecture (Ports & Adapters)**
- **Command Query Responsibility Segregation (CQRS)**

## Architecture Layers

### 1. Domain Layer (`src/Domain/`)

The core business logic layer, independent of infrastructure concerns.

#### **Model** (`src/Domain/Accounting/`)
- **Aggregates**:
  - `JournalEntry` - Main aggregate root for recording financial transactions
  - `Account` - Entity representing accounts in the chart of accounts
- **Value Objects**: Immutable objects like `JournalLine`, `Money`, `Side` (debit/credit)
- **Enums**: `AccountType` (Asset, Liability, Equity, Revenue, Expense), `EntryStatus`, `Side`
- **Events**: Domain events like `JournalEntryCreated`, `JournalEntryPosted`, `LedgerPosted`

#### **Ports** (`src/Domain/Port/`)
Interfaces that define contracts for external dependencies:
- `JournalEntryRepositoryInterface` - Write model repository
- `AccountRepositoryInterface` - Account management
- `LedgerReadModelInterface` - Read model queries for ledger and balances

**Note:** Event publishing is provided by the common-event-sourcing library. Consumers can implement their own event bus adapters (Laravel Events, Symfony EventDispatcher, RabbitMQ, etc.).

### 2. Application Layer (`src/Application/`)

Orchestrates use cases and business workflows.

#### **Commands** (`src/Application/Command/`)
Write operations that change state:
- `CreateJournalEntryCommand` / `CreateJournalEntryHandler`
- `PostJournalEntryCommand` / `PostJournalEntryHandler`
- `CreateAccountCommand` / `CreateAccountHandler`

#### **Queries** (`src/Application/Query/`)
Read operations that retrieve data:
- `GetAccountBalanceQuery` / `GetAccountBalanceHandler`
- `GetLedgerQuery` / `GetLedgerHandler`
- `GetTrialBalanceQuery` / `GetTrialBalanceHandler`

#### **DTOs** (`src/Application/Query/.../DTO/`)
Data Transfer Objects for query results:
- `AccountBalanceDTO`
- `LedgerPostingDTO`
- `TrialBalanceDTO`

### 3. Infrastructure Layer (`src/Infrastructure/`)

Concrete implementations of ports and technical concerns.

#### **Persistence** (`src/Infrastructure/Persistence/`)
- **EventStore**:
  - Uses `dranzd/common-event-sourcing` library for event store operations
  - `InMemoryEventStore` - In-memory implementation for testing/MVP
  - `EventSourcedJournalEntryRepository` - Event sourcing repository for journal entries
- **Projection**:
  - `LedgerProjection` - Projects journal entry events to ledger read model
  - `AccountBalanceProjection` - Maintains current account balances
- **ReadModel**:
  - `PDOLedgerReadModel` - Query implementation for ledger postings
  - `InMemoryReadModel` - In-memory implementation for testing
- **InMemory**: `InMemoryAccountRepository` - Example in-memory implementation for testing

**Note:** The MVP uses in-memory implementations. Production deployments should use PostgreSQL event store and read models with proper transaction management and optimistic concurrency control.

### 4. Shared Kernel (`src/Shared/`)

Common utilities and exceptions used across layers:
- `DomainException`
- `AggregateNotFoundException`

## Key Patterns

### Event Sourcing

Instead of storing current state, we store a sequence of events:

```php
// Events are the source of truth
StockItemCreated -> StockAdjusted -> StockReserved
```

The aggregate state is reconstructed by replaying events.

### CQRS

Separate models for reads and writes:

- **Write Model**: `StockItem` aggregate + Event Store
- **Read Model**: Projections in relational database for fast queries

### Hexagonal Architecture

```
┌─────────────────────────────────────────────┐
│         Application Layer (Use Cases)       │
│  Commands, Queries, Handlers                │
└──────────────┬──────────────────────────────┘
               │
    ┌──────────┴──────────┐
    │                     │
┌───▼────┐          ┌────▼────┐
│ Domain │          │  Ports  │ (Interfaces)
│ Model  │          │         │
└────────┘          └────┬────┘
                         │
              ┌──────────┴──────────┐
              │                     │
        ┌─────▼─────┐         ┌────▼────┐
        │Infrastructure│       │ Adapters│
        │ (Event Store)│       │  (PDO)  │
        └───────────────┘       └─────────┘
```

## Folder Structure

```
src/
├── Domain/
│   ├── Model/
│   │   └── Stock/
│   │       ├── StockItem.php (Aggregate Root)
│   │       ├── ValueObject/
│   │       │   ├── StockItemId.php
│   │       │   ├── ProductId.php
│   │       │   ├── LocationId.php
│   │       │   ├── Quantity.php
│   │       │   └── StockStatus.php
│   │       └── Event/
│   │           ├── StockItemCreated.php
│   │           ├── StockAdjusted.php
│   │           ├── StockReserved.php
│   │           └── StockReleased.php
│   └── Port/
│       ├── StockItemRepositoryInterface.php
│       └── StockItemReadModelInterface.php
├── Application/
│   ├── Command/
│   │   ├── CreateStockItem/
│   │   │   ├── CreateStockItemCommand.php
│   │   │   └── CreateStockItemHandler.php
│   │   ├── AdjustStock/
│   │   │   ├── AdjustStockCommand.php
│   │   │   └── AdjustStockHandler.php
│   │   └── ReserveStock/
│   │       ├── ReserveStockCommand.php
│   │       └── ReserveStockHandler.php
│   └── Query/
│       ├── GetStockItem/
│       │   ├── GetStockItemQuery.php
│       │   ├── GetStockItemHandler.php
│       │   └── DTO/
│       │       └── StockItemDTO.php
│       └── ListStockByLocation/
│           ├── ListStockByLocationQuery.php
│           └── ListStockByLocationHandler.php
├── Infrastructure/
│   └── Persistence/
│       ├── EventStore/
│       │   ├── EventStoreInterface.php (simple interface for testing)
│       │   ├── InMemoryEventStore.php (in-memory implementation)
│       │   └── EventSourcedStockItemRepository.php (example)
│       ├── Projection/
│       │   └── StockItemProjection.php
│       ├── ReadModel/
│       │   └── PDOStockItemReadModel.php
│       └── InMemoryStockRepository.php (example for testing)
└── Shared/
    └── Exception/
        ├── DomainException.php
        └── AggregateNotFoundException.php
```

## Usage Example

### Creating a Journal Entry

```php
// Command
$command = new CreateJournalEntryCommand(
    id: 'JE-001',
    date: new DateTime('2025-11-19'),
    description: 'Cash sale',
    lines: [
        ['accountId' => 'cash', 'amount' => 500.00, 'side' => 'debit'],
        ['accountId' => 'sales', 'amount' => 500.00, 'side' => 'credit']
    ]
);

// Handler
$handler = new CreateJournalEntryHandler($journalEntryRepository);
$handler->handle($command);
```

### Posting a Journal Entry

```php
// Command
$command = new PostJournalEntryCommand(
    journalEntryId: 'JE-001'
);

// Handler
$handler = new PostJournalEntryHandler($journalEntryRepository);
$handler->handle($command);
```

### Querying Account Balance

```php
// Query
$query = new GetAccountBalanceQuery(
    tenantId: 'T-1',
    accountId: 'cash'
);

// Handler
$handler = new GetAccountBalanceHandler($ledgerReadModel);
$balance = $handler->handle($query);

echo "Balance: " . $balance->balance;
```

## Business Rules

1. **Debits must equal credits** (fundamental accounting equation)
2. **Minimum 2 lines per journal entry** (double-entry bookkeeping)
3. **All entries must reference valid accounts** (referential integrity)
4. **Posted entries cannot be modified** (immutability for audit trail)
5. **All changes are event-driven** (complete audit trail via event sourcing)
6. **Multi-tenant isolation** (tenant-specific event streams and projections)

## Events

### Published Events
- `dranzd.storebunk.accounting.journal_entry.created`
- `dranzd.storebunk.accounting.journal_entry.posted`
- `dranzd.storebunk.accounting.ledger.posted`
- `dranzd.storebunk.accounting.account.created`

### Consumed Events (from other domains)
- `sales.sale.completed` - To record revenue and receivables
- `purchase.purchase.received` - To record expenses and payables
- `payment.payment.received` - To record cash receipts
- `payment.payment.made` - To record cash disbursements

## Dependencies

- `php: ^8.3`
- `dranzd/common-event-sourcing: dev-main` - Event sourcing infrastructure
- `dranzd/common-utils: dev-main` - Shared utilities
- `dranzd/common-domain-assert: dev-main` - Domain assertion helpers
- `dranzd/common-valueobject: dev-main` - Base value objects (Money, etc.)
- `dranzd/common-cqrs: dev-main` - Command/query bus infrastructure

## Testing

Run tests with PHPUnit:

```bash
./utils test
```

## Next Steps (Post-MVP)

1. Add persistent event store (PostgreSQL)
2. Implement trial balance and financial reports
3. Add journal entry reversal capability
4. Implement multi-currency support
5. Add fiscal periods and period closing
6. Implement account hierarchies (parent/child accounts)
7. Add opening balances
8. Implement idempotency and optimistic concurrency control
9. Add snapshot support for performance optimization
10. Build REST/GraphQL API layer
