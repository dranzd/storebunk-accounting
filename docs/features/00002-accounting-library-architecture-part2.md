# Feature 00001: Accounting Library Architecture (Part 2)

**This is a continuation of `00001-accounting-library-architecture.md`**

---

## 3. Bounded Contexts & Responsibilities

### Accounting Core (Domain Layer)
**Responsibilities:**
- Chart of Accounts management
- Journal Entry aggregate with invariant enforcement
- Account entity and business rules
- Domain events definition
- Domain services (posting, validation)

**Does NOT contain:**
- Persistence logic
- API endpoints
- Framework-specific code
- Infrastructure concerns

---

### Sales / Purchasing / Payments (External Modules)
**Responsibilities:**
- Generate accounting commands based on business transactions
- Produce domain events that trigger accounting entries
- Examples:
  - Sale completed → CreateJournalEntry command (debit Cash, credit Sales)
  - Purchase received → CreateJournalEntry command (debit Inventory, credit Accounts Payable)
  - Payment received → CreateJournalEntry command (debit Cash, credit Accounts Receivable)

**Integration Pattern:**
- External modules send commands to accounting via SDK
- Accounting publishes events that external modules can subscribe to

---

### Projection & Reporting (Read Side)
**Responsibilities:**
- Subscribe to domain events
- Materialize ledgers (detailed posting history)
- Compute trial balance (account balances summary)
- Generate financial statements (income statement, balance sheet, cash flow)
- Optimize read models for specific query patterns

**Key Projections:**
1. **LedgerAccount Projection** - Detailed posting history per account
2. **Trial Balance Projection** - Current balances for all accounts
3. **Income Statement Projection** - Revenue and expenses for a period
4. **Balance Sheet Projection** - Assets, liabilities, equity at a point in time
5. **Cash Flow Projection** - Cash movements categorized by activity

---

### Infrastructure (Adapters)
**Responsibilities:**
- Event Store implementation (PostgreSQL, EventStoreDB, etc.)
- Snapshot Store implementation (Redis, PostgreSQL)
- Read DB implementation (PostgreSQL, MySQL)
- Message Bus integration (RabbitMQ, Kafka, AWS SQS)
- Authentication/Authorization adapters
- Logging and monitoring

---

## 4. Domain Model Specification

### 4.1 Aggregates

#### JournalEntry (Aggregate Root)

**Properties:**
- `id` (string) - Unique identifier (e.g., "JE-001")
- `journalType` (enum) - Type of journal: general, sales, purchase, cashReceipt, cashDisbursement
- `date` (DateTimeInterface) - Transaction date
- `description` (string) - Human-readable description
- `lines` (array<JournalLine>) - Collection of journal lines
- `metadata` (array) - Additional context (tenantId, actorId, source, etc.)
- `postedAt` (DateTimeInterface|null) - When the entry was posted (null if draft)
- `status` (enum) - draft, posted, reversed

**Invariants:**
1. Must have at least 2 lines
2. Sum of debits must equal sum of credits
3. Cannot modify after posting
4. All lines must reference valid accounts
5. Date cannot be in the future (business rule)

**Methods:**
- `create(...)` - Factory method to create new entry
- `addLine(JournalLine $line)` - Add a line (only if draft)
- `removeLine(int $index)` - Remove a line (only if draft)
- `post()` - Mark as posted (validates invariants)
- `reverse(string $reason)` - Create reversing entry

**Domain Events:**
- `JournalEntryCreated` - Entry created in draft state
- `JournalLineAdded` - Line added to entry
- `JournalLineRemoved` - Line removed from entry
- `JournalEntryPosted` - Entry posted to ledger
- `JournalEntryReversed` - Entry reversed

---

#### Account (Entity in ChartOfAccounts)

**Properties:**
- `accountId` (string) - Unique identifier
- `code` (string) - Account code (e.g., "1000", "4000")
- `name` (string) - Account name (e.g., "Cash", "Sales Revenue")
- `type` (enum) - Asset, Liability, Equity, Revenue, Expense
- `parentId` (string|null) - Parent account for hierarchical structure
- `openingBalance` (Money) - Initial balance
- `isActive` (bool) - Whether account is active
- `metadata` (array) - Additional properties

**Invariants:**
1. Account code must be unique within chart of accounts
2. Account type cannot change after posting
3. Parent account must exist and be of compatible type
4. Cannot delete account with postings

**Methods:**
- `create(...)` - Factory method
- `deactivate()` - Mark as inactive
- `updateName(string $name)` - Change name
- `setParent(string $parentId)` - Set parent account

**Domain Events:**
- `AccountCreated` - New account added to chart
- `AccountUpdated` - Account properties changed
- `AccountDeactivated` - Account marked inactive

---

### 4.2 Value Objects

#### JournalLine

**Properties:**
- `accountId` (string) - Reference to account
- `amount` (Money) - Transaction amount
- `side` (enum) - debit or credit
- `currency` (string) - Currency code (ISO 4217)
- `reference` (string|null) - External reference (invoice #, etc.)

**Invariants:**
1. Amount must be positive
2. Side must be debit or credit
3. Account ID must reference valid account

**Methods:**
- Value object is immutable
- Equality based on all properties

---

#### Money

**Properties:**
- `amount` (string|int) - Amount in smallest currency unit
- `currency` (string) - Currency code

**Methods:**
- `add(Money $other): Money`
- `subtract(Money $other): Money`
- `multiply(float $factor): Money`
- `equals(Money $other): bool`
- `isZero(): bool`
- `isPositive(): bool`
- `isNegative(): bool`

---

### 4.3 Domain Services

#### JournalPostingService

**Responsibilities:**
- Validate journal entry before posting
- Generate ledger posting events
- Enforce posting rules (e.g., period lock, approval workflow)

**Methods:**
```php
public function validateForPosting(JournalEntry $entry): ValidationResult;
public function generateLedgerPostings(JournalEntry $entry): array<LedgerPosted>;
```

---

#### ExchangeService (Multi-Currency)

**Responsibilities:**
- Convert amounts between currencies
- Apply exchange rates
- Tag entries with exchange rate metadata

**Methods:**
```php
public function convert(Money $amount, string $toCurrency, DateTimeInterface $date): Money;
public function getRate(string $fromCurrency, string $toCurrency, DateTimeInterface $date): float;
```

---

### 4.4 Domain Events

#### Event Envelope Structure

```json
{
  "eventId": "evt-uuid-123",
  "eventType": "JournalEntryCreated",
  "schemaVersion": "1.0",
  "streamId": "journal-T-1",
  "version": 5,
  "timestamp": "2025-11-08T10:30:00Z",
  "metadata": {
    "tenantId": "T-1",
    "actorId": "user-7",
    "source": "pos-1",
    "correlationId": "corr-abc-123",
    "causationId": "cmd-abc-123"
  },
  "payload": {
    // Event-specific data
  }
}
```

#### Event Catalog

**JournalEntryCreated**
```json
{
  "type": "JournalEntryCreated",
  "payload": {
    "id": "JE-001",
    "journalType": "sales",
    "date": "2025-11-08",
    "description": "Cash sale POS-123",
    "lines": [
      {"accountId": "cash", "side": "debit", "amount": 500, "currency": "USD"},
      {"accountId": "sales", "side": "credit", "amount": 500, "currency": "USD"}
    ]
  }
}
```

**JournalEntryPosted**
```json
{
  "type": "JournalEntryPosted",
  "payload": {
    "id": "JE-001",
    "postedAt": "2025-11-08T09:00:00Z"
  }
}
```

**LedgerPosted**
```json
{
  "type": "LedgerPosted",
  "payload": {
    "accountId": "A-100",
    "entryId": "JE-001",
    "amount": 500,
    "side": "debit",
    "balance": 1500,
    "date": "2025-11-08"
  }
}
```

**AccountCreated**
```json
{
  "type": "AccountCreated",
  "payload": {
    "accountId": "A-100",
    "code": "1000",
    "name": "Cash",
    "type": "Asset",
    "parentId": null,
    "openingBalance": 0
  }
}
```

---

## 5. Commands & Command Handlers

### 5.1 Command Definitions

#### CreateJournalEntry
```php
class CreateJournalEntry
{
    public string $journalType;
    public DateTimeInterface $date;
    public string $description;
    public array $lines; // array of JournalLine data
    public array $metadata;
}
```

#### PostJournalEntry
```php
class PostJournalEntry
{
    public string $journalEntryId;
}
```

#### ReverseJournalEntry
```php
class ReverseJournalEntry
{
    public string $journalEntryId;
    public string $reason;
    public DateTimeInterface $reversalDate;
}
```

#### CreateAccount
```php
class CreateAccount
{
    public string $code;
    public string $name;
    public string $type; // Asset|Liability|Equity|Revenue|Expense
    public ?string $parentId;
    public float $openingBalance;
}
```

---

### 5.2 Command Handler Responsibilities

1. **Validate command structure**
   - Required fields present
   - Data types correct
   - Business rule validation

2. **Load aggregate from event store**
   - Read event stream
   - Rehydrate aggregate state
   - Apply snapshot if available

3. **Execute domain method**
   - Call appropriate method on aggregate
   - Aggregate enforces invariants
   - Aggregate emits domain events

4. **Persist events**
   - Append events to event store
   - Handle optimistic concurrency conflicts
   - Retry or reject on conflict

5. **Return result**
   - Success with event IDs
   - Failure with validation errors
   - Conflict with retry instructions

---

## 6. Multi-Tenant Considerations

### 6.1 Tenant Isolation

- **Event level:** Every event contains `tenantId` in metadata
- **Stream level:** Per-tenant streams recommended
- **Read model level:** All tables have `tenant_id` column
- **Query level:** Always filter by `tenant_id`

### 6.2 Data Isolation Enforcement

- Row-level security in PostgreSQL
- Application-level tenant context
- API authentication includes tenant ID
- Cross-tenant queries explicitly forbidden

### 6.3 Scaling Strategy

- Partition event store by tenant
- Separate projection workers per tenant (or tenant group)
- Horizontal scaling by tenant sharding

---

## 7. Event Versioning & Schema Evolution

### 7.1 Event Schema Versioning

Every event includes `schemaVersion`:
```json
{
  "eventType": "JournalEntryCreated",
  "schemaVersion": "1.0",
  "payload": { ... }
}
```

### 7.2 Handling Schema Changes

**Additive changes (safe):**
- Add optional fields to payload
- Increment minor version (1.0 → 1.1)
- Old events still valid

**Breaking changes (requires migration):**
- Remove or rename fields
- Change field types
- Increment major version (1.0 → 2.0)
- Implement event upcasters in projections

### 7.3 Event Upcasters

Transform old event format to new format during replay:
```php
class JournalEntryCreatedUpcaster
{
    public function upcast(array $event): array
    {
        if ($event['schemaVersion'] === '1.0') {
            // Transform to 2.0 format
            $event['payload']['newField'] = 'default';
            $event['schemaVersion'] = '2.0';
        }
        return $event;
    }
}
```

---

## 8. Idempotency & Concurrency

### 8.1 Command Idempotency

- Client supplies `idempotencyKey` with each command
- Store processed keys in `processed_commands` table
- If key exists, return cached result without re-processing

```sql
CREATE TABLE processed_commands (
    idempotency_key VARCHAR(255) PRIMARY KEY,
    command_type VARCHAR(255) NOT NULL,
    result JSONB NOT NULL,
    processed_at TIMESTAMP NOT NULL DEFAULT NOW()
);
```

### 8.2 Event Store Concurrency

- Use `expectedVersion` parameter when appending
- Event store checks current version matches expected
- If mismatch → `WrongExpectedVersionException`
- Handler can retry after rehydrating latest state

### 8.3 Projection Idempotency

- Track last processed event position
- Check if event already processed before applying
- Use database transactions for atomic updates

---

## 9. Security & Audit

### 9.1 Audit Trail

- Events are the audit trail
- Store command metadata: userId, actor, source, IP, correlationId
- Read-only access to event store for auditors
- Immutable events provide tamper-proof history

### 9.2 Sensitive Data

- Encrypt sensitive fields if needed (PII)
- Or store separately with references
- Apply data retention policies

### 9.3 Access Control

- Authentication at API layer
- Authorization checks in command handlers
- Tenant isolation enforced at all layers

---

## 10. Testing Strategy

### 10.1 Unit Tests

- Domain invariants (debits == credits)
- Account creation and validation
- Value object immutability
- Aggregate state transitions

### 10.2 Integration Tests

- Command → events → projection updated
- Event store persistence
- Concurrency handling
- Idempotency verification

### 10.3 Property Tests

- Random journal lines that must balance
- Fuzz testing for edge cases

### 10.4 Replay Tests

- Clear projections
- Replay full event store
- Compare with expected balances

### 10.5 Performance Tests

- Projection throughput
- Event store append latency
- Query response times

---

## 11. Operational Considerations

### 11.1 Event Store Backing

- Durable storage (Postgres, EventStoreDB, cloud stream)
- Regular backups
- Replication for high availability

### 11.2 Projection Workers

- Run as separate services
- Horizontal scaling by partitioning
- Monitor projection lag
- Dead-letter queue for failed events

### 11.3 Monitoring

- Event throughput metrics
- Projection lag alerts
- Failed event processing
- Command latency tracking
- Query performance

### 11.4 Onboarding

- Migration/importer for initial Chart of Accounts
- Opening balance entries (as events)
- Tenant provisioning workflow

---

## 12. Example Command Flow

### Sale → Cash Receipt

1. **POS module issues CreateJournalEntry command:**

```json
{
  "type": "CreateJournalEntry",
  "payload": {
    "journalType": "cashReceipts",
    "date": "2025-11-08",
    "description": "Cash sale POS-123",
    "lines": [
      {"accountId": "cash", "side": "debit", "amount": 500},
      {"accountId": "sales", "side": "credit", "amount": 500}
    ],
    "metadata": {
      "tenantId": "T-1",
      "actorId": "user-7",
      "source": "pos-1"
    }
  },
  "idempotencyKey": "cmd-abc-123"
}
```

2. **Command handler validates, emits `JournalEntryCreated` event**

3. **Business rule triggers `JournalEntryPosted` event (immediate posting)**

4. **Projection worker consumes events:**
   - Writes to `ledger_postings` table
   - Updates `trial_balance` table
   - Updates account balances

---

## 13. Acceptance Criteria Summary

- ✓ `CreateJournalEntry` command results in `JournalEntryCreated` event
- ✓ Invariants are enforced (debits == credits)
- ✓ Events replay produces same balances as incremental processing
- ✓ Projections are idempotent
- ✓ Projections can recover from failures
- ✓ Tenant isolation is enforced
- ✓ Audit metadata is captured per event

---

## 14. Deliverables Checklist

- [ ] Domain model files + tests for aggregates & invariants
- [ ] Event schema document (JSON schema for every event)
- [ ] Event store adapter (in-memory + one persistent)
- [ ] Projection handlers for Ledger and Trial Balance
- [ ] Command handlers and minimal API adapter
- [ ] Replay & snapshot utilities
- [ ] Documentation: domain glossary, developer guide, onboarding guide

---

## 15. Risks & Mitigations

| Risk | Mitigation |
|------|------------|
| Event schema changes break projections | Use schemaVersion & migration handlers |
| Projection lag causes stale reports | Monitor lag, horizontal scaling |
| Incorrect manual journal entries | Validation rules, role-based restrictions, approval workflow |
| Data loss in event store | Regular backups, replication, disaster recovery plan |
| Performance degradation | Snapshotting, caching, query optimization |

---

## 16. Folder Structure (PHP)

```
/storebunk-accounting
  /src
    /Domain
      /Accounting
        JournalEntry.php
        JournalLine.php
        Account.php
        ChartOfAccounts.php
        Money.php
        /Events
          JournalEntryCreated.php
          JournalEntryPosted.php
          LedgerPosted.php
          AccountCreated.php
        /Services
          JournalPostingService.php
          ExchangeService.php
    /Application
      /Commands
        CreateJournalEntry.php
        PostJournalEntry.php
        ReverseJournalEntry.php
        CreateAccount.php
      /Handlers
        CreateJournalEntryHandler.php
        PostJournalEntryHandler.php
      /Queries
        LedgerQueryService.php
        TrialBalanceQueryService.php
    /Infrastructure
      /EventStore
        EventStoreInterface.php
        InMemoryEventStore.php
        PostgresEventStore.php
      /Projections
        LedgerProjection.php
        TrialBalanceProjection.php
        IncomeStatementProjection.php
        BalanceSheetProjection.php
      /Persistence
        ReadModelRepository.php
    /Api
      RestAdapter.php (example)
  /tests
    /Domain
    /Integration
    /Performance
  /docs
    /features
    /discussions
  composer.json
  phpunit.xml
  docker-compose.yml
```

---

## Next Steps

This plan is now ready for Phase 0 execution. The next document to create should be:

**`docs/features/00002-domain-glossary.md`** - Comprehensive domain glossary defining all accounting terms, invariants, and business rules.

After the glossary, proceed with:
- Event schema specifications
- Aggregate detailed design documents
- Test specifications for each phase
