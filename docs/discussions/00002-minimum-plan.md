Alright Pre — here’s the **brutally honest, bare-minimum starting point** to call something an *accounting library*.
No fluff. No overbuilding. Just what’s *required* for it to actually function as “accounting.”

---

# ✅ **Minimum Viable Accounting Library (Core Requirements)**

If you strip everything down to the core, an accounting system only needs **three things**:

---

## **1. Chart of Accounts**

A structured list of all accounts (Asset, Liability, Equity, Revenue, Expense).

**Why it’s required:**
Without accounts, you have nowhere to record anything.

**Minimum data needed per account:**

* `id`
* `name`
* `type` (asset/liability/equity/revenue/expense)

**This is mandatory — everything in accounting ties back to the CoA.**

---

## **2. Journal Entry Aggregate**

This is the **heart** of accounting.

A Journal Entry must support:

* Multiple lines (debit/credit pairs)
* Validation that **total debits = total credits**
* A date
* A description

**Minimum rules:**

* Must have ≥ 2 lines
* Must balance
* Lines must reference existing accounts

This is the **origin** of all accounting data.
If you have a journal, you have accounting.

---

## **3. Posting to Ledger (Projection)**

A ledger is simply:

* A list of postings grouped by account
* A running balance per account

**Minimum functionality:**

* Take a journal entry
* Break it into postings
* Apply them to each account’s balance

That’s it.

You don’t need statements, trial balance, special journals, multi-currency, fiscal periods, or fancy workflows yet.

If you have:

* **Chart of accounts**
* **Journal entries**
* **Ledger projection**

You already have the core of accounting.

---

# Minimum Architecture for DDD + Event Sourcing

Since you’re using DDD + ES + onion, minimum components become:

---

## **Domain (Core)**

You only need two aggregates + 1 projection:

### 1. `Account` (Entity in ChartOfAccounts)

### 2. `JournalEntry` (Aggregate Root)

### 3. `LedgerAccount` (Projection / Read Model)

---

## **Events (Minimum)**

You only need these two events to start:

### `JournalEntryCreated`

Contains:

* entry id
* date
* description
* lines[]
* metadata (tenant, actor)

### `JournalEntryPosted`

Contains:

* entry id
* timestamp

(Posting is the thing that triggers projection into the ledger.)

---

## **Command Handlers (Minimum)**

You only need:

### `CreateJournalEntryHandler`

→ validates → emits JournalEntryCreated

### `PostJournalEntryHandler`

→ emits JournalEntryPosted

---

## **Event Store (Minimum)**

Just the ability to:

* append events
* read all events for Ledger projection

You don’t even need snapshots yet.

---

## **Projection Worker (Minimum)**

A simple worker that:

* Subscribes to events
* On `JournalEntryPosted`, updates LedgerAccount balances

---

# 🔥 Minimum functionality summary (in plain English)

If your library can do all of this, it’s a real accounting engine:

1. **Register accounts in a chart of accounts.**
2. **Create a journal entry that balances.**
3. **Post it.**
4. **The ledger updates automatically.**
5. **You can ask for the current balance of any account.**

That’s enough to:

* Track sales
* Track expenses
* Track payables
* Track assets
* Produce basic reports later

Everything else (trial balance, statements, period closing, multi-currency, special journals) is *nice to have*, not required for a minimal accounting core.

---

# If I were starting right now, my first file would be:

1. `Account.php` (or ts/go/java)
2. `JournalEntry.php`
3. `JournalLine.php`
4. `LedgerAccountProjection.php`
5. Event definitions for:

   * `JournalEntryCreated`
   * `JournalEntryPosted`

And then a simple in-memory event store + projection runner.

This gives you the **barebones engine**, the beating heart.

---

If you want, I can draft you the **initial directory skeleton + first domain classes** in your preferred language (PHP? TS? Go?) to start coding immediately.
