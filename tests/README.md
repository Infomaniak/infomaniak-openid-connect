# Unit Tests for Infomaniak OpenID Connect

This directory contains the unit tests for the WordPress Infomaniak OpenID Connect plugin.

## Prerequisites

- PHP 7.4 or higher
- Composer
- MySQL/MariaDB
- Subversion (svn)
- WordPress (for integration tests)
- PHPUnit 9.5 or higher

## Installation

1. Install dependencies with Composer:

```bash
composer install
```

2. Make the test installation script executable:

```bash
chmod +x bin/install-wp-tests.sh
```

3. Set up the WordPress test environment:

```bash
bin/install-wp-tests.sh wordpress_test root '' localhost latest
```

   - Replace `root` with your MySQL username if different
   - Add your MySQL password as the third argument if needed
   - Adjust `localhost` if your database is on a different host

## Running Tests

To run all tests:

```bash
composer test
```

To run a specific test file:

```bash
./vendor/bin/phpunit tests/phpunit/tests/test-main-class.php
```

To generate a code coverage report:

```bash
./vendor/bin/phpunit --coverage-html coverage
```

## Test Structure

- `tests/phpunit/testcases/` - Base test classes
- `tests/phpunit/tests/` - Test files
  - `test-main-class.php` - Tests for the main plugin class
  - `test-settings.php` - Tests for settings management
  - `test-authentication.php` - Tests for authentication

## Best Practices

- One test file per tested class
- One test method per method or feature
- Use mocks for external dependencies
- Follow PHPUnit naming conventions
- Document tests with clear comments

## Troubleshooting

If you encounter errors while running tests:

1. Verify the test database is properly configured
2. Ensure all dependencies are installed (svn, mysql, etc.)
3. Check file and directory permissions
4. Check PHP and WordPress error logs
5. Make sure the test database user has proper permissions

## License

These tests are licensed under the same license as the main plugin (GPL-2.0+).
