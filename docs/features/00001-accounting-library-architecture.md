# Feature 00001: Accounting Library Architecture & Implementation Plan

**Status:** Planning
**Priority:** Critical
**Architecture:** DDD + Event Sourcing + Onion/Hexagonal
**Target:** Framework-agnostic accounting library for multi-tenant retail (StoreBunk)

---

## Executive Summary

This document defines the complete architecture and implementation plan for the Storebunk Accounting library. The library will be built using Domain-Driven Design (DDD), Event Sourcing (ES), and Onion/Hexagonal architecture patterns to ensure:

- **Domain-first design** with clear business rules and invariants
- **Event-sourced state** for complete audit trails and replayability
- **CQRS separation** between write and read models
- **Multi-tenant isolation** with per-tenant event streams and projections
- **Framework-agnostic core** that can integrate with any PHP application

---

## 1. Architectural Principles

### 1.1 Core Principles

#### Domain-First Design
- Design aggregates, entities, and invariants before any infrastructure concerns
- Business rules live in the domain layer, not in services or controllers
- Domain objects are framework-agnostic and testable in isolation

#### Event Sourcing
- All state changes are represented as immutable domain events
- Events are the source of truth; current state is derived from event history
- Examples: `JournalEntryCreated`, `JournalEntryPosted`, `LedgerPosted`
- Events enable complete audit trails and temporal queries

#### CQRS (Command Query Responsibility Segregation)
- **Command model (writes):** Handles commands, enforces invariants, emits events
- **Read models (queries):** Materialized projections optimized for specific queries
- Separate models allow independent scaling and optimization

#### Onion/Hexagonal Architecture
- **Domain core:** Entities, value objects, domain services, aggregates (innermost layer)
- **Application layer:** Command handlers, query services, use cases
- **Infrastructure layer:** Event store, projections, persistence, external adapters (outermost layer)
- Dependencies point inward; domain has zero external dependencies

#### Idempotency & Concurrency
- Commands use client-supplied idempotency keys to prevent duplicates
- Event store uses optimistic concurrency control via `expectedVersion`
- Projections track last processed event position for idempotent updates

#### Multi-Tenant Awareness
- Every event contains `tenantId` in metadata
- Event streams partitioned per tenant for isolation and scaling
- Read models tagged by tenant with row-level security where possible
- Cross-tenant operations are restricted and audited

#### Leveraging Common Libraries
This library builds upon existing Dranzd common libraries to avoid reinventing infrastructure:

- **`dranzd/common-event-sourcing`** - Provides event store interfaces, event bus, and event sourcing infrastructure
- **`dranzd/common-utils`** - Shared utilities for common operations
- **`dranzd/common-domain-assert`** - Domain assertion helpers for enforcing invariants
- **`dranzd/common-valueobject`** - Base value object implementations (Money, DateTime, etc.)
- **`dranzd/common-cqrs`** - Command and query bus infrastructure, command handlers

These libraries provide battle-tested implementations of infrastructure concerns, allowing this library to focus on accounting domain logic.

---

## 2. Implementation Phases & Milestones

### Phase 0: Kickoff & Domain Specification
**Goal:** Establish domain understanding and vocabulary

**Deliverables:**
- Domain glossary defining all accounting terms
- List of domain invariants (e.g., debits must equal credits)
- Example business flows (sales, purchases, payments)
- Complete event catalog with descriptions

**Acceptance Criteria:**
- Domain model document approved
- Event list documented with payload structures
- All stakeholders aligned on terminology

---

### Phase 1: Core Domain & Aggregates
**Goal:** Implement domain objects with business rules

**Deliverables:**
- `JournalEntry` aggregate root with invariant enforcement
- `JournalLine` value object
- `Account` entity and `ChartOfAccounts` aggregate
- `LedgerAccount` aggregate (projection target)
- Domain services: `JournalPostingService`

**Acceptance Criteria:**
- Unit tests verify all invariants (e.g., debits == credits)
- Aggregates enforce business rules correctly
- Value objects are immutable
- No infrastructure dependencies in domain layer

**Key Invariants to Test:**
- Journal entry must have at least 2 lines
- Sum of debits must equal sum of credits
- Account codes must be unique within a chart of accounts
- Journal entries cannot be modified after posting

---

### Phase 2: Event Store Adapter & Event Sourcing Plumbing
**Goal:** Establish event persistence and replay capability

**Deliverables:**
- Event store interface definition
- In-memory event store implementation (for testing)
- Event serialization/deserialization
- Event envelope with metadata (eventId, eventType, schemaVersion, timestamp, tenantId)
- Aggregate rehydration from event stream

**Event Store Interface Methods:**
```php
interface EventStore
{
    public function appendToStream(
        string $streamId,
        int $expectedVersion,
        array $events
    ): int;

    public function readStream(
        string $streamId,
        int $fromVersion = 0,
        int $batchSize = 100
    ): array;

    public function readAll(
        ?string $streamName = null,
        ?int $fromPosition = null
    ): array;

    public function createSnapshot(
        string $streamId,
        int $version,
        array $snapshotPayload
    ): void;

    public function loadSnapshot(string $streamId): ?array;
}
```

**Stream Naming Strategy:**
- Per-tenant streams: `journal-{tenantId}`, `accounts-{tenantId}`
- Or per-aggregate: `journal-entry-{journalEntryId}`, `account-{accountId}`

**Acceptance Criteria:**
- Can append events to a stream
- Can read events from a stream with version control
- Can replay events to rebuild aggregate state
- Optimistic concurrency violations are detected and handled
- In-memory implementation passes all tests

---

### Phase 3: Application Services & Command Handlers
**Goal:** Implement command processing pipeline

**Deliverables:**
- Command objects (DTOs)
- Command handlers
- Command bus/dispatcher
- Idempotency handling

**Commands:**
- `CreateJournalEntry` - Create a new journal entry in draft state
- `PostJournalEntry` - Post a journal entry to the ledger
- `ReverseJournalEntry` - Create a reversing entry
- `CreateAccount` - Add a new account to chart of accounts
- `UpdateAccount` - Modify account properties (if not posted to)
- `CreateChartOfAccounts` - Initialize chart for a tenant

**Command Handler Responsibilities:**
1. Validate command structure and required fields
2. Load aggregate from event store (rehydrate)
3. Execute domain method on aggregate
4. Collect emitted domain events
5. Append events to event store
6. Handle concurrency conflicts

**Acceptance Criteria:**
- End-to-end tests: send command → events persisted → aggregate state updated
- Idempotency: duplicate commands with same key produce no additional events
- Concurrency: simultaneous commands on same aggregate are handled correctly
- Validation errors are returned without persisting events

---

### Phase 4: Projections (Ledgers & Reports)
**Goal:** Materialize read models for queries

**Deliverables:**
- Projection handler framework
- `LedgerAccount` projection (account balances and posting history)
- `TrialBalance` projection (summary of all account balances)
- `IncomeStatement` projection (revenue and expenses for a period)
- `BalanceSheet` projection (assets, liabilities, equity at a point in time)
- Projection rebuild utilities

**Projection Handler Pattern:**
```php
interface ProjectionHandler
{
    public function handle(DomainEvent $event): void;
    public function getLastProcessedPosition(): int;
    public function setLastProcessedPosition(int $position): void;
    public function rebuild(): void;
}
```

**Read Model Schema (PostgreSQL example):**

```sql
-- Ledger postings (detailed transaction log)
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
    INDEX idx_tenant_account (tenant_id, account_id, date),
    INDEX idx_tenant_entry (tenant_id, entry_id)
);

-- Trial balance (current balances)
CREATE TABLE trial_balance (
    tenant_id VARCHAR(50) NOT NULL,
    account_id VARCHAR(50) NOT NULL,
    account_code VARCHAR(50) NOT NULL,
    account_name VARCHAR(255) NOT NULL,
    account_type VARCHAR(50) NOT NULL,
    debit_total DECIMAL(19,4) NOT NULL DEFAULT 0,
    credit_total DECIMAL(19,4) NOT NULL DEFAULT 0,
    balance DECIMAL(19,4) NOT NULL DEFAULT 0,
    updated_at TIMESTAMP NOT NULL,
    PRIMARY KEY (tenant_id, account_id),
    INDEX idx_tenant_type (tenant_id, account_type)
);
```

**Acceptance Criteria:**
- Projections update correctly when events are applied
- Projections produce identical results when rebuilt from scratch
- Projection lag is monitored and acceptable
- Failed event processing is logged and can be retried
- Projections are idempotent (processing same event twice has no effect)

---

### Phase 5: Storage Adapters & Integrations
**Goal:** Implement persistent storage

**Deliverables:**
- PostgreSQL event store adapter
- PostgreSQL read model repositories
- Redis snapshot store (optional, for performance)
- Database migration scripts
- Connection pooling and transaction management

**PostgreSQL Event Store Schema:**

```sql
CREATE TABLE events (
    id BIGSERIAL PRIMARY KEY,
    stream_id VARCHAR(255) NOT NULL,
    version INT NOT NULL,
    event_type VARCHAR(255) NOT NULL,
    event_data JSONB NOT NULL,
    metadata JSONB NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    UNIQUE (stream_id, version),
    INDEX idx_stream (stream_id, version),
    INDEX idx_created (created_at),
    INDEX idx_tenant (((metadata->>'tenantId')))
);

CREATE TABLE snapshots (
    stream_id VARCHAR(255) PRIMARY KEY,
    version INT NOT NULL,
    snapshot_data JSONB NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE TABLE projection_positions (
    projection_name VARCHAR(255) PRIMARY KEY,
    position BIGINT NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);
```

**Acceptance Criteria:**
- Events persist across application restarts
- Event store handles concurrent writes correctly
- Snapshots reduce aggregate rehydration time
- Read models persist and can be queried efficiently
- Database migrations are versioned and repeatable

---

### Phase 6: API & SDK
**Goal:** Expose library functionality to consumers

**Deliverables:**
- SDK interface for commands and queries
- Event subscription mechanism
- REST adapter example (optional, for reference)
- gRPC adapter example (optional, for reference)
- Client library documentation

**SDK Interface:**

```php
// Command API
interface AccountingCommands
{
    public function send(Command $command, ?string $idempotencyKey = null): CommandResult;
}

// Query API
interface AccountingQueries
{
    public function getLedger(
        string $tenantId,
        string $accountId,
        ?DateTimeInterface $fromDate = null,
        ?DateTimeInterface $toDate = null
    ): LedgerView;

    public function getTrialBalance(
        string $tenantId,
        DateTimeInterface $asOfDate
    ): TrialBalanceView;
}

// Event subscription API
interface AccountingEvents
{
    public function subscribe(string $eventType, callable $handler): void;
    public function subscribeAll(callable $handler): void;
}
```

**Acceptance Criteria:**
- Third-party application can create a sale and see ledger updates
- SDK is framework-agnostic and easy to integrate
- REST/gRPC adapters demonstrate integration patterns
- Event subscriptions work for external systems

---

### Phase 7: Hardening
**Goal:** Production readiness

**Deliverables:**
- Multi-tenant security enforcement
- Authentication and authorization integration points
- Audit logging (command metadata: userId, actor, source, IP, correlationId)
- Event schema versioning and migration handlers
- Performance monitoring and metrics
- Backup and restore procedures
- Load testing and optimization
- Error handling and dead-letter queue

**Acceptance Criteria:**
- Meets all non-functional requirements (performance, security, reliability)
- Production deployment runbook completed
- Disaster recovery procedures tested
- Performance benchmarks met
- Security audit passed

---

_Continued in next section..._
