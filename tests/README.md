# PurePressureLive Test Suite

This directory contains comprehensive unit and integration tests for the PurePressureLive application.

## Test Structure

- `Unit/` - Unit tests that test individual components in isolation
- `Integration/` - Integration tests that test components working together with real database
- `Fixtures/` - Test data and helper classes
- `bootstrap.php` - Test environment setup

## Running Tests

### Install Dependencies

```bash
composer install
```

### Run All Tests

```bash
vendor/bin/phpunit
```

### Run Specific Test Suite

```bash
# Unit tests only
vendor/bin/phpunit --testsuite Unit

# Integration tests only
vendor/bin/phpunit --testsuite Integration
```

### Run Specific Test Class

```bash
vendor/bin/phpunit tests/Unit/ConfigTest.php
vendor/bin/phpunit tests/Unit/LoginTest.php
```

### Run with Coverage

```bash
vendor/bin/phpunit --coverage-html coverage/
```

## Test Files

### Unit Tests

#### `ConfigTest.php`
Tests for `config.php` covering:
- Environment variable handling (DSN, host, name, charset, user, password)
- DSN construction with fallback defaults
- PDO options configuration
- Empty password handling
- All default values

#### `LoginTest.php`
Tests for `login.php` covering:
- CSRF token generation and validation
- Form input validation (empty fields, missing fields)
- Session management
- Remember me functionality
- HTML escaping for XSS prevention
- Multiple error display
- Email/username field flexibility
- Config deferred loading

### Integration Tests

#### `ConfigIntegrationTest.php`
Integration tests for `config.php` covering:
- Actual PDO connection creation
- Invalid connection handling
- Query execution
- Fetch mode verification
- Exception handling

#### `LoginIntegrationTest.php`
Integration tests for `login.php` covering:
- Complete login flow with database
- Email and username login
- Password verification (password_hash and legacy password columns)
- Failed login scenarios
- Password rehashing logic
- Database query structure

### Fixtures

#### `TestDatabase.php`
Helper class providing:
- Test database schema creation
- Test data insertion
- Data cleanup methods

## Environment Variables

Tests use the following environment variables (configured in `phpunit.xml`):

- `PPLIVE_DB_HOST` - Database host (default: localhost)
- `PPLIVE_DB_NAME` - Database name (default: test_purepressure)
- `PPLIVE_DB_USER` - Database user
- `PPLIVE_DB_PASS` - Database password
- `PPLIVE_DB_CHARSET` - Database charset (default: utf8mb4)

## Test Coverage

The test suite provides comprehensive coverage of:

1. **Configuration Management**
   - Environment variable parsing
   - Default value handling
   - Database connection setup

2. **Authentication**
   - CSRF protection
   - Input validation
   - Password verification
   - Session management

3. **Security**
   - XSS prevention through HTML escaping
   - CSRF token validation
   - Secure password handling

4. **Edge Cases**
   - Missing environment variables
   - Invalid database connections
   - Empty/null values
   - Legacy password formats
   - Password rehashing

## Best Practices

1. **Isolation**: Unit tests don't rely on external dependencies
2. **Fixtures**: Integration tests use SQLite for fast, isolated database testing
3. **Cleanup**: Tests clean up after themselves (temporary files, database records)
4. **Mocking**: External dependencies are mocked where appropriate
5. **Assertions**: Specific, meaningful assertions with clear failure messages

## Contributing

When adding new features:

1. Write unit tests for new functions/methods
2. Write integration tests for complete workflows
3. Ensure all tests pass before committing
4. Maintain test coverage above 80%
5. Follow existing test patterns and naming conventions