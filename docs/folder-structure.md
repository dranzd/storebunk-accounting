# Folder Structure Reference

## Complete Directory Tree

```
storebunk-accounting/
├── src/
│   ├── Domain/                          # Core business logic
│   │   ├── Accounting/                  # Accounting domain models
│   │   │   ├── JournalEntry.php         # Aggregate Root
│   │   │   ├── Account.php              # Entity
│   │   │   ├── JournalLine.php          # Value Object
│   │   │   ├── AccountType.php          # Enum
│   │   │   ├── Side.php                 # Enum (debit/credit)
│   │   │   ├── EntryStatus.php          # Enum
│   │   │   └── Events/                  # Domain events
│   │   │       ├── DomainEvent.php
│   │   │       ├── JournalEntryCreated.php
│   │   │       ├── JournalEntryPosted.php
│   │   │       ├── LedgerPosted.php
│   │   │       └── AccountCreated.php
│   │   └── Port/                        # Interfaces (Hexagonal Architecture)
│   │       ├── JournalEntryRepositoryInterface.php
│   │       ├── AccountRepositoryInterface.php
│   │       └── LedgerReadModelInterface.php
│   │
│   ├── Application/                     # Use cases and orchestration
│   │   ├── Command/                     # Write operations (CQRS)
│   │   │   ├── CreateJournalEntry/
│   │   │   │   ├── CreateJournalEntryCommand.php
│   │   │   │   └── CreateJournalEntryHandler.php
│   │   │   ├── PostJournalEntry/
│   │   │   │   ├── PostJournalEntryCommand.php
│   │   │   │   └── PostJournalEntryHandler.php
│   │   │   └── CreateAccount/
│   │   │       ├── CreateAccountCommand.php
│   │   │       └── CreateAccountHandler.php
│   │   └── Query/                       # Read operations (CQRS)
│   │       ├── GetAccountBalance/
│   │       │   ├── GetAccountBalanceQuery.php
│   │       │   ├── GetAccountBalanceHandler.php
│   │       │   └── DTO/
│   │       │       └── AccountBalanceDTO.php
│   │       ├── GetLedger/
│   │       │   ├── GetLedgerQuery.php
│   │       │   ├── GetLedgerHandler.php
│   │       │   └── DTO/
│   │       │       └── LedgerPostingDTO.php
│   │       └── GetTrialBalance/
│   │           ├── GetTrialBalanceQuery.php
│   │           └── GetTrialBalanceHandler.php
│   │
│   ├── Infrastructure/                  # Technical implementations
│   │   ├── Persistence/
│   │   │   ├── EventStore/
│   │   │   │   ├── InMemoryEventStore.php
│   │   │   │   └── EventSourcedJournalEntryRepository.php
│   │   │   ├── Projection/
│   │   │   │   ├── LedgerProjection.php
│   │   │   │   └── AccountBalanceProjection.php
│   │   │   ├── ReadModel/
│   │   │   │   ├── InMemoryReadModel.php
│   │   │   │   └── PDOLedgerReadModel.php
│   │   │   └── Repository/
│   │   │       └── InMemoryAccountRepository.php
│   │   └── CommandBus/
│   │       └── SimpleCommandBus.php
│   │
│   └── Shared/                          # Common utilities
│       └── Exception/
│           ├── DomainException.php
│           └── AggregateNotFoundException.php
│
├── tests/
│   └── Unit/
│       └── Domain/
│           └── Model/
│               └── Stock/
│                   └── StockItemTest.php
│
├── database/
│   └── migrations/
│       └── 001_create_stock_items_table.sql
│
├── docs/
│   ├── README.md
│   ├── api-reference.md
│   ├── architecture.md
│   └── features/
│
├── composer.json
├── phpunit.xml
├── README.md
├── README_ARCHITECTURE.md
├── SETUP.md
└── CHANGELOG.md
```

## Layer Responsibilities

### Domain Layer
- **Pure business logic** - No framework dependencies
- **Aggregates** enforce business rules and invariants
- **Value Objects** are immutable and self-validating
- **Events** represent facts that happened
- **Ports** define contracts without implementation

### Application Layer
- **Orchestrates** domain objects to fulfill use cases
- **Commands** represent intentions to change state
- **Queries** represent requests for data
- **Handlers** execute commands and queries
- **DTOs** transfer data across boundaries

### Infrastructure Layer
- **Implements** ports with concrete technology
- **Event Store** persists event streams
- **Projections** build read models from events
- **Adapters** connect to external systems

### Shared Kernel
- **Exceptions** for domain errors
- **Utilities** used across layers
- **Common types** and interfaces

## Naming Conventions

### Files
- **Aggregates**: `{Name}.php` (e.g., `JournalEntry.php`)
- **Entities**: `{Name}.php` (e.g., `Account.php`)
- **Value Objects**: `{Name}.php` (e.g., `JournalLine.php`, `Money.php`)
- **Enums**: `{Name}.php` (e.g., `AccountType.php`, `Side.php`)
- **Events**: `{Entity}{Action}ed.php` (e.g., `JournalEntryCreated.php`, `JournalEntryPosted.php`)
- **Commands**: `{Action}{Entity}Command.php` (e.g., `CreateJournalEntryCommand.php`)
- **Handlers**: `{Action}{Entity}Handler.php` (e.g., `CreateJournalEntryHandler.php`)
- **Queries**: `Get{Entity}Query.php` (e.g., `GetAccountBalanceQuery.php`)
- **DTOs**: `{Entity}DTO.php` (e.g., `AccountBalanceDTO.php`)
- **Interfaces**: `{Name}Interface.php` (e.g., `JournalEntryRepositoryInterface.php`)

### Namespaces
- Domain: `Dranzd\StorebunkAccounting\Domain\Accounting`
- Application: `Dranzd\StorebunkAccounting\Application\{Command|Query}\{UseCase}`
- Infrastructure: `Dranzd\StorebunkAccounting\Infrastructure\{Technology}`

## Adding New Features

### New Aggregate
1. Create aggregate in `src/Domain/Accounting/{Aggregate}.php`
2. Add value objects in `src/Domain/Accounting/` (if needed)
3. Add events in `src/Domain/Accounting/Events/`
4. Create repository interface in `src/Domain/Port/`
5. Implement repository in `src/Infrastructure/Persistence/EventStore/`

### New Use Case
1. Create command/query in `src/Application/{Command|Query}/{UseCase}/`
2. Create handler in same directory
3. Add DTO if needed for queries (in `DTO/` subdirectory)
4. Wire up in command/query bus

### New Projection
1. Create projection class in `src/Infrastructure/Persistence/Projection/`
2. Subscribe to relevant domain events
3. Update read model schema (SQL migrations or in-memory structure)
4. Implement projection handler methods

### New Query
1. Create query in `src/Application/Query/{QueryName}/`
2. Create DTO for result in `DTO/` subdirectory
3. Create handler that uses read model interface
4. Register in query bus
