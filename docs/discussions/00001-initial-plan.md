Nice — got you, Pre. Below is a copy-paste-ready **Markdown implementation plan** for a framework-agnostic accounting library built with **DDD + Event Sourcing + Onion/Hexagonal** architecture. It’s written so another AI or engineer can take it and start implementing.

Paste away.

---

# Accounting Library — Implementation Plan (DDD + Event Sourcing + Onion/Hexagonal)

> Scope: framework-agnostic accounting library oriented for multi-tenant retail (e.g., StoreBunk).
> Goals: domain-first, event-sourced, testable, replayable ledgers, clear bounded contexts (journals, ledgers, chart-of-accounts, reporting).

---

## 1. High-level approach & principles

* **Domain-First**: Design aggregates and invariants before persistence or APIs.
* **Event Sourcing**: All state changes represented as immutable domain events (JournalEntryCreated, JournalEntryPosted, etc.).
* **CQRS**: Command model (writes) separate from read models (ledgers, trial balance, statements).
* **Onion/Hexagonal**: Domain core (entities, value objects, domain services, aggregates) isolated from application logic and infrastructure adapters.
* **Idempotency & Concurrency**: Strong handling for duplicate commands and optimistic concurrency via event stream versions.
* **Multi-tenant aware**: Tenant id in events/streams and projections; partitioning strategies supported.

---

## 2. Project phases & milestones

1. **Phase 0 — Kickoff & Domain Spec**

   * Deliver domain glossary, invariants, example flows (sales, purchase, payment).
   * Acceptance: domain model doc + event list.

2. **Phase 1 — Core Domain & Aggregates**

   * Implement domain objects (JournalEntry aggregate, JournalLine VO, ChartOfAccounts, LedgerAccount aggregate).
   * Acceptance: unit tests for invariants (e.g., debits == credits).

3. **Phase 2 — Event Store Adapter & Basic ES plumbing**

   * Define event schemas, create a simple in-memory and pluggable event store interface (append, read, read-from-version, snapshot support).
   * Acceptance: can persist and replay events in-memory.

4. **Phase 3 — Application Services & Command Handlers**

   * Command model (CreateJournalEntry, PostJournalEntry, ReverseJournalEntry, CreateAccount).
   * Acceptance: end-to-end tests sending commands produce events.

5. **Phase 4 — Projections (Ledgers & Reports)**

   * Implement projections: LedgerAccount projection, Trial Balance, Income Statement, Balance Sheet.
   * Use projection handlers that subscribe to event streams.
   * Acceptance: projections update correctly when events are applied and on replay.

6. **Phase 5 — Storage Adapters & Integrations**

   * Implement adapters for persistent event store (e.g., PostgreSQL event table), projection DB (Postgres, read optimized), and optional snapshot store (Redis).
   * Acceptance: data persists across process restarts.

7. **Phase 6 — API & SDK**

   * Expose a minimal SDK: commands, queries, event subscription hooks. Provide REST/GRPC examples (adapter layer only).
   * Acceptance: third-party app can create a sale -> ledger updates.

8. **Phase 7 — Hardening**

   * Add multi-tenant, security, auditing, migrations, versioning of events, monitoring, backup/restore, performance tuning.
   * Acceptance: meet non-functional requirements.

---

## 3. Bounded contexts & responsibilities

* **Accounting Core** (domain layer)

  * Chart of Accounts, JournalEntry aggregate, LedgerAccount projection, domain events.
* **Sales / Purchasing / Payments** (outside modules)

  * Produce accounting commands/events that post to accounting journals.
* **Projection & Reporting**

  * Subscribes to events, materializes ledgers, trial balance, financial statements.
* **Infrastructure**

  * Event Store, Snapshot Store, Read DB, Message Bus, Authentication/Authorization.

---

## 4. Domain model (concise)

### Entities / Aggregates

* `JournalEntry` (Aggregate Root)

  * id, journalType (general, sales, purchase, cashReceipt, cashDisbursement), date, description, lines[], metadata, postedAt?, status
  * invariants: lines.length >= 2; sum(debits) == sum(credits)
* `JournalLine` (Value Object)

  * accountId, amount, side (debit|credit), currency, reference
* `Account` (Entity in ChartOfAccounts)

  * accountId, code, name, type (Asset|Liability|Equity|Revenue|Expense), parentId?, openingBalance
* `LedgerAccount` (Projection)

  * accountId, postings[], balance, currency

### Domain Services

* `JournalPostingService` — validates and generates posting events to ledger.
* `ExchangeService` (if multicurrency) — for converting entries or tagging.

### Domain Events (examples)

```json
{ "type": "JournalEntryCreated", "payload": { "id":"JE-001","journalType":"sales","date":"2025-11-08","lines":[...] , "tenantId": "T-1" } }
{ "type": "JournalEntryPosted", "payload": { "id":"JE-001","postedAt":"2025-11-08T09:00Z","tenantId":"T-1" } }
{ "type": "LedgerPosted", "payload": { "accountId":"A-100","entryId":"JE-001","amount":500,"side":"debit","balance":1500,"tenantId":"T-1" } }
{ "type": "AccountCreated", "payload": {...} }
```

---

## 5. Commands & Command Handlers (examples)

* Commands:

  * `CreateJournalEntry` {journalType, date, description, lines[], metadata}
  * `PostJournalEntry` {journalEntryId}
  * `ReverseJournalEntry` {journalEntryId, reason}
  * `CreateAccount` {code,name,type,parentId}
* Command handler responsibilities:

  * Validate invariants, check chart-of-accounts references, enforce business rules, emit events.

---

## 6. Event Store interface (required methods)

* `appendToStream(streamId, expectedVersion, events[]) -> newVersion`
* `readStream(streamId, fromVersion, batchSize) -> [events]`
* `readAll(streamName?, fromPosition?) -> [events]` (optional global)
* `createSnapshot(streamId, version, snapshotPayload)`
* `loadSnapshot(streamId) -> {version, payload}`

Design streams by aggregate: `journal-{tenantId}`, `accounts-{tenantId}`, or separate streams per aggregate id.

---

## 7. Projections & Read Models

* **Projection handlers**: subscribe to events and update read DB.

* **Read DB schema (example Postgres):**

  * `ledger_postings (id, tenant_id, account_id, date, entry_id, debit, credit, balance_after, created_at)`
  * `trial_balance (tenant_id, account_id, debit_total, credit_total, updated_at)`
  * `financial_statements` materialized views or snapshot tables for fast queries.

* **Projection responsibilities**

  * Idempotent processing (store last processed event id/position).
  * Rebuild support (able to truncate and replay from event store).

---

## 8. Snapshotting & Rebuild strategy

* Snapshot aggregates when event stream length > N (e.g., 100) or large state size.
* Store snapshots in a cache or DB to speed up rehydration.
* Provide utilities to rebuild projections: stop projection workers, wipe read DB, replay events.

---

## 9. Multi-tenant considerations

* **Tenant ID**: present in every event and in projection keys.
* **Stream partitioning**: per-tenant streams recommended for easier isolation and scaling.
* **Data isolation**: ensure read DB rows are tagged by tenant; use row-level security where possible.
* **Cross-tenant operations**: restricted and audited.

---

## 10. Event versioning & schema evolution

* Include `eventType` and `schemaVersion` in event envelope.
* Use migration handlers or transformation layers in projections to handle older events.
* Prefer additive changes; avoid breaking old event consumers.

---

## 11. Idempotency & concurrency

* Commands must be deduplicated (use client-supplied idempotency key).
* Event store append with `expectedVersion` for optimistic concurrency; handle `WrongExpectedVersion` by rejecting or retrying after rehydration.
* Projections track last processed event position to ensure idempotent updates.

---

## 12. API & SDK surface (suggested)

* **SDK (library)**:

  * `accounting.commands.send(command)`
  * `accounting.query.getLedger(accountId, options)`
  * `accounting.query.getTrialBalance(period, options)`
  * `accounting.events.subscribe(handler)`

* **Adapters**:

  * REST/GRPC endpoints that call SDK command handlers.
  * Message bus publisher that publishes domain events to other systems.

---

## 13. Security & audit

* Events are the audit trail — store original command metadata: userId, actor, source, ip, correlationId.
* Read-only access to event store for auditors.
* Encrypt sensitive fields if needed (PII) or store them separately.

---

## 14. Tests & QA

* **Unit tests**: domain invariants (debits==credits), account creation, validation logic.
* **Integration tests**: command -> events -> projection updated.
* **Property tests**: random journal lines that must balance.
* **Replay tests**: clear projections, replay full event store, compare with expected balances.
* **Performance tests**: projection throughput, event store append latency.

---

## 15. Operational & deployment notes

* Event store backed by durable storage (Postgres event table, specialized ES like EventStoreDB, or cloud stream).
* Projections run as separate worker services; horizontal scale by partitioning tenant streams.
* Backups: event store is source-of-truth — back it up and provide replay tooling.
* Monitoring: event throughput, projection lag, failed events, dead-letter queue.
* Onboarding: a migration/importer to create initial Chart of Accounts and opening balances (produce events for opening balances).

---

## 16. Sample folder structure (framework-agnostic)

```
/accounting-lib
  /src
    /domain
      /accounting
        JournalEntry.ts
        JournalLine.ts
        Account.ts
        ChartOfAccounts.ts
        events.ts
        services.ts
    /application
      commands/
        CreateJournalEntryHandler.ts
        PostJournalEntryHandler.ts
      queries/
        LedgerQueryService.ts
    /infrastructure
      /eventstore
        EventStoreInterface.ts
        PgEventStoreAdapter.ts
      /projections
        LedgerProjection.ts
        TrialBalanceProjection.ts
      /persistence
        ReadModelRepos.ts
    /api
      restAdapter.ts (example minimal adapter)
  /tests
    domain/
    integration/
  /docs
    domain-glossary.md
    event-schema.md
  package.json or build files
```

(Translate `.ts` to chosen language: PHP, Go, Java, etc.)

---

## 17. Example command flow (Sale -> Cash Receipt)

1. POS module issues `CreateJournalEntry` command:

```json
{
  "type": "CreateJournalEntry",
  "payload": {
    "journalType":"cashReceipts",
    "date":"2025-11-08",
    "description":"Cash sale POS-123",
    "lines":[
      {"accountId":"cash","side":"debit","amount":500},
      {"accountId":"sales","side":"credit","amount":500}
    ],
    "metadata":{"tenantId":"T-1","actorId":"user-7","source":"pos-1"}
  },
  "idempotencyKey":"cmd-abc-123"
}
```

2. Command handler validates, emits `JournalEntryCreated` event.
3. Possibly `JournalEntryPosted` event after business rule (immediate posting).
4. Projection worker consumes events and writes ledger_postings and updates balances.

---

## 18. Acceptance criteria (example)

* A `CreateJournalEntry` command results in a `JournalEntryCreated` event and invariants are enforced.
* When events are replayed from scratch, the Ledger projection produces the same balances as incremental processing.
* Projections are idempotent and can recover from failures using last-processed-position.
* Supports tenant isolation and audit metadata per event.

---

## 19. Deliverables to request from implementer/AI

* Domain model files + tests for aggregates & invariants.
* Event schema document (JSON schema for every event).
* Event store adapter (in-memory + one persistent).
* Projection handlers for Ledger and Trial Balance.
* Command handlers and minimal API adapter.
* Replay & snapshot utilities.
* Documentation: domain glossary, developer guide, onboarding guide.

---

## 20. Quick risks & mitigations

* **Risk**: Event schema changes break projections. → Mitigate with schemaVersion & migration handlers.
* **Risk**: Projection lag causes stale reports. → Mitigate with monitoring & horizontal scaling.
* **Risk**: Incorrect manual general journal entries. → Mitigate with validation rules, role-based restrictions, approval workflow.

---

If you want, I’ll next generate:

* (A) **Concrete JSON schema** for the main events and commands, or
* (B) **A starter domain file** (e.g., `JournalEntry` aggregate pseudo-code) in your preferred language.

Which one do you want me to produce now?
