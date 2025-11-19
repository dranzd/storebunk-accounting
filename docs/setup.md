# Setup Guide

This guide covers the complete installation and setup process for the Storebunk Accounting library in a Docker development environment.

## Prerequisites

- Docker and Docker Compose installed
- Git installed
- SSH key configured for GitHub (for private repositories)
- Linux/macOS environment (or WSL2 on Windows)

## Installation

### 1. Clone the Repository

```bash
git clone git@github.com:dranzd/storebunk-accounting.git
cd storebunk-accounting
```

### 2. Configure Environment

Run the setup script to auto-detect your user information:

```bash
./setup-env.sh
```

This will:
- Detect your username, UID, and GID
- Create/update `.env` file with your user configuration
- Ensure proper file permissions in Docker

**Manual Configuration (Alternative)**

If you prefer to configure manually, copy the example file:

```bash
cp .env.example .env
```

Then edit `.env` and set:

```bash
# Project name
PROJECT_NAME=storebunk-accounting

# Your user information (run: id -u, id -g, whoami)
USER=your-username
UID=1000
GID=1000

# GitHub token for private repositories
# Get from: https://github.com/settings/tokens
GITHUB_TOKEN=your_github_token_here
```

### 3. Build Docker Container

```bash
./utils rebuild
```

This will:
- Build the PHP 8.3 Docker image
- Create a user in the container matching your host user
- Install system dependencies
- Configure SSH for GitHub access

### 4. Install PHP Dependencies

```bash
./utils install
```

This runs `composer install` inside the container, creating the `vendor/` directory on your host machine with proper ownership.

### 5. Set Up Database (Optional)

For the MVP, the library uses in-memory implementations. For production deployments with PostgreSQL:

```bash
# Apply ledger and account balance schema migrations
# (Migrations will be provided in post-MVP phases)
mysql -u your_user -p your_database < database/migrations/001_create_ledger_tables.sql
```

### 6. Verify Installation

Check that everything is set up correctly:

```bash
# Check vendor directory exists and has correct ownership
ls -la vendor/

# Should show your username, e.g.:
# drwxr-xr-x your-username your-username

# Run tests to verify setup
./utils test

# Check autoloading works
./utils composer dump-autoload
```

## Docker Utility Commands

The `./utils` script provides convenient commands for development:

### Container Management
```bash
./utils up          # Start containers
./utils down        # Stop containers
./utils restart     # Restart containers
./utils rebuild     # Rebuild containers from scratch
./utils ps          # Show container status
./utils logs        # Show container logs
```

### Shell Access
```bash
./utils shell       # Open shell as your user
./utils bash        # Alias for shell
./utils root-shell  # Open shell as root (for system tasks)
```

### Composer Commands
```bash
./utils composer [command]    # Run any composer command
./utils install              # composer install
./utils update               # composer update
./utils dump-autoload        # composer dump-autoload
```

### Testing & Quality
```bash
./utils test         # Run PHPUnit tests
./utils phpstan      # Run static analysis
./utils cs-check     # Check code style
./utils cs-fix       # Fix code style
./utils quality      # Run all quality checks
```

### Utility Commands
```bash
./utils php [command]    # Run PHP commands
./utils exec [command]   # Execute any command in container
```

## Running Tests

Inside the container:

```bash
./utils test

# Or with specific options
./utils test --filter StockItemTest
./utils test --testdox
```

Outside Docker (if vendor exists locally):

```bash
./vendor/bin/phpunit
./vendor/bin/phpunit --testdox
```

## Quick Start

### Creating a Journal Entry

```php
use Dranzd\StorebunkAccounting\Application\Command\CreateJournalEntry\CreateJournalEntryCommand;
use Dranzd\StorebunkAccounting\Application\Command\CreateJournalEntry\CreateJournalEntryHandler;

$command = new CreateJournalEntryCommand(
    id: 'JE-001',
    date: new DateTime('2025-11-19'),
    description: 'Cash sale',
    lines: [
        ['accountId' => 'cash', 'amount' => 500.00, 'side' => 'debit'],
        ['accountId' => 'sales', 'amount' => 500.00, 'side' => 'credit']
    ]
);

$handler = new CreateJournalEntryHandler($journalEntryRepository);
$handler->handle($command);
```

### Posting a Journal Entry

```php
use Dranzd\StorebunkAccounting\Application\Command\PostJournalEntry\PostJournalEntryCommand;
use Dranzd\StorebunkAccounting\Application\Command\PostJournalEntry\PostJournalEntryHandler;

$command = new PostJournalEntryCommand(
    journalEntryId: 'JE-001'
);

$handler = new PostJournalEntryHandler($journalEntryRepository);
$handler->handle($command);
```

### Creating an Account

```php
use Dranzd\StorebunkAccounting\Domain\Accounting\Account;
use Dranzd\StorebunkAccounting\Domain\Accounting\AccountType;

$account = Account::create(
    id: 'cash',
    name: 'Cash',
    type: AccountType::Asset
);

$accountRepository->save($account);
```

### Querying Account Balance

```php
use Dranzd\StorebunkAccounting\Application\Query\GetAccountBalance\GetAccountBalanceQuery;
use Dranzd\StorebunkAccounting\Application\Query\GetAccountBalance\GetAccountBalanceHandler;

$query = new GetAccountBalanceQuery(
    tenantId: 'T-1',
    accountId: 'cash'
);

$handler = new GetAccountBalanceHandler($ledgerReadModel);
$balanceDTO = $handler->handle($query);

echo "Balance: " . $balanceDTO->balance;
echo "Account: " . $balanceDTO->accountName;
```

## Dependency Injection Setup

Example using a simple container (MVP uses in-memory implementations):

```php
use Dranzd\StorebunkAccounting\Infrastructure\Persistence\EventStore\InMemoryEventStore;
use Dranzd\StorebunkAccounting\Infrastructure\Persistence\EventStore\EventSourcedJournalEntryRepository;
use Dranzd\StorebunkAccounting\Infrastructure\Persistence\ReadModel\InMemoryReadModel;
use Dranzd\StorebunkAccounting\Infrastructure\Persistence\Repository\InMemoryAccountRepository;

// Event Store (in-memory for MVP)
$eventStore = new InMemoryEventStore();

// Read Model (in-memory for MVP)
$readModel = new InMemoryReadModel();

// Repositories
$journalEntryRepository = new EventSourcedJournalEntryRepository($eventStore);
$accountRepository = new InMemoryAccountRepository();

// Command Handlers
$createJournalEntryHandler = new CreateJournalEntryHandler($journalEntryRepository, $accountRepository);
$postJournalEntryHandler = new PostJournalEntryHandler($journalEntryRepository);
$createAccountHandler = new CreateAccountHandler($accountRepository);

// Query Handlers
$getAccountBalanceHandler = new GetAccountBalanceHandler($readModel);
$getLedgerHandler = new GetLedgerHandler($readModel);
```

## Event Projection Setup

Wire up the projection to listen to events:

```php
use Dranzd\StorebunkAccounting\Infrastructure\Persistence\Projection\LedgerProjection;
use Dranzd\StorebunkAccounting\Infrastructure\Persistence\Projection\ProjectionRunner;

$ledgerProjection = new LedgerProjection($readModel);
$projectionRunner = new ProjectionRunner($eventStore, [$ledgerProjection]);

// Subscribe to events
$eventStore->subscribe('JournalEntryPosted', function($event) use ($ledgerProjection) {
    $ledgerProjection->onJournalEntryPosted($event);
});

// Run projections to update read model
$projectionRunner->run();
```

## Troubleshooting

### Permission Issues with vendor/

**Problem:** `vendor/` directory owned by root or wrong user

**Solution:**
```bash
# Run setup script to configure correct user
./setup-env.sh

# Rebuild container
./utils rebuild

# Reinstall dependencies
./utils install
```

### SSH Key Issues

**Problem:** `Failed to clone git@github.com:...` or permission denied

**Solution:**
```bash
# Check SSH key is loaded
ssh-add -l

# If empty, add your key
ssh-add ~/.ssh/id_rsa

# Test GitHub connection
ssh -T git@github.com

# Rebuild container to update SSH configuration
./utils rebuild
```

### Composer Cache Errors

**Problem:** `/.composer/cache/vcs does not exist and could not be created`

**Solution:**
```bash
# Clear composer cache
./utils composer clear-cache

# Or manually create directory
mkdir -p ~/.composer/cache

# Rebuild container
./utils rebuild
```

### Container Won't Start

**Problem:** Container exits immediately or won't start

**Solution:**
```bash
# Check container logs
./utils logs

# Check container status
./utils ps

# Rebuild from scratch
./utils down
./utils rebuild
```

### UID/GID Mismatch

**Problem:** Files created in container have wrong ownership

**Solution:**
```bash
# Check your UID and GID
id -u  # Should match UID in .env
id -g  # Should match GID in .env

# Update .env if different
./setup-env.sh

# Rebuild container
./utils rebuild
```

### Missing Dependencies

**Problem:** `Class not found` or `Undefined type` errors

**Solution:**
```bash
# Reinstall dependencies
./utils install

# Regenerate autoloader
./utils composer dump-autoload

# Check composer.json has all required repositories
cat composer.json | grep -A 20 repositories
```

### GitHub Token Issues

**Problem:** Rate limit exceeded or authentication failures

**Solution:**
```bash
# Generate new token at: https://github.com/settings/tokens
# Add to .env file:
echo "GITHUB_TOKEN=your_new_token_here" >> .env

# Rebuild container
./utils rebuild
```

## Getting Help

If you encounter issues not covered here:

1. Check the [Architecture Guide](architecture.md) for design patterns
2. Review [API Reference](api-reference.md) for usage examples
3. Search [GitHub Issues](https://github.com/dranzd/storebunk-accounting/issues)
4. Open a new issue with:
   - Steps to reproduce
   - Error messages
   - Output of `./utils ps` and `./utils logs`
   - Your environment (OS, Docker version)

## Next Steps

1. Read the [Architecture Guide](architecture.md) to understand DDD, ES, CQRS patterns
2. Review [Folder Structure](folder-structure.md) for code organization
3. Explore [Feature Specifications](features/) for detailed requirements
4. Review [MVP Implementation](features/00004-mvp-implementation.md) for the minimum viable product
5. Implement the MVP following the [Implementation Checklist](features/00003-implementation-checklist.md)
6. Add persistent event store (PostgreSQL) for production
7. Implement trial balance and financial reports
8. Add journal entry reversal capability
9. Build REST/GraphQL API layer
