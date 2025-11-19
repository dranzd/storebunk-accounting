# Contributing Guide

Thank you for considering contributing to Storebunk Accounting! This document outlines the process and guidelines for contributing.

## Code of Conduct

Be respectful, inclusive, and constructive in all interactions.

## How to Contribute

### Reporting Bugs

1. Check if the bug has already been reported in [Issues](https://github.com/dranzd/storebunk-accounting/issues)
2. If not, create a new issue with:
   - Clear title and description
   - Steps to reproduce
   - Expected vs actual behavior
   - PHP version and environment details
   - Code samples if applicable

### Suggesting Features

1. Check existing issues and discussions
2. Create a new issue with:
   - Clear description of the feature
   - Use cases and benefits
   - Possible implementation approach

### Pull Requests

1. **Fork the repository**
   ```bash
   git clone https://github.com/YOUR_USERNAME/storebunk-accounting.git
   cd storebunk-accounting
   ```

2. **Create a feature branch**
   ```bash
   git checkout -b feature/your-feature-name
   ```

3. **Set up development environment**
   ```bash
   chmod +x utils
   ./utils up
   ./utils install
   ```

4. **Make your changes**
   - Write clean, readable code
   - Follow PSR-12 coding standards
   - Add tests for new features
   - Update documentation as needed

5. **Run quality checks**
   ```bash
   ./utils quality
   ```

6. **Commit your changes**
   ```bash
   git add .
   git commit -m "Add: Brief description of changes"
   ```

   Use conventional commit messages:
   - `Add:` for new features
   - `Fix:` for bug fixes
   - `Update:` for updates to existing features
   - `Refactor:` for code refactoring
   - `Docs:` for documentation changes
   - `Test:` for test additions/changes

7. **Push to your fork**
   ```bash
   git push origin feature/your-feature-name
   ```

8. **Create a Pull Request**
   - Provide a clear description
   - Reference any related issues
   - Ensure all checks pass

## Development Guidelines

### Coding Standards

- Follow [PSR-12](https://www.php-fig.org/psr/psr-12/) coding style
- Use strict typing: `declare(strict_types=1);`
- Add type hints for all parameters and return types
- Write self-documenting code with clear variable names

### Code Style

**IMPORTANT:** StoreBunk Accounting has specific code style requirements beyond PSR-12.

### Official Code Style Guide

See [Code Style Guide](../.cascade/CODE_STYLE_GUIDE.md) for complete standards.

### Key Rules

1. **Visibility Order:** public → protected → private (for constants, properties, and methods)
2. **Class Structure:** Constants → Properties → Constructor → Public Methods → Protected Methods → Private Methods
3. **Getters:** MUST use `get` prefix (e.g., `getQuantity()`, not `quantity()`)
4. **Public Methods:** MUST be `final` by default (unless explicitly designed for extension)
5. **Boolean Checks:** Use `is`/`has` prefix (e.g., `isActive()`, `hasExpired()`)
6. **Type Hints:** Required for all parameters and return types
7. **Strict Types:** Always use `declare(strict_types=1);`

### Quick Example

```php
final class Stock
{
    // 1. Constants (public → protected → private)
    public const STATUS_ACTIVE = 'active';
    private const MAX_QUANTITY = 1000;

    // 2. Properties (public → protected → private)
    private StockId $stockId;
    private Quantity $quantity;

    // 3. Constructor
    private function __construct() { }

    // 4. Public methods (final by default)
    final public function getQuantity(): Quantity
    {
        return $this->quantity;
    }

    final public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    // 5. Protected methods
    protected function applyEvent(Event $event): void { }

    // 6. Private methods
    private function assertActive(): void { }
}
```

### PSR-12 Compliance

- Follow PSR-12 for formatting, indentation, and naming
- Use 4 spaces for indentation
- Opening braces on same line for methods
- One statement per line
- Maintain or improve code coverage
- Run tests before submitting PR:
  ```bash
  ./utils test
  ```

### Testing

- Write tests for all new features
- Maintain or improve code coverage
- Run tests before submitting PR:
  ```bash
  ./utils test
  ```

### Documentation

- Update relevant documentation files
- Add PHPDoc blocks for all public methods
- Include code examples for new features
- Keep README.md up to date

### Code Review Process

1. Maintainers will review your PR
2. Address any requested changes
3. Once approved, your PR will be merged

## Development Workflow

### Using Docker

```bash
# Start containers
./utils up

# Run tests
./utils test

# Open shell
./utils shell

# Run composer commands
./utils composer require package/name

# Check code style
./utils cs-check

# Fix code style
./utils cs-fix

# Run static analysis
./utils phpstan

# Run all quality checks
./utils quality
```

### Without Docker

```bash
# Install dependencies
composer install

# Run tests
./vendor/bin/phpunit --testdox

# Other composer scripts (if configured)
composer test
composer phpstan
composer cs-check
composer cs-fix
```

## Documentation Standards

### File Naming Convention

**All documentation files MUST use kebab-case (lowercase with hyphens):**

✅ **Correct:**
- `architecture.md`
- `domain-vision.md`
- `product-item-stock-overview.md`
- `10002-product-item-stock-relationship.md`

❌ **Incorrect:**
- `Architecture.md`
- `DOMAIN_VISION.md`
- `Product_Item_Stock_Overview.md`
- `10002_product_item_stock_relationship.md`

### Documentation Structure

```
docs/
├── *.md                   # Main documentation files (kebab-case)
├── discussions/           # Design discussions and decisions
│   └── *.md               # Numbered discussions (e.g., 10001-topic-name.md)
├── features/              # Feature specifications
│   └── *.md               # Numbered features (e.g., 9001-feature-name.md)
└── proposals/             # Architecture proposals
    └── *.md               # Proposal documents (kebab-case)
```

### Numbering Scheme for Features and Discussions

**CRITICAL:** Numbers must be **unique and incremental** within each directory.

#### Features (`docs/features/`)
- Format: `NNNN-feature-name.md` (4 digits)
- Numbers are **incremental** - each new feature gets the next available number
- **DO NOT reuse numbers** - each feature must have a unique number

```
✅ CORRECT:
docs/features/
├── 9001-complete-event-sourcing-implementation.md
├── 9002-refactor-application-services-api.md
├── 9003-remove-aggregate-getter-methods.md
├── 9004-product-item-stock-architecture.md  ← Next number
└── 9005-next-feature.md                     ← Next after that

❌ WRONG:
docs/features/
├── 9003-remove-aggregate-getter-methods.md
└── 9003-product-item-stock-architecture.md  ← Duplicate number!
```

#### Discussions (`docs/discussions/`)
- Format: `NNNNN-discussion-topic.md` (5 digits)
- Numbers are **incremental** - each new discussion gets the next available number
- **DO NOT reuse numbers** - each discussion must have a unique number

```
✅ CORRECT:
docs/discussions/
├── 10001-stock-flow-aggregate-pattern.md
├── 10002-product-item-stock-relationship.md
└── 10003-next-discussion.md  ← Next number

❌ WRONG:
docs/discussions/
├── 10002-product-item-stock-relationship.md
└── 10002-another-discussion.md  ← Duplicate number!
```

**Why Incremental Numbering?**
- Ensures uniqueness (no conflicts)
- Provides chronological ordering
- Makes references unambiguous
- Simplifies automation and tooling

### Internal Documentation

Files in `.cascade/` directory may use UPPERCASE_SNAKE_CASE for task tracking:
- `.cascade/TASK_*.md` - Implementation tasks
- `.cascade/PRODUCT_*.md` - Product-related designs
- Other internal docs follow kebab-case

## Project Structure

```
storebunk-accounting/
├── src/                # Source code
├── tests/              # Test files
├── docs/               # Documentation (kebab-case filenames)
├── .cascade/           # Internal docs and tasks
├── .github/            # GitHub workflows
├── Dockerfile          # Docker configuration
├── docker-compose.yml  # Docker Compose configuration
├── phpunit.xml         # PHPUnit configuration
├── composer.json       # Composer dependencies
├── utils               # Development utility script
└── README.md           # Main documentation
```

## Quality Standards

All contributions must:
- Pass all tests
- Follow coding standards
- Include appropriate documentation
- Not decrease code coverage
- Pass static analysis (PHPStan)

## Getting Help

- Open an issue for questions
- Check existing documentation
- Review closed issues and PRs

## License

By contributing, you agree that your contributions will be licensed under the MIT License.

## Recognition

Contributors will be recognized in the project's documentation and release notes.

Thank you for contributing to Storebunk Accounting! 🎉
