# Testing Guide

## Overview

This package uses Pest PHP for testing. The test suite provides comprehensive coverage for the fluent `Columns` API.

## Test File Structure

```
tests/
├── bootstrap.php              # WordPress function mocks and test helpers
├── Pest.php                   # Pest configuration and global helpers
├── TestCase.php               # Base test case class
└── Unit/
    └── ColumnsTest.php        # Main test suite with 30+ tests
```

## Running Tests

### Basic Test Run

```bash
composer test
```

or

```bash
vendor/bin/pest
```

### With Coverage

```bash
composer test:coverage
```

Note: Requires Xdebug or PCOV extension.

### Watch Mode

```bash
composer test:watch
```

or

```bash
vendor/bin/pest --watch
```

### Specific Test File

```bash
vendor/bin/pest tests/Unit/ColumnsTest.php
```

### Filter by Test Name

```bash
vendor/bin/pest --filter="fluent"
vendor/bin/pest --filter="custom renderer"
```

### Parallel Execution

```bash
composer test:parallel
```

## Test Coverage

The test suite covers:

### ✅ Static Factory Pattern
- Creates instance with single post type
- Creates instance with multiple post types
- Converts string to array internally

### ✅ Fluent API
- All methods return self for chaining
- Can chain all methods together
- Named arguments work in any order

### ✅ Adding Columns
- Adds single column
- Adds multiple columns
- Validates empty keys and labels
- Throws exceptions for invalid inputs

### ✅ Sortable Columns
- Makes single column sortable (string)
- Makes multiple columns sortable (array)
- Handles empty sortable columns gracefully

### ✅ Custom Renderers
- Uses custom renderer when provided
- Uses complex rendering logic
- Falls back to default renderer
- Renders nothing for undefined columns
- Handles missing post meta

### ✅ WordPress Integration
- Registers filters and actions correctly
- Handles multiple post types
- Transforms 'post' → 'posts'
- Transforms 'page' → 'pages'
- Respects custom priority

### ✅ Named Arguments
- Accepts arguments in any order
- Works with all methods
- Self-documenting code

### ✅ Real-world Usage
- Complete product columns setup
- Multiple columns with renderers
- Sortable columns
- Custom priority

## WordPress Function Mocks

The test suite includes mocks for essential WordPress functions:

- `add_filter()` - Registers filters
- `add_action()` - Registers actions
- `get_post_meta()` - Retrieves post meta
- `apply_filters()` - Applies registered filters
- `do_action()` - Executes registered actions

## Test Helpers

### `clearWordPressHooks()`
Clears all registered WordPress hooks and post meta between tests.

### `set_test_post_meta($post_id, $key, $value)`
Sets post meta for testing purposes.

```php
set_test_post_meta(123, 'product_price', '99.99');
```

## Example: Writing Tests

### Testing the Fluent API

```php
it('chains all methods', function () {
    $columns = Columns::for(postTypes: 'product')
        ->add(key: 'price', label: 'Price')
        ->sortable(keys: 'price')
        ->priority(value: 15);
    
    expect($columns)->toBeInstanceOf(Columns::class);
});
```

### Testing Custom Renderers

```php
it('uses custom renderer', function () {
    $columns = Columns::for(postTypes: 'product')
        ->add(
            key: 'price',
            label: 'Price',
            render: fn(int $postId): string => '$' . get_post_meta($postId, 'price', true)
        );
    
    set_test_post_meta(123, 'price', '99');
    
    ob_start();
    $columns->renderColumn('price', 123);
    $output = ob_get_clean();
    
    expect($output)->toBe('$99');
});
```

### Testing WordPress Integration

```php
it('registers filters for post type', function () {
    global $wp_filter;
    
    Columns::for(postTypes: 'product')
        ->add(key: 'price', label: 'Price')
        ->register();
    
    expect($wp_filter)->toHaveKey('manage_product_posts_columns');
});
```

## CI/CD Integration

### GitHub Actions Example

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    strategy:
      matrix:
        php: [8.1, 8.2, 8.3]
    
    steps:
      - uses: actions/checkout@v4
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          coverage: pcov
      
      - name: Install dependencies
        run: composer install --prefer-dist --no-progress
      
      - name: Run tests
        run: composer test
      
      - name: Run tests with coverage
        run: composer test:coverage
```

## Installing Coverage Driver

To enable code coverage reporting, install PCOV or Xdebug:

### Using PECL
```bash
pecl install pcov
```

### Using Homebrew (macOS)
```bash
brew install php-pcov
# or
brew install php-xdebug
```

### Verify Installation
```bash
php -m | grep -i pcov
# or
php -m | grep -i xdebug
```

## Tips

1. **Isolation**: Each test runs in isolation with fresh WordPress globals
2. **Output Buffering**: Use `ob_start()` and `ob_get_clean()` to capture echoed output
3. **Named Arguments**: Tests use named arguments for clarity
4. **Descriptive Names**: Test names follow "it does something" pattern

## Test Organization

Tests are organized using Pest's `describe()` blocks:

- **Static Factory** - Factory method tests
- **Fluent API** - Chaining tests
- **Adding Columns** - Column addition and validation
- **Sortable Columns** - Sortable functionality
- **Custom Renderers** - Rendering logic tests
- **WordPress Integration** - Hook registration tests
- **Named Arguments** - Named parameter tests
- **Real-world Usage** - Complete usage examples

## Troubleshooting

### Pest Not Found
```bash
composer require --dev pestphp/pest
composer dump-autoload
```

### WordPress Functions Undefined
The `tests/bootstrap.php` file provides mocks. Ensure it's loaded via `phpunit.xml`:
```xml
bootstrap="tests/bootstrap.php"
```

### Tests Not Running
Check that `phpunit.xml` exists and is properly configured.

### Coverage Not Working
Install PCOV or Xdebug:
```bash
pecl install pcov
php -m | grep pcov
```

## Running Specific Test Groups

```bash
# Run only API tests
vendor/bin/pest --filter="Fluent API"

# Run only integration tests
vendor/bin/pest --filter="WordPress Integration"

# Run tests for custom renderers
vendor/bin/pest --filter="Custom Renderers"
```

## Writing New Tests

When adding new features, follow this pattern:

```php
describe('New Feature', function () {
    
    it('does something specific', function () {
        // Arrange
        $columns = Columns::for(postTypes: 'product');
        
        // Act
        $result = $columns->newFeature();
        
        // Assert
        expect($result)->toBe('expected');
    });
    
});
```

Use named arguments for clarity and maintain consistency with existing tests.
