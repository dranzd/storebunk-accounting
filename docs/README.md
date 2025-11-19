# Storebunk Accounting

A PHP library for handling accounting logic and flows within the Storebunk ecosystem.

This document is aimed at both **consumers** of the library and **developers** working on the codebase.

---

## 1. Features (High-Level)

- **Accounting primitives** – Core value objects and services for accounting.
- **Domain-first design** – Focus on clear, testable business rules.
- **Framework-agnostic** – Can be used from any PHP application.
- **Docker-based dev environment** – Consistent tooling via Docker and the `utils` helper script.

_As the project evolves, expand this section with concrete modules and examples._

---

## 2. Installation (Library Consumers)

### 2.1. Requirements

- PHP 8.3+ (recommended to match the dev environment)
- Composer
- SSH access to private GitHub repositories (for dependencies)

### 2.2. Private Dependencies

This library depends on several private Dranzd common libraries:

- `dranzd/common-event-sourcing` - Event sourcing infrastructure
- `dranzd/common-utils` - Shared utilities
- `dranzd/common-domain-assert` - Domain assertion helpers
- `dranzd/common-valueobject` - Common value objects
- `dranzd/common-cqrs` - CQRS infrastructure

**For developers:** Ensure your SSH key is added to GitHub and has access to these repositories.

### 2.3. Install via Composer

```bash
composer require dranzd/storebunk-accounting
```

(If the package is not yet published, require it via VCS or a path repository in your root `composer.json`.)

### 2.4. Autoloading

The library is autoloaded via PSR‑4:

- Namespace root: `Dranzd\\StorebunkAccounting\\`
- Source path: `src/`

In your application code, you might use:

```php
use Dranzd\StorebunkAccounting\SomeService;

$service = new SomeService();
```

Replace `SomeService` with the actual classes provided by this library.

---

## 3. Basic Usage (Consumers)

> This section should be updated as the public API stabilizes.

Typical usage patterns might include:

- Creating accounting entities (e.g. ledgers, entries, balances).
- Applying domain rules for posting, reconciliation, and reporting.
- Integrating with Storebunk or other services.

When concrete services and value objects are defined, add:

- **Code examples** for the most common operations.
- **Reference** for all public classes intended for external use.

---

## 4. Local Development (Contributors)

### 4.1. Repository Layout

- `src/` – Library source code (PHP).
- `tests/` – PHPUnit tests.
- `composer.json` – Package definition and autoload config.
- `docker-compose.yml` – Docker services for the dev environment.
- `utils` – Helper script to manage Docker, Composer, and quality tools.
- `docs/` – Project documentation (including this file).

### 4.2. Prerequisites

- Docker and Docker Compose installed.
- Unix-like shell (Linux/macOS or WSL on Windows) to run the `utils` script.
- SSH key configured with GitHub access to private Dranzd repositories.

**Setting up SSH access:**

1. Generate an SSH key if you don't have one:
   ```bash
   ssh-keygen -t ed25519 -C "your_email@example.com"
   ```

2. Add your SSH key to your GitHub account (Settings → SSH and GPG keys).

3. Test your connection:
   ```bash
   ssh -T git@github.com
   ```

4. Ensure your SSH key is available in the Docker container by mounting your SSH directory (already configured in `docker-compose.yml` if needed).

### 4.3. Starting the Dev Environment

From the project root:

```bash
./utils up
```

This will:

- Build and start the PHP container defined in `docker-compose.yml`.
- Mount the repository into the container at `/app`.

To see container status:

```bash
./utils ps
```

To stop containers:

```bash
./utils down
```

### 4.4. Shell Access

Open a shell in the PHP container:

```bash
./utils shell
```

Or, explicitly with bash:

```bash
./utils bash
```

For a root shell (if needed):

```bash
./utils root-shell
```

---

## 5. Composer & Dependencies (Dev Workflow)

You typically run Composer inside the container via `utils`.

Install dependencies:

```bash
./utils install
```

Update dependencies:

```bash
./utils update
```

Dump autoload (after adding classes or changing namespaces):

```bash
./utils dump-autoload
```

You can also proxy Composer directly:

```bash
./utils composer <command>
```

Example:

```bash
./utils composer show
```

---

## 6. Tests & Quality Tools

### 6.1. Run Tests

Run the test suite (PHPUnit):

```bash
./utils test
```

### 6.2. Static Analysis (PHPStan)

```bash
./utils phpstan
```

### 6.3. Coding Standards (PHPCS / PHPCBF)

Check coding standards:

```bash
./utils cs-check
```

Auto-fix coding standard issues where possible:

```bash
./utils cs-fix
```

### 6.4. Full Quality Pipeline

Run tests, static analysis, and code style checks together:

```bash
./utils quality
```

---

## 7. Versioning & Releases

_Currently unspecified._

When you define a release process, document:

- Versioning scheme (e.g. SemVer).
- How to cut a new release/tag.
- How and when to publish to Packagist (if applicable).

---

## 8. Contributing Guidelines

Basic expectations for contributors:

- Follow existing coding style and conventions.
- Add or update tests for any behavior change.
- Ensure `./utils quality` passes before opening a PR.
- Keep public APIs backward-compatible where possible.

You can extend this section with:

- Branch naming conventions.
- PR templates or expectations.
- Code review checklist.

---

## 9. Roadmap / TODOs

Use this section as a living list of planned work. Examples:

- [ ] Document all public services and value objects.
- [ ] Add end-to-end examples of integrating with a host application.
- [ ] Flesh out domain documentation (accounting rules, invariants, etc.).
- [ ] Add diagrams for key flows once they stabilize.

---

## 10. Support & Contact

For now, treat this as an internal/early-stage library. Once you have a stable release and primary maintainer(s), add:

- Contact email or Slack/Teams channel.
- How to report bugs or request features.

---

Feel free to edit this file to better reflect the actual feature set, internal processes, and integration patterns as the project matures.
