# Storebunk Accounting

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.3-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

> **A framework-agnostic accounting library built with DDD, Event Sourcing, and CQRS for multi-tenant retail platforms.**

A PHP library for handling accounting logic and flows within the Storebunk ecosystem. This library implements double-entry bookkeeping, journal entries, ledgers, and financial reporting using event sourcing and domain-driven design principles.

## 🎯 Domain Purpose

The Accounting Domain ensures **financial accuracy**, **maintains audit trails**, and **provides financial insights** through proper bookkeeping and reporting — forming the financial backbone of any business.

It answers the key business questions:
> "Where did the money come from, where did it go, and what is our financial position?"

## 🏢 Business Context

Accounting sits at the heart of business operations, connecting multiple domains:

- **Sales / POS / E-commerce** - Records revenue and receivables
- **Purchasing / Procurement** - Records expenses and payables
- **Inventory** - Tracks cost of goods sold and inventory valuation
- **Payments** - Records cash movements and settlements
- **Reporting / Analytics** - Provides financial statements and insights

## ✨ Key Features

- **Double-Entry Bookkeeping** - Enforces debits equal credits invariant
- **Journal Entries** - Support for general, sales, purchase, cash receipt, and cash disbursement journals
- **Chart of Accounts** - Hierarchical account structure with Asset, Liability, Equity, Revenue, Expense types
- **Ledger Management** - Real-time posting and balance tracking per account
- **Financial Reporting** - Trial balance, income statement, balance sheet, cash flow
- **Event Sourcing** - Complete audit trail with event replay capability
- **CQRS Architecture** - Separate command and query models for optimal performance
- **Multi-Tenant** - Tenant isolation at event and projection levels
- **Idempotency** - Duplicate command detection and handling
- **Audit Trail** - Complete history of all financial transactions with actor context

## Requirements

- PHP 8.3 or higher
- Composer
- Docker & Docker Compose (for development)
- SSH access to private Dranzd repositories (for dependencies)

## Installation

### For Library Usage

Install via Composer:

```bash
composer require dranzd/storebunk-accounting
```

**Note:** This library depends on private Dranzd common libraries. Ensure your SSH key has access to:
- `dranzd/common-event-sourcing`
- `dranzd/common-utils`
- `dranzd/common-domain-assert`
- `dranzd/common-valueobject`
- `dranzd/common-cqrs`

### For Development

**Quick Start:**

```bash
# 1. Clone repository
git clone git@github.com:dranzd/storebunk-accounting.git
cd storebunk-accounting

# 2. Configure environment (auto-detects your user)
./setup-env.sh

# 3. Add your GitHub token to .env
# GITHUB_TOKEN=your_token_here

# 4. Build Docker container
./utils rebuild

# 5. Install dependencies
./utils install

# 6. Run tests
./utils test
```

**Detailed instructions:** See [docs/README.md](docs/README.md)

## Quick Start

```php
<?php

require 'vendor/autoload.php';

use Dranzd\StorebunkAccounting\AccountingCommands;
use Dranzd\StorebunkAccounting\AccountingQueries;

// Initialize SDK
$commands = new AccountingCommands();
$queries = new AccountingQueries();

// Create a journal entry for a cash sale
$commands->send(new CreateJournalEntry(
    journalType: 'cashReceipts',
    date: new DateTime('2025-11-19'),
    description: 'Cash sale POS-123',
    lines: [
        ['accountId' => 'cash', 'side' => 'debit', 'amount' => 500],
        ['accountId' => 'sales', 'side' => 'credit', 'amount' => 500]
    ],
    metadata: ['tenantId' => 'T-1', 'source' => 'pos-1']
));

// Query ledger
$ledger = $queries->getLedger('T-1', 'cash', fromDate: new DateTime('2025-11-01'));

// Get trial balance
$trialBalance = $queries->getTrialBalance('T-1', new DateTime('2025-11-19'));
```

## Development

This project uses Docker for development. See [Installation](#for-development) above for setup instructions.

### Available Commands

The `./utils` script provides convenient commands:

#### Docker Commands
- `./utils up` - Start the Docker containers
- `./utils down` - Stop the Docker containers
- `./utils restart` - Restart the Docker containers
- `./utils build` - Build the Docker containers
- `./utils rebuild` - Rebuild containers from scratch
- `./utils ps` - Show container status
- `./utils logs` - Show container logs

#### Shell Commands
- `./utils shell` - Open a shell in the PHP container
- `./utils bash` - Alias for shell
- `./utils root-shell` - Open a root shell in the PHP container

#### Composer Commands
- `./utils composer [command]` - Run Composer commands
- `./utils install` - Run composer install
- `./utils update` - Run composer update
- `./utils dump-autoload` - Run composer dump-autoload

#### Testing Commands
- `./utils test` - Run PHPUnit tests
- `./utils phpstan` - Run PHPStan static analysis
- `./utils cs-check` - Check code style with PHPCS
- `./utils cs-fix` - Fix code style with PHPCBF
- `./utils quality` - Run all quality checks

#### Utility Commands
- `./utils php [command]` - Run PHP commands
- `./utils exec [command]` - Execute a command in the container

### Running Tests

```bash
./utils test
```

Or without Docker:
```bash
./vendor/bin/phpunit --testdox
```

### Code Quality

Run all quality checks:
```bash
./utils quality
```

## 📚 Documentation

For detailed documentation, see the [docs](docs/) directory:

- **[Main Documentation](docs/README.md)** - Complete developer and consumer guide
- **[Architecture](docs/features/00001-accounting-library-architecture.md)** - DDD, Event Sourcing, CQRS patterns
- **[Implementation Checklist](docs/features/00003-implementation-checklist.md)** - Task breakdown and execution plan
- **[Feature Specifications](docs/features/)** - Detailed feature documentation
- **[Discussions](docs/discussions/)** - Design discussions and decisions

## 🎯 Business Objectives

| Objective | Description |
|-----------|-------------|
| **Accuracy** | Maintain accurate financial records with enforced invariants |
| **Traceability** | Provide complete audit trail of all financial transactions |
| **Compliance** | Support regulatory and tax reporting requirements |
| **Insights** | Enable financial analysis and decision-making |
| **Accountability** | Attribute all transactions to specific actors and sources |

## 🔄 Domain Events

This library follows an event-driven architecture:

**Emits:**
- `JournalEntryCreated` - When a new journal entry is created
- `JournalEntryPosted` - When an entry is posted to the ledger
- `JournalEntryReversed` - When an entry is reversed
- `LedgerPosted` - When a posting is made to an account
- `AccountCreated` - When a new account is added to chart

**Listens to:**
- `SaleCompleted` - To record revenue and receivables
- `PurchaseReceived` - To record expenses and payables
- `PaymentReceived` - To record cash receipts
- `PaymentMade` - To record cash disbursements

## 🔧 Development Process

This project follows a strict **documentation-first development** process:

- **Documentation before implementation** - No code without approved documentation
- **Feature specifications** - All features documented in `docs/features/` with incremental numbering
- **Design discussions** - All design decisions documented in `docs/discussions/`
- **Test-driven** - Tests written before or alongside implementation
- **Quality gates** - All code must pass `./utils quality` before merge

See [Implementation Checklist](docs/features/00003-implementation-checklist.md) for the complete execution plan.

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](docs/contributing.md) for details.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Testing

This library uses PHPUnit for testing. Tests are located in the `tests/` directory.

```bash
# Run tests
./utils test

# Run tests with coverage (if configured)
./utils test --coverage
```

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Support

For issues, questions, or contributions, please use the [GitHub issue tracker](https://github.com/dranzd/storebunk-accounting/issues).

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a history of changes to this project.

## Authors

- **dranzd** - *Initial work*

## Acknowledgments

- Built for the Storebunk ecosystem
- Inspired by modern PHP library best practices
