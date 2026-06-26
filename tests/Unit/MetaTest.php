<?php

use RalfHortt\Meta\Meta;

beforeEach(function () {
    clearWordPressHooks();
});

describe('Meta', function () {

    describe('Static Factory', function () {

        it('can create instance with static factory', function () {
            $meta = Meta::for(objectSubtypes: 'product');
            expect($meta)->toBeInstanceOf(Meta::class);
        });

        it('can create instance for multiple post types', function () {
            $meta = Meta::for(objectSubtypes: ['product', 'event']);
            expect($meta)->toBeInstanceOf(Meta::class);
        });

        it('defaults to post object type', function () {
            $meta = Meta::for();
            expect($meta)->toBeInstanceOf(Meta::class);
        });

    });

    describe('Fluent API', function () {

        it('returns self for chaining with add()', function () {
            $meta = Meta::for(objectSubtypes: 'product');
            $result = $meta->add(key: 'price', type: 'number');
            expect($result)->toBe($meta);
        });

        it('returns self for chaining with addString()', function () {
            $meta = Meta::for(objectSubtypes: 'product');
            $result = $meta->addString(key: 'sku');
            expect($result)->toBe($meta);
        });

        it('returns self for chaining with needsCapability()', function () {
            $meta = Meta::for(objectSubtypes: 'product');
            $result = $meta->needsCapability(capability: 'edit_posts');
            expect($result)->toBe($meta);
        });

        it('can chain all methods together', function () {
            $meta = Meta::for(objectSubtypes: 'product')
                ->addString(key: 'sku', description: 'Product SKU')
                ->addNumber(key: 'price', description: 'Product price')
                ->addBoolean(key: 'featured', description: 'Is featured')
                ->needsCapability(capability: 'edit_posts');

            expect($meta)->toBeInstanceOf(Meta::class);
        });

    });

    describe('Adding Fields', function () {

        it('adds single field', function () {
            $meta = Meta::for(objectSubtypes: 'product')
                ->add(key: 'price', type: 'number', description: 'Product price');

            $fields = $meta->getFields();

            expect($fields)->toHaveKey('price')
                ->and($fields['price']['type'])->toBe('number')
                ->and($fields['price']['description'])->toBe('Product price');
        });

        it('adds multiple fields', function () {
            $meta = Meta::for(objectSubtypes: 'product')
                ->add(key: 'price', type: 'number')
                ->add(key: 'sku', type: 'string')
                ->add(key: 'featured', type: 'boolean');

            $fields = $meta->getFields();

            expect($fields)->toHaveKey('price')
                ->and($fields)->toHaveKey('sku')
                ->and($fields)->toHaveKey('featured');
        });

        it('throws exception for empty key', function () {
            expect(fn() => Meta::for(objectSubtypes: 'product')
                ->add(key: '', type: 'string')
            )->toThrow(\InvalidArgumentException::class, 'Meta key cannot be empty');
        });

        it('throws exception for invalid type', function () {
            expect(fn() => Meta::for(objectSubtypes: 'product')
                ->add(key: 'test', type: 'invalid')
            )->toThrow(\InvalidArgumentException::class);
        });

        it('accepts valid types', function () {
            $meta = Meta::for(objectSubtypes: 'product')
                ->add(key: 'string_field', type: 'string')
                ->add(key: 'boolean_field', type: 'boolean')
                ->add(key: 'integer_field', type: 'integer')
                ->add(key: 'number_field', type: 'number')
                ->add(key: 'array_field', type: 'array')
                ->add(key: 'object_field', type: 'object');

            $fields = $meta->getFields();

            expect(count($fields))->toBe(6);
        });

    });

    describe('Type-specific Helpers', function () {

        describe('addString()', function () {

            it('adds string field with correct type', function () {
                $meta = Meta::for(objectSubtypes: 'product')
                    ->addString(key: 'sku', description: 'Product SKU');

                $fields = $meta->getFields();

                expect($fields['sku']['type'])->toBe('string')
                    ->and($fields['sku']['description'])->toBe('Product SKU');
            });

            it('returns self for chaining', function () {
                $meta = Meta::for(objectSubtypes: 'product');
                $result = $meta->addString(key: 'sku');

                expect($result)->toBe($meta);
            });

        });

        describe('addText()', function () {

            it('adds multiline string field with correct type', function () {
                $meta = Meta::for(objectSubtypes: 'product')
                    ->addText(key: 'description', description: 'Product description');

                $fields = $meta->getFields();

                expect($fields['description']['type'])->toBe('string')
                    ->and($fields['description']['description'])->toBe('Product description')
                    ->and($fields['description']['multiline'])->toBeTrue();
            });

            it('returns self for chaining', function () {
                $meta = Meta::for(objectSubtypes: 'product');
                $result = $meta->addText(key: 'description');

                expect($result)->toBe($meta);
            });

            it('generates TextareaControl in block editor', function () {
                global $wp_scripts, $typenow;

                $typenow = 'product';

                Meta::for(objectSubtypes: 'product')
                    ->addText(key: 'description', description: 'Product description')
                    ->showInEditor(keys: 'description', title: 'Product Details')
                    ->register();

                do_action('enqueue_block_editor_assets');

                $inlineScript = $wp_scripts['meta-boxes-product']['inline']['after'][0] ?? '';

                expect($inlineScript)->toContain('TextareaControl')
                    ->and($inlineScript)->not->toContain('el(TextControl, {
                label: \'Product description\'');
            });

            it('renders textarea in quick edit', function () {
                global $wp_filter;

                Meta::for(objectSubtypes: 'product')
                    ->addText(key: 'description', description: 'Product description')
                    ->addColumn(key: 'description', label: 'Description')
                    ->showInQuickEdit(keys: 'description')
                    ->register();

                ob_start();
                do_action('quick_edit_custom_box', 'description', 'product');
                $output = ob_get_clean();

                expect($output)->toContain('<textarea')
                    ->and($output)->not->toContain('<input type="text"');
            });

        });

        describe('addBoolean()', function () {

            it('adds boolean field with correct type', function () {
                $meta = Meta::for(objectSubtypes: 'product')
                    ->addBoolean(key: 'featured', description: 'Is featured');

                $fields = $meta->getFields();

                expect($fields['featured']['type'])->toBe('boolean')
                    ->and($fields['featured']['description'])->toBe('Is featured');
            });

            it('has get_callback that converts truthy values', function () {
                $meta = Meta::for(objectSubtypes: 'product')
                    ->addBoolean(key: 'featured');

                $fields = $meta->getFields();
                $callback = $fields['featured']['get_callback'];

                set_test_post_meta(123, 'featured', '1');
                $result = $callback(['id' => 123], 'featured', null);

                expect($result)->toBeTrue();
            });

            it('has get_callback that converts falsy values', function () {
                $meta = Meta::for(objectSubtypes: 'product')
                    ->addBoolean(key: 'featured');

                $fields = $meta->getFields();
                $callback = $fields['featured']['get_callback'];

                set_test_post_meta(123, 'featured', '0');
                $result = $callback(['id' => 123], 'featured', null);

                expect($result)->toBeFalse();
            });

            it('has update_callback that stores as string', function () {
                $meta = Meta::for(objectSubtypes: 'product')
                    ->addBoolean(key: 'featured');

                $fields = $meta->getFields();
                $callback = $fields['featured']['update_callback'];

                $object = (object) ['ID' => 123];
                $callback(true, $object, 'featured');

                expect(get_post_meta(123, 'featured', true))->toBe('1');
            });

            it('returns self for chaining', function () {
                $meta = Meta::for(objectSubtypes: 'product');
                $result = $meta->addBoolean(key: 'featured');

                expect($result)->toBe($meta);
            });

        });

        describe('addInteger()', function () {

            it('adds integer field with correct type', function () {
                $meta = Meta::for(objectSubtypes: 'product')
                    ->addInteger(key: 'stock', description: 'Stock quantity');

                $fields = $meta->getFields();

                expect($fields['stock']['type'])->toBe('integer')
                    ->and($fields['stock']['description'])->toBe('Stock quantity');
            });

            it('has get_callback that converts to integer', function () {
                $meta = Meta::for(objectSubtypes: 'product')
                    ->addInteger(key: 'stock');

                $fields = $meta->getFields();
                $callback = $fields['stock']['get_callback'];

                set_test_post_meta(123, 'stock', '42');
                $result = $callback(['id' => 123], 'stock', null);

                expect($result)->toBe(42)
                    ->and($result)->toBeInt();
            });

            it('has update_callback that stores as integer', function () {
                $meta = Meta::for(objectSubtypes: 'product')
                    ->addInteger(key: 'stock');

                $fields = $meta->getFields();
                $callback = $fields['stock']['update_callback'];

                $object = (object) ['ID' => 123];
                $callback('42', $object, 'stock');

                expect(get_post_meta(123, 'stock', true))->toBe(42);
            });

            it('returns self for chaining', function () {
                $meta = Meta::for(objectSubtypes: 'product');
                $result = $meta->addInteger(key: 'stock');

                expect($result)->toBe($meta);
            });

        });

        describe('addNumber()', function () {

            it('adds number field with correct type', function () {
                $meta = Meta::for(objectSubtypes: 'product')
                    ->addNumber(key: 'price', description: 'Product price');

                $fields = $meta->getFields();

                expect($fields['price']['type'])->toBe('number')
                    ->and($fields['price']['description'])->toBe('Product price');
            });

            it('has get_callback that converts to float', function () {
                $meta = Meta::for(objectSubtypes: 'product')
                    ->addNumber(key: 'price');

                $fields = $meta->getFields();
                $callback = $fields['price']['get_callback'];

                set_test_post_meta(123, 'price', '99.99');
                $result = $callback(['id' => 123], 'price', null);

                expect($result)->toBe(99.99)
                    ->and($result)->toBeFloat();
            });

            it('has update_callback that stores as float', function () {
                $meta = Meta::for(objectSubtypes: 'product')
                    ->addNumber(key: 'price');

                $fields = $meta->getFields();
                $callback = $fields['price']['update_callback'];

                $object = (object) ['ID' => 123];
                $callback('99.99', $object, 'price');

                expect(get_post_meta(123, 'price', true))->toBe(99.99);
            });

            it('returns self for chaining', function () {
                $meta = Meta::for(objectSubtypes: 'product');
                $result = $meta->addNumber(key: 'price');

                expect($result)->toBe($meta);
            });

        });

        describe('addArray()', function () {

            it('adds array field with correct type', function () {
                $meta = Meta::for(objectSubtypes: 'product')
                    ->addArray(key: 'tags', description: 'Product tags');

                $fields = $meta->getFields();

                expect($fields['tags']['type'])->toBe('array')
                    ->and($fields['tags']['description'])->toBe('Product tags');
            });

            it('has get_callback that returns array', function () {
                $meta = Meta::for(objectSubtypes: 'product')
                    ->addArray(key: 'tags');

                $fields = $meta->getFields();
                $callback = $fields['tags']['get_callback'];

                set_test_post_meta(123, 'tags', ['tag1', 'tag2']);
                $result = $callback(['id' => 123], 'tags', null);

                expect($result)->toBe(['tag1', 'tag2'])
                    ->and($result)->toBeArray();
            });

            it('has get_callback that returns empty array for non-array value', function () {
                $meta = Meta::for(objectSubtypes: 'product')
                    ->addArray(key: 'tags');

                $fields = $meta->getFields();
                $callback = $fields['tags']['get_callback'];

                set_test_post_meta(123, 'tags', 'not-an-array');
                $result = $callback(['id' => 123], 'tags', null);

                expect($result)->toBe([]);
            });

            it('has update_callback that stores as array', function () {
                $meta = Meta::for(objectSubtypes: 'product')
                    ->addArray(key: 'tags');

                $fields = $meta->getFields();
                $callback = $fields['tags']['update_callback'];

                $object = (object) ['ID' => 123];
                $callback(['tag1', 'tag2'], $object, 'tags');

                expect(get_post_meta(123, 'tags', true))->toBe(['tag1', 'tag2']);
            });

            it('returns self for chaining', function () {
                $meta = Meta::for(objectSubtypes: 'product');
                $result = $meta->addArray(key: 'tags');

                expect($result)->toBe($meta);
            });

        });

        describe('addDate()', function () {

            it('adds date field with string type', function () {
                $meta = Meta::for(objectSubtypes: 'event')
                    ->addDate(key: 'event_date', description: 'Event date');

                $fields = $meta->getFields();

                expect($fields['event_date']['type'])->toBe('string')
                    ->and($fields['event_date']['description'])->toBe('Event date');
            });

            it('has get_callback that converts date to ISO 8601', function () {
                $meta = Meta::for(objectSubtypes: 'event')
                    ->addDate(key: 'event_date');

                $fields = $meta->getFields();
                $callback = $fields['event_date']['get_callback'];

                set_test_post_meta(123, 'event_date', '2024-03-15');
                $result = $callback(['id' => 123], 'event_date', null);

                expect($result)->toContain('2024-03-15');
            });

            it('has get_callback that handles timestamps', function () {
                $meta = Meta::for(objectSubtypes: 'event')
                    ->addDate(key: 'event_date', inputFormat: 'timestamp');

                $fields = $meta->getFields();
                $callback = $fields['event_date']['get_callback'];

                set_test_post_meta(123, 'event_date', '1710460800'); // 2024-03-15 00:00:00 UTC
                $result = $callback(['id' => 123], 'event_date', null);

                expect($result)->toContain('2024-03-15');
            });

            it('has get_callback that returns null for empty date', function () {
                $meta = Meta::for(objectSubtypes: 'event')
                    ->addDate(key: 'event_date');

                $fields = $meta->getFields();
                $callback = $fields['event_date']['get_callback'];

                $result = $callback(['id' => 123], 'event_date', null);

                expect($result)->toBeNull();
            });

            it('has update_callback that stores in specified format', function () {
                $meta = Meta::for(objectSubtypes: 'event')
                    ->addDate(key: 'event_date', inputFormat: 'Y-m-d');

                $fields = $meta->getFields();
                $callback = $fields['event_date']['update_callback'];

                $object = (object) ['ID' => 123];
                $callback('2024-03-15T10:30:00+00:00', $object, 'event_date');

                expect(get_post_meta(123, 'event_date', true))->toBe('2024-03-15');
            });

            it('has update_callback that deletes meta for empty value', function () {
                $meta = Meta::for(objectSubtypes: 'event')
                    ->addDate(key: 'event_date');

                $fields = $meta->getFields();
                $callback = $fields['event_date']['update_callback'];

                set_test_post_meta(123, 'event_date', '2024-03-15');
                $object = (object) ['ID' => 123];
                $callback('', $object, 'event_date');

                expect(get_post_meta(123, 'event_date', true))->toBe('');
            });

            it('returns self for chaining', function () {
                $meta = Meta::for(objectSubtypes: 'event');
                $result = $meta->addDate(key: 'event_date');

                expect($result)->toBe($meta);
            });

        });

    });

    describe('Authorization', function () {

        describe('needsCapability()', function () {

            it('adds auth_callback requiring specified capability', function () {
                $meta = Meta::for(objectSubtypes: 'product')
                    ->addString(key: 'sku')
                    ->needsCapability(capability: 'edit_posts');

                $fields = $meta->getFields();
                $callback = $fields['sku']['auth_callback'];

                set_current_user_capabilities(['edit_posts']);
                expect($callback(true, 'edit'))->toBeTrue();
            });

            it('denies access without specified capability', function () {
                $meta = Meta::for(objectSubtypes: 'product')
                    ->addString(key: 'sku')
                    ->needsCapability(capability: 'edit_posts');

                $fields = $meta->getFields();
                $callback = $fields['sku']['auth_callback'];

                set_current_user_capabilities([]);
                expect($callback(true, 'edit'))->toBeFalse();
            });

            it('allows view context without restrictions', function () {
                $meta = Meta::for(objectSubtypes: 'product')
                    ->addString(key: 'sku')
                    ->needsCapability(capability: 'edit_posts');

                $fields = $meta->getFields();
                $callback = $fields['sku']['auth_callback'];

                set_current_user_capabilities([]);
                expect($callback(true, 'view'))->toBeTrue();
            });

            it('works with admin capability', function () {
                $meta = Meta::for(objectSubtypes: 'product')
                    ->addString(key: 'internal_note')
                    ->needsCapability(capability: 'manage_options');

                $fields = $meta->getFields();
                $callback = $fields['internal_note']['auth_callback'];

                set_current_user_capabilities(['manage_options']);
                expect($callback(true, 'edit'))->toBeTrue();
            });

            it('denies access without admin capability', function () {
                $meta = Meta::for(objectSubtypes: 'product')
                    ->addString(key: 'internal_note')
                    ->needsCapability(capability: 'manage_options');

                $fields = $meta->getFields();
                $callback = $fields['internal_note']['auth_callback'];

                set_current_user_capabilities(['edit_posts']);
                expect($callback(true, 'edit'))->toBeFalse();
            });

        });

        it('does not override existing auth_callback', function () {
            $customAuth = fn() => true;

            $meta = Meta::for(objectSubtypes: 'product')
                ->add(key: 'sku', type: 'string', authCallback: $customAuth)
                ->needsCapability(capability: 'edit_posts');

            $fields = $meta->getFields();

            expect($fields['sku']['auth_callback'])->toBe($customAuth);
        });

    });

    describe('WordPress Integration', function () {

        it('registers meta field with register_post_meta', function () {
            global $wp_registered_post_meta;

            Meta::for(objectSubtypes: 'product')
                ->addString(key: 'sku', description: 'Product SKU')
                ->register();

            expect($wp_registered_post_meta)->toHaveKey('product')
                ->and($wp_registered_post_meta['product'])->toHaveKey('sku');
        });

        it('registers multiple meta fields', function () {
            global $wp_registered_post_meta;

            Meta::for(objectSubtypes: 'product')
                ->addString(key: 'sku')
                ->addNumber(key: 'price')
                ->addBoolean(key: 'featured')
                ->register();

            expect($wp_registered_post_meta['product'])->toHaveKey('sku')
                ->and($wp_registered_post_meta['product'])->toHaveKey('price')
                ->and($wp_registered_post_meta['product'])->toHaveKey('featured');
        });

        it('registers with correct type and description', function () {
            global $wp_registered_post_meta;

            Meta::for(objectSubtypes: 'product')
                ->addNumber(key: 'price', description: 'Product price')
                ->register();

            $meta = $wp_registered_post_meta['product']['price'];

            expect($meta['type'])->toBe('number')
                ->and($meta['description'])->toBe('Product price');
        });

    });

    describe('Custom Callbacks', function () {

        it('accepts custom get_callback', function () {
            $customGet = fn($object) => 'custom value';

            $meta = Meta::for(objectSubtypes: 'product')
                ->add(key: 'custom', type: 'string', getCallback: $customGet);

            $fields = $meta->getFields();

            expect($fields['custom']['get_callback'])->toBe($customGet);
        });

        it('accepts custom update_callback', function () {
            $customUpdate = fn($value, $object) => true;

            $meta = Meta::for(objectSubtypes: 'product')
                ->add(key: 'custom', type: 'string', updateCallback: $customUpdate);

            $fields = $meta->getFields();

            expect($fields['custom']['update_callback'])->toBe($customUpdate);
        });

        it('accepts custom auth_callback', function () {
            $customAuth = fn() => true;

            $meta = Meta::for(objectSubtypes: 'product')
                ->add(key: 'custom', type: 'string', authCallback: $customAuth);

            $fields = $meta->getFields();

            expect($fields['custom']['auth_callback'])->toBe($customAuth);
        });

    });

    describe('Column Integration', function () {

        it('can add columns to meta fields', function () {
            $meta = Meta::for(objectSubtypes: 'product')
                ->addString(key: 'sku', description: 'Product SKU')
                ->addColumn(key: 'sku', label: 'SKU');

            expect($meta)->toBeInstanceOf(Meta::class);
        });

        it('throws exception for empty column key', function () {
            expect(fn() => Meta::for(objectSubtypes: 'product')
                ->addColumn(key: '', label: 'Label')
            )->toThrow(\InvalidArgumentException::class, 'Column key cannot be empty');
        });

        it('throws exception for empty column label', function () {
            expect(fn() => Meta::for(objectSubtypes: 'product')
                ->addColumn(key: 'test', label: '')
            )->toThrow(\InvalidArgumentException::class, 'Column label cannot be empty');
        });

        it('registers columns when register() is called', function () {
            global $wp_filter;

            Meta::for(objectSubtypes: 'product')
                ->addString(key: 'sku', description: 'Product SKU')
                ->addColumn(key: 'sku', label: 'SKU')
                ->register();

            expect($wp_filter)->toHaveKey('manage_product_posts_columns');
        });

        it('registers sortable columns', function () {
            global $wp_filter;

            Meta::for(objectSubtypes: 'product')
                ->addString(key: 'sku', description: 'Product SKU')
                ->addColumn(key: 'sku', label: 'SKU', sortable: true)
                ->register();

            expect($wp_filter)->toHaveKey('manage_product_posts_columns')
                ->and($wp_filter)->toHaveKey('manage_edit-product_sortable_columns');
        });

        it('registers multiple columns', function () {
            global $wp_filter;

            Meta::for(objectSubtypes: 'product')
                ->addString(key: 'sku', description: 'Product SKU')
                ->addColumn(key: 'sku', label: 'SKU')
                ->addNumber(key: 'price', description: 'Product price')
                ->addColumn(key: 'price', label: 'Price', sortable: true)
                ->register();

            expect($wp_filter)->toHaveKey('manage_product_posts_columns')
                ->and($wp_filter)->toHaveKey('manage_edit-product_sortable_columns');
        });

        it('registers columns for multiple post types', function () {
            global $wp_filter;

            Meta::for(objectSubtypes: ['product', 'event'])
                ->addString(key: 'sku', description: 'SKU')
                ->addColumn(key: 'sku', label: 'SKU')
                ->register();

            expect($wp_filter)->toHaveKey('manage_product_posts_columns')
                ->and($wp_filter)->toHaveKey('manage_event_posts_columns');
        });

        it('handles custom render callbacks', function () {
            global $wp_filter;

            $renderCallback = fn(int $postId, string $key): string => 'Custom: ' . get_post_meta($postId, $key, true);

            Meta::for(objectSubtypes: 'product')
                ->addString(key: 'sku', description: 'Product SKU')
                ->addColumn(key: 'sku', label: 'SKU', render: $renderCallback)
                ->register();

            expect($wp_filter)->toHaveKey('manage_product_posts_columns');
        });

        it('does not register columns if none were added', function () {
            global $wp_filter, $wp_registered_post_meta;

            Meta::for(objectSubtypes: 'product')
                ->addString(key: 'sku', description: 'Product SKU')
                ->register();

            // Meta should be registered
            expect($wp_registered_post_meta)->toHaveKey('product');
            
            // But no columns should be registered
            expect($wp_filter)->not->toHaveKey('manage_product_posts_columns');
        });

        it('does not register columns if no post types specified', function () {
            global $wp_filter;

            Meta::for()
                ->addString(key: 'sku', description: 'Product SKU')
                ->addColumn(key: 'sku', label: 'SKU')
                ->register();

            // No columns should be registered without post types
            expect($wp_filter)->not->toHaveKey('manage_posts_columns');
        });

    });

    describe('Quick Edit Integration', function () {

        it('can enable quick edit for fields', function () {
            $meta = Meta::for(objectSubtypes: 'product')
                ->addString(key: 'sku', description: 'Product SKU')
                ->showInQuickEdit(keys: 'sku');

            expect($meta)->toBeInstanceOf(Meta::class);
        });

        it('can enable quick edit for multiple fields', function () {
            $meta = Meta::for(objectSubtypes: 'product')
                ->addString(key: 'sku', description: 'Product SKU')
                ->addNumber(key: 'price', description: 'Product price')
                ->showInQuickEdit(keys: ['sku', 'price']);

            expect($meta)->toBeInstanceOf(Meta::class);
        });

        it('throws exception when enabling quick edit for non-existent field', function () {
            expect(fn() => Meta::for(objectSubtypes: 'product')
                ->showInQuickEdit(keys: 'non_existent')
            )->toThrow(\InvalidArgumentException::class, "Field 'non_existent' must be added before enabling quick edit");
        });

        it('registers quick edit hooks when register() is called', function () {
            global $wp_filter;

            Meta::for(objectSubtypes: 'product')
                ->addString(key: 'sku', description: 'Product SKU')
                ->showInQuickEdit(keys: 'sku')
                ->register();

            expect($wp_filter)->toHaveKey('quick_edit_custom_box');
        });

        it('does not register quick edit if no fields enabled', function () {
            global $wp_filter;

            Meta::for(objectSubtypes: 'product')
                ->addString(key: 'sku', description: 'Product SKU')
                ->register();

            // Quick edit should not be registered
            expect($wp_filter)->not->toHaveKey('quick_edit_custom_box');
        });

    });

    describe('Editor Meta Box Integration', function () {

        it('throws exception when title is empty', function () {
            expect(fn() => Meta::for(objectSubtypes: 'product')
                ->addString(key: 'sku', description: 'Product SKU')
                ->showInEditor(keys: 'sku', title: '')
            )->toThrow(\InvalidArgumentException::class, 'Meta box title is required');
        });

        it('throws exception for non-existent field', function () {
            expect(fn() => Meta::for(objectSubtypes: 'product')
                ->showInEditor(keys: 'non_existent', title: 'Test')
            )->toThrow(\InvalidArgumentException::class, "Field 'non_existent' must be added before showing in editor");
        });

        it('can add single field to editor', function () {
            $meta = Meta::for(objectSubtypes: 'product')
                ->addString(key: 'sku', description: 'Product SKU')
                ->showInEditor(keys: 'sku', title: 'Product Info');

            expect($meta)->toBeInstanceOf(Meta::class);
        });

        it('can add multiple fields to same meta box', function () {
            $meta = Meta::for(objectSubtypes: 'product')
                ->addString(key: 'sku', description: 'Product SKU')
                ->addNumber(key: 'price', description: 'Product price')
                ->showInEditor(keys: ['sku', 'price'], title: 'Product Details');

            expect($meta)->toBeInstanceOf(Meta::class);
        });

        it('can create multiple meta boxes', function () {
            $meta = Meta::for(objectSubtypes: 'product')
                ->addString(key: 'sku', description: 'Product SKU')
                ->showInEditor(keys: 'sku', title: 'Basic Info')
                ->addNumber(key: 'price', description: 'Product price')
                ->showInEditor(keys: 'price', title: 'Pricing');

            expect($meta)->toBeInstanceOf(Meta::class);
        });

        it('silently skips array/object types', function () {
            $meta = Meta::for(objectSubtypes: 'product')
                ->addArray(key: 'tags', description: 'Product tags')
                ->showInEditor(keys: 'tags', title: 'Tags');

            // Should not throw, just skip silently
            expect($meta)->toBeInstanceOf(Meta::class);
        });

        it('auto-generates meta box ID if not provided', function () {
            global $wp_filter;

            Meta::for(objectSubtypes: 'product')
                ->addString(key: 'sku', description: 'Product SKU')
                ->showInEditor(keys: 'sku', title: 'Product Info')
                ->register();

            expect($wp_filter)->toHaveKey('enqueue_block_editor_assets');
        });

        it('uses provided meta box ID', function () {
            $meta = Meta::for(objectSubtypes: 'product')
                ->addString(key: 'sku', description: 'Product SKU')
                ->showInEditor(
                    keys: 'sku',
                    title: 'Product Info',
                    metaBoxId: 'custom-id'
                );

            expect($meta)->toBeInstanceOf(Meta::class);
        });

        it('registers block editor hooks when register() is called', function () {
            global $wp_filter;

            Meta::for(objectSubtypes: 'product')
                ->addString(key: 'sku', description: 'Product SKU')
                ->showInEditor(keys: 'sku', title: 'Product Info')
                ->register();

            expect($wp_filter)->toHaveKey('enqueue_block_editor_assets');
        });

        it('does not register meta boxes if none configured', function () {
            global $wp_filter;

            Meta::for(objectSubtypes: 'product')
                ->addString(key: 'sku', description: 'Product SKU')
                ->register();

            // No meta boxes registered
            expect($wp_filter)->not->toHaveKey('enqueue_block_editor_assets');
        });

        it('enqueues scripts with correct dependencies', function () {
            global $wp_filter, $wp_scripts, $typenow;

            // Set the current post type
            $typenow = 'product';

            Meta::for(objectSubtypes: 'product')
                ->addString(key: 'sku', description: 'Product SKU')
                ->showInEditor(keys: 'sku', title: 'Product Info')
                ->register();

            // Manually trigger the enqueue_block_editor_assets action
            if (isset($wp_filter['enqueue_block_editor_assets'])) {
                foreach ($wp_filter['enqueue_block_editor_assets'] as $priority => $callbacks) {
                    foreach ($callbacks as $callback) {
                        call_user_func($callback);
                    }
                }
            }

            // Check that script was registered
            expect($wp_scripts)->toHaveKey('meta-boxes-product')
                ->and($wp_scripts['meta-boxes-product']['enqueued'])->toBeTrue()
                ->and($wp_scripts['meta-boxes-product']['deps'])->toContain('wp-plugins')
                ->and($wp_scripts['meta-boxes-product']['deps'])->toContain('wp-edit-post');
        });

        it('generates JavaScript with correct structure', function () {
            global $wp_scripts, $typenow;

            $typenow = 'product';

            Meta::for(objectSubtypes: 'product')
                ->addString(key: 'sku', description: 'Product SKU')
                ->addNumber(key: 'price', description: 'Product price')
                ->showInEditor(keys: ['sku', 'price'], title: 'Product Details')
                ->register();

            // Trigger the action
            do_action('enqueue_block_editor_assets');

            // Check that inline script was added
            $inlineScript = $wp_scripts['meta-boxes-product']['inline']['after'][0] ?? '';

            expect($inlineScript)->toContain('registerPlugin')
                ->and($inlineScript)->toContain('PluginDocumentSettingPanel')
                ->and($inlineScript)->toContain('Product Details')
                ->and($inlineScript)->toContain('TextControl')
                ->and($inlineScript)->toContain('NumberControl');
        });

    });

    describe('Real-world Usage', function () {

        it('handles complete product meta setup', function () {
            global $wp_registered_post_meta;

            Meta::for(objectSubtypes: 'product')
                ->addString(key: 'sku', description: 'Product SKU')
                ->addNumber(key: 'price', description: 'Product price')
                ->addInteger(key: 'stock', description: 'Stock quantity')
                ->addBoolean(key: 'featured', description: 'Is featured')
                ->addArray(key: 'tags', description: 'Product tags')
                ->addDate(key: 'release_date', description: 'Release date')
                ->needsCapability(capability: 'edit_posts')
                ->register();

            expect($wp_registered_post_meta)->toHaveKey('product')
                ->and(count($wp_registered_post_meta['product']))->toBe(6);
        });

        it('handles complete meta + columns setup', function () {
            global $wp_registered_post_meta, $wp_filter;

            Meta::for(objectSubtypes: 'product')
                ->addString(key: 'sku', description: 'Product SKU')
                ->addColumn(key: 'sku', label: 'SKU', sortable: true)
                ->addNumber(key: 'price', description: 'Product price')
                ->addColumn(key: 'price', label: 'Price', sortable: true)
                ->addBoolean(key: 'featured', description: 'Is featured')
                ->addColumn(key: 'featured', label: 'Featured')
                ->needsCapability(capability: 'edit_posts')
                ->register();

            // Meta fields registered
            expect($wp_registered_post_meta)->toHaveKey('product')
                ->and(count($wp_registered_post_meta['product']))->toBe(3);

            // Columns registered
            expect($wp_filter)->toHaveKey('manage_product_posts_columns')
                ->and($wp_filter)->toHaveKey('manage_edit-product_sortable_columns');
        });

    });

});
