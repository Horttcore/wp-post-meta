<div align="center">

![WordPress Post Meta](.github/banner.svg)

[![Tests](https://github.com/ralfhortt/wp-post-meta/actions/workflows/tests.yml/badge.svg)](https://github.com/ralfhortt/wp-post-meta/actions/workflows/tests.yml)
[![codecov](https://codecov.io/gh/ralfhortt/wp-post-meta/graph/badge.svg)](https://codecov.io/gh/ralfhortt/wp-post-meta)
[![PHP Version](https://img.shields.io/packagist/php-v/ralfhortt/wp-post-meta)](https://packagist.org/packages/ralfhortt/wp-post-meta)
[![Latest Version](https://img.shields.io/packagist/v/ralfhortt/wp-post-meta)](https://packagist.org/packages/ralfhortt/wp-post-meta)
[![License](https://img.shields.io/packagist/l/ralfhortt/wp-post-meta)](https://github.com/ralfhortt/wp-post-meta/blob/main/LICENSE)

</div>

# WordPress Post Meta Utilities

A modern, fluent WordPress Composer package for working with post meta fields in the REST API and admin list tables.

## Features

- 📡 **REST API Meta** - Expose post meta in WordPress REST API with type conversion
- 📋 **Admin Columns** - Add custom columns to admin list tables  
- 🔐 **Authorization** - Fine-grained permission control
- 🎯 **Type Helpers** - Built-in support for strings, booleans, integers, numbers, arrays, dates
- ✨ **Fluent API** - Chainable, self-documenting code
- 🚀 **Zero Boilerplate** - No class extension needed
- ✅ **Fully Tested** - 141 tests passing

## Requirements

- PHP 8.1+
- WordPress 5.0+

## Installation

```bash
composer require ralfhortt/wp-post-meta
```

## Quick Start

### REST API Meta Fields

```php
use RalfHortt\Meta\Meta;

Meta::for(objectSubtypes: 'product')
    ->addString(key: 'sku', description: 'Product SKU')
    ->addNumber(key: 'price', description: 'Product price')
    ->addBoolean(key: 'featured', description: 'Is featured')
    ->needsCapability(capability: 'edit_posts')
    ->register();
```

Your REST API responses now include these fields:

```json
{
  "id": 123,
  "title": {...},
  "meta": {
    "sku": "PROD-123",
    "price": 99.99,
    "featured": true
  }
}
```

### REST API + Admin Columns

Add columns to admin list tables at the same time:

```php
Meta::for(objectSubtypes: 'product')
    ->addString(key: 'sku', description: 'Product SKU')
    ->addColumn(key: 'sku', label: __('SKU', 'plugin'), sortable: true)
    
    ->addNumber(key: 'price', description: 'Product price')
    ->addColumn(
        key: 'price',
        label: __('Price', 'plugin'),
        render: fn($postId) => '$' . number_format(get_post_meta($postId, 'price', true), 2),
        sortable: true
    )
    
    ->needsCapability(capability: 'edit_posts')
    ->register();
```

### REST API + Admin Columns + Quick Edit

Enable quick editing of meta fields directly from the admin list table:

```php
Meta::for(objectSubtypes: 'product')
    ->addString(key: 'sku', description: 'Product SKU')
    ->addColumn(key: 'sku', label: __('SKU', 'plugin'), sortable: true)
    ->showInQuickEdit(keys: 'sku')
    
    ->addNumber(key: 'price', description: 'Product price')
    ->addColumn(
        key: 'price',
        label: __('Price', 'plugin'),
        render: fn($postId) => '$' . number_format(get_post_meta($postId, 'price', true), 2),
        sortable: true
    )
    ->showInQuickEdit(keys: 'price')
    
    ->needsCapability(capability: 'edit_posts')
    ->register();
```

## REST API Usage

### Type Helpers

#### String Fields
```php
Meta::for(objectSubtypes: 'product')
    ->addString(key: 'sku', description: 'Product SKU')
    ->register();
```

#### Boolean Fields
Automatically converts between boolean (API) and string (storage):
```php
Meta::for(objectSubtypes: 'product')
    ->addBoolean(key: 'featured', description: 'Is featured')
    ->register();
```

#### Integer & Number Fields
```php
Meta::for(objectSubtypes: 'product')
    ->addInteger(key: 'stock', description: 'Stock quantity')
    ->addNumber(key: 'price', description: 'Product price')
    ->register();
```

#### Array Fields
```php
Meta::for(objectSubtypes: 'product')
    ->addArray(key: 'tags', description: 'Product tags')
    ->register();
```

#### Date Fields
Automatically converts to/from ISO 8601:
```php
Meta::for(objectSubtypes: 'event')
    ->addDate(
        key: 'event_date',
        description: 'Event date',
        inputFormat: 'Y-m-d'  // Storage format
    )
    ->addDate(
        key: 'created_at',
        description: 'Created timestamp',
        inputFormat: 'timestamp'  // Unix timestamp
    )
    ->register();
```

### Authorization

Control who can edit meta fields via the REST API:

```php
// Require edit_posts capability
Meta::for(objectSubtypes: 'product')
    ->addString(key: 'sku')
    ->needsCapability(capability: 'edit_posts')
    ->register();

// Require manage_options (admin) capability
Meta::for(objectSubtypes: 'product')
    ->addString(key: 'internal_note')
    ->needsCapability(capability: 'manage_options')
    ->register();

// Custom WordPress capability
Meta::for(objectSubtypes: 'product')
    ->addNumber(key: 'wholesale_price')
    ->needsCapability(capability: 'manage_woocommerce')
    ->register();

// Per-field custom authorization callback
Meta::for(objectSubtypes: 'product')
    ->add(
        key: 'cost',
        type: 'number',
        authCallback: function($allowed, $context, $objectId) {
            return current_user_can('manage_shop') && $objectId > 0;
        }
    )
    ->register();
```

### Custom Callbacks

#### Transform on Read
```php
Meta::for(objectSubtypes: 'product')
    ->add(
        key: 'price',
        type: 'object',
        getCallback: function ($object) {
            $price = get_post_meta($object['id'], 'price', true);
            $tax = get_post_meta($object['id'], 'tax_rate', true);
            return [
                'net' => (float) $price,
                'gross' => (float) $price * (1 + (float) $tax)
            ];
        }
    )
    ->register();
```

#### Validate on Write
```php
Meta::for(objectSubtypes: 'product')
    ->add(
        key: 'sku',
        type: 'string',
        updateCallback: function ($value, $object) {
            if (!preg_match('/^[A-Z]{3}-\d{4}$/', $value)) {
                return new \WP_Error('invalid_sku', 'Invalid SKU format');
            }
            return update_post_meta($object->ID, 'sku', strtoupper($value));
        }
    )
    ->register();
```

### Quick Edit

Enable inline editing of meta fields from the admin list table:

```php
Meta::for(objectSubtypes: 'product')
    ->addString(key: 'sku', description: 'Product SKU')
    ->addNumber(key: 'price', description: 'Product price')
    ->addBoolean(key: 'featured', description: 'Is featured')
    
    // Enable quick edit for specific fields
    ->showInQuickEdit(keys: ['sku', 'price', 'featured'])
    
    ->needsCapability(capability: 'edit_posts')
    ->register();
```

Quick edit automatically renders appropriate input types:
- **String** fields → text input
- **Number/Integer** fields → number input
- **Boolean** fields → checkbox

### Editor Meta Boxes

Add fields to Gutenberg sidebar panels for inline editing in the block editor:

```php
Meta::for(objectSubtypes: 'product')
    ->addString(key: 'sku', description: 'Product SKU')
    ->addNumber(key: 'price', description: 'Product price')
    ->addInteger(key: 'stock', description: 'Stock quantity')
    
    // Group fields in sidebar meta box
    ->showInEditor(
        keys: ['sku', 'price', 'stock'],
        title: __('Product Details', 'plugin')
    )
    
    ->needsCapability(capability: 'edit_posts')
    ->register();
```

**Multiple Meta Boxes:**

```php
Meta::for(objectSubtypes: 'product')
    // Basic information
    ->addString(key: 'sku', description: 'SKU')
    ->addNumber(key: 'price', description: 'Price')
    ->showInEditor(
        keys: ['sku', 'price'],
        title: __('Basic Info', 'plugin'),
        metaBoxId: 'product-basic',
        context: 'side'  // Sidebar (default)
    )
    
    // Inventory details in main content area
    ->addInteger(key: 'stock', description: 'Stock')
    ->addBoolean(key: 'backorder', description: 'Allow backorders')
    ->showInEditor(
        keys: ['stock', 'backorder'],
        title: __('Inventory', 'plugin'),
        metaBoxId: 'product-inventory',
        context: 'normal'  // Main content area
    )
    
    ->register();
```

**Supported Field Types:**
- String → Text input
- Number/Integer → Number input
- Boolean → Toggle switch

**Note:** Array and object types are automatically skipped (Gutenberg sidebar only supports simple types).

### Complete Example

```php
use RalfHortt\Meta\Meta;

add_action('init', function () {
    Meta::for(objectSubtypes: 'product')
        // SKU field - REST API + admin column + quick edit + editor
        ->addString(key: 'sku', description: 'Product SKU')
        ->addColumn(key: 'sku', label: __('SKU', 'plugin'), sortable: true)
        ->showInQuickEdit(keys: 'sku')
        ->showInEditor(
            keys: 'sku',
            title: __('Product SKU', 'plugin'),
            metaBoxId: 'product-sku'
        )
        
        // Price field - REST API + formatted column + quick edit + editor
        ->addNumber(key: 'price', description: 'Product price')
        ->addColumn(
            key: 'price',
            label: __('Price', 'plugin'),
            render: fn($postId) => '$' . number_format(get_post_meta($postId, 'price', true), 2),
            sortable: true
        )
        ->showInQuickEdit(keys: 'price')
        ->showInEditor(
            keys: 'price',
            title: __('Pricing', 'plugin'),
            metaBoxId: 'product-pricing'
        )
        
        // Stock quantity - all features
        ->addInteger(key: 'stock', description: 'Stock quantity')
        ->addColumn(key: 'stock', label: __('Stock', 'plugin'), sortable: true)
        ->showInQuickEdit(keys: 'stock')
        ->showInEditor(
            keys: 'stock',
            title: __('Inventory', 'plugin'),
            metaBoxId: 'product-inventory'
        )
        
        // Featured flag - REST + column (no quick edit/editor for this one)
        ->addBoolean(key: 'featured', description: 'Is featured')
        ->addColumn(key: 'featured', label: __('Featured', 'plugin'))
        
        // Tags - REST only (arrays don't support quick edit/editor)
        ->addArray(key: 'tags', description: 'Product tags')
        
        // Release date
        ->addDate(key: 'release_date', description: 'Release date')
        ->addColumn(key: 'release_date', label: __('Release', 'plugin'))
        
        ->needsCapability(capability: 'edit_posts')
        ->register();
});
```

## Admin Columns

For standalone admin columns without REST API, use the `Columns` class:

```php
use RalfHortt\Meta\Columns;

Columns::for(postTypes: 'product')
    ->add(key: 'sku', label: __('SKU', 'plugin'))
    ->add(key: 'price', label: __('Price', 'plugin'))
    ->sortable(keys: ['sku', 'price'])
    ->register();
```

### Type-Specific Helpers

```php
Columns::for(postTypes: 'product')
    // Currency formatting
    ->addCurrency(
        key: 'price',
        label: __('Price', 'plugin'),
        decimals: 2,
        currencySymbol: '$'
    )
    
    // Date formatting
    ->addDate(
        key: 'event_date',
        label: __('Date', 'plugin'),
        format: 'F j, Y'
    )
    
    // Boolean display
    ->addBoolean(
        key: 'featured',
        label: __('Featured', 'plugin'),
        trueLabel: 'Yes',
        falseLabel: 'No'
    )
    
    // Image thumbnails
    ->addImage(
        key: 'thumbnail',
        label: __('Image', 'plugin'),
        width: 50,
        height: 50
    )
    
    // Array/list display
    ->addList(
        key: 'tags',
        label: __('Tags', 'plugin'),
        separator: ', ',
        limit: 3
    )
    
    ->sortable(keys: ['price', 'event_date'])
    ->register();
```

### Custom Renderers

```php
Columns::for(postTypes: 'product')
    ->add(
        key: 'stock',
        label: __('Stock', 'plugin'),
        render: function(int $postId): string {
            $stock = (int) get_post_meta($postId, 'stock', true);
            $color = $stock > 10 ? 'green' : ($stock > 0 ? 'orange' : 'red');
            return sprintf('<span style="color: %s">%d</span>', $color, $stock);
        }
    )
    ->sortable(keys: 'stock')
    ->register();
```

## API Reference

### Meta Class

#### Factory
- `Meta::for(string $objectType = 'post', string|array $objectSubtypes = []): self`

#### Type Helpers
- `addString(key, description, getCallback, updateCallback, authCallback): self`
- `addBoolean(key, description, authCallback): self`
- `addInteger(key, description, authCallback): self`
- `addNumber(key, description, authCallback): self`
- `addArray(key, description, authCallback): self`
- `addDate(key, description, inputFormat, authCallback): self`

#### Low-Level
- `add(key, type, description, getCallback, updateCallback, authCallback): self`

#### Authorization
- `needsCapability(capability): self` - Require specific WordPress capability

#### Admin Integration
- `addColumn(key, label, render, sortable): self` - Add admin list table column
- `showInQuickEdit(keys): self` - Enable quick edit for field(s)
- `showInEditor(keys, title, metaBoxId, context): self` - Add Gutenberg editor meta box

#### Registration
- `register(): void`

### Columns Class

#### Factory
- `Columns::for(string|array $postTypes): self`

#### Basic
- `add(key, label, render): self`

#### Type Helpers
- `addCurrency(key, label, decimals, currencySymbol, symbolPosition): self`
- `addDate(key, label, format, inputFormat): self`
- `addBoolean(key, label, trueLabel, falseLabel): self`
- `addImage(key, label, width, height, isAttachmentId): self`
- `addList(key, label, separator, limit): self`

#### Configuration
- `sortable(keys): self`
- `priority(value): self`

#### Registration
- `register(): void`

## Development

### Running Tests

```bash
composer test              # Run all tests
composer test:coverage     # With coverage (requires Xdebug/PCOV)
```

## Changelog

### v2.0.0
- Complete rewrite with fluent API
- Added REST API meta field registration (`Meta` class)
- Added column integration to `Meta` class
- Removed abstract class pattern
- Added type-specific helpers
- PHP 8.1+ with named arguments

### v1.0.0
- Initial release with abstract class pattern

## License

MIT

## Author

Ralf Hortt - [mail@ralfhortt.dev](mailto:mail@ralfhortt.dev)
