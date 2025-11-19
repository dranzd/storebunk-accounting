# Installation Guide

## Requirements

- PHP 8.3 or higher
- Composer 2.x

## Via Composer

The recommended way to install Storebunk Inventory is through Composer:

```bash
composer require dranzd/storebunk-inventory
```

## Development Installation

If you want to contribute or develop with this library:

### 1. Clone the Repository

```bash
git clone https://github.com/dranzd/storebunk-inventory.git
cd storebunk-inventory
```

### 2. Using Docker (Recommended)

Make the utils script executable and start the containers:

```bash
chmod +x utils
./utils up
./utils install
```

### 3. Without Docker

Install dependencies directly:

```bash
composer install
```

## Verifying Installation

Run the test suite to verify everything is working:

```bash
# With Docker
./utils test

# Without Docker
./vendor/bin/phpunit --testdox
```

## Configuration

Currently, this library doesn't require additional configuration. Simply require the autoloader:

```php
<?php

require 'vendor/autoload.php';

use Dranzd\StorebunkInventory\YourClass;
```

## Troubleshooting

### Composer Authentication Issues

If you encounter authentication issues with private repositories:

```bash
composer config --global github-oauth.github.com YOUR_GITHUB_TOKEN
```

### Docker Issues

If containers fail to start:

```bash
# Rebuild from scratch
./utils rebuild

# Check container logs
./utils logs
```

### Permission Issues

If you encounter permission issues with Docker:

```bash
# Use root shell to fix permissions
./utils root-shell
chown -R www-data:www-data /app
```

## Next Steps

- Read the [Usage Guide](usage.md)
- Check the [API Reference](api-reference.md)
- Learn about [Contributing](contributing.md)
