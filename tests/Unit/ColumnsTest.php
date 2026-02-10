<?php

use RalfHortt\Meta\Columns;

beforeEach(function () {
    clearWordPressHooks();
});

describe('Columns', function () {

    describe('Type-specific Helpers', function () {

        describe('addCurrency()', function () {

            it('formats currency with default settings', function () {
                $columns = Columns::for(postTypes: 'product')
                    ->addCurrency(key: 'price', label: 'Price');

                $columns->register();
                set_test_post_meta(123, 'price', '1234.567');

                ob_start();
                $columns->renderColumn('price', 123);
                $output = ob_get_clean();

                expect($output)->toBe('$1,234.57');
            });

            it('formats currency with custom decimals', function () {
                $columns = Columns::for(postTypes: 'product')
                    ->addCurrency(key: 'price', label: 'Price', decimals: 0);

                $columns->register();
                set_test_post_meta(123, 'price', '1234.567');

                ob_start();
                $columns->renderColumn('price', 123);
                $output = ob_get_clean();

                expect($output)->toBe('$1,235');
            });

            it('formats currency with custom symbol', function () {
                $columns = Columns::for(postTypes: 'product')
                    ->addCurrency(key: 'price', label: 'Price', currencySymbol: '€');

                $columns->register();
                set_test_post_meta(123, 'price', '99.99');

                ob_start();
                $columns->renderColumn('price', 123);
                $output = ob_get_clean();

                expect($output)->toBe('€99.99');
            });

            it('formats currency with symbol after', function () {
                $columns = Columns::for(postTypes: 'product')
                    ->addCurrency(key: 'price', label: 'Price', currencySymbol: '€', symbolPosition: 'after');

                $columns->register();
                set_test_post_meta(123, 'price', '99.99');

                ob_start();
                $columns->renderColumn('price', 123);
                $output = ob_get_clean();

                expect($output)->toBe('99.99€');
            });

            it('returns empty string for empty currency value', function () {
                $columns = Columns::for(postTypes: 'product')
                    ->addCurrency(key: 'price', label: 'Price');

                $columns->register();

                ob_start();
                $columns->renderColumn('price', 123);
                $output = ob_get_clean();

                expect($output)->toBe('');
            });

            it('returns self for chaining', function () {
                $columns = Columns::for(postTypes: 'product');
                $result = $columns->addCurrency(key: 'price', label: 'Price');

                expect($result)->toBe($columns);
            });

        });

        describe('addDate()', function () {

            it('formats date with default format', function () {
                $columns = Columns::for(postTypes: 'event')
                    ->addDate(key: 'event_date', label: 'Event Date');

                $columns->register();
                set_test_post_meta(123, 'event_date', '2024-03-15');

                ob_start();
                $columns->renderColumn('event_date', 123);
                $output = ob_get_clean();

                expect($output)->toBe('2024-03-15');
            });

            it('formats date with custom format', function () {
                $columns = Columns::for(postTypes: 'event')
                    ->addDate(key: 'event_date', label: 'Event Date', format: 'F j, Y');

                $columns->register();
                set_test_post_meta(123, 'event_date', '2024-03-15');

                ob_start();
                $columns->renderColumn('event_date', 123);
                $output = ob_get_clean();

                expect($output)->toBe('March 15, 2024');
            });

            it('handles timestamp input', function () {
                $columns = Columns::for(postTypes: 'event')
                    ->addDate(key: 'event_date', label: 'Event Date', format: 'Y-m-d', inputFormat: 'timestamp');

                $columns->register();
                set_test_post_meta(123, 'event_date', '1710460800'); // 2024-03-15 00:00:00 UTC

                ob_start();
                $columns->renderColumn('event_date', 123);
                $output = ob_get_clean();

                expect($output)->toBe('2024-03-15');
            });

            it('handles custom input format', function () {
                $columns = Columns::for(postTypes: 'event')
                    ->addDate(key: 'event_date', label: 'Event Date', format: 'Y-m-d', inputFormat: 'd/m/Y');

                $columns->register();
                set_test_post_meta(123, 'event_date', '15/03/2024');

                ob_start();
                $columns->renderColumn('event_date', 123);
                $output = ob_get_clean();

                expect($output)->toBe('2024-03-15');
            });

            it('returns empty string for empty date', function () {
                $columns = Columns::for(postTypes: 'event')
                    ->addDate(key: 'event_date', label: 'Event Date');

                $columns->register();

                ob_start();
                $columns->renderColumn('event_date', 123);
                $output = ob_get_clean();

                expect($output)->toBe('');
            });

            it('returns empty string for invalid date', function () {
                $columns = Columns::for(postTypes: 'event')
                    ->addDate(key: 'event_date', label: 'Event Date');

                $columns->register();
                set_test_post_meta(123, 'event_date', 'invalid-date');

                ob_start();
                $columns->renderColumn('event_date', 123);
                $output = ob_get_clean();

                expect($output)->toBe('');
            });

            it('returns self for chaining', function () {
                $columns = Columns::for(postTypes: 'event');
                $result = $columns->addDate(key: 'event_date', label: 'Event Date');

                expect($result)->toBe($columns);
            });

        });

        describe('addBoolean()', function () {

            it('displays default true label', function () {
                $columns = Columns::for(postTypes: 'product')
                    ->addBoolean(key: 'featured', label: 'Featured');

                $columns->register();
                set_test_post_meta(123, 'featured', '1');

                ob_start();
                $columns->renderColumn('featured', 123);
                $output = ob_get_clean();

                expect($output)->toBe('Yes');
            });

            it('displays default false label', function () {
                $columns = Columns::for(postTypes: 'product')
                    ->addBoolean(key: 'featured', label: 'Featured');

                $columns->register();
                set_test_post_meta(123, 'featured', '0');

                ob_start();
                $columns->renderColumn('featured', 123);
                $output = ob_get_clean();

                expect($output)->toBe('No');
            });

            it('displays custom labels', function () {
                $columns = Columns::for(postTypes: 'product')
                    ->addBoolean(key: 'featured', label: 'Featured', trueLabel: 'Active', falseLabel: 'Inactive');

                $columns->register();
                set_test_post_meta(123, 'featured', 'yes');

                ob_start();
                $columns->renderColumn('featured', 123);
                $output = ob_get_clean();

                expect($output)->toBe('Active');
            });

            it('handles various truthy values', function () {
                $columns = Columns::for(postTypes: 'product')
                    ->addBoolean(key: 'featured', label: 'Featured');

                $columns->register();

                // Test true
                set_test_post_meta(1, 'featured', true);
                ob_start();
                $columns->renderColumn('featured', 1);
                expect(ob_get_clean())->toBe('Yes');

                // Test 1
                set_test_post_meta(2, 'featured', 1);
                ob_start();
                $columns->renderColumn('featured', 2);
                expect(ob_get_clean())->toBe('Yes');

                // Test '1'
                set_test_post_meta(3, 'featured', '1');
                ob_start();
                $columns->renderColumn('featured', 3);
                expect(ob_get_clean())->toBe('Yes');

                // Test 'yes'
                set_test_post_meta(4, 'featured', 'yes');
                ob_start();
                $columns->renderColumn('featured', 4);
                expect(ob_get_clean())->toBe('Yes');

                // Test 'on'
                set_test_post_meta(5, 'featured', 'on');
                ob_start();
                $columns->renderColumn('featured', 5);
                expect(ob_get_clean())->toBe('Yes');
            });

            it('handles various falsy values', function () {
                $columns = Columns::for(postTypes: 'product')
                    ->addBoolean(key: 'featured', label: 'Featured');

                $columns->register();

                // Test 0
                set_test_post_meta(1, 'featured', 0);
                ob_start();
                $columns->renderColumn('featured', 1);
                expect(ob_get_clean())->toBe('No');

                // Test '0'
                set_test_post_meta(2, 'featured', '0');
                ob_start();
                $columns->renderColumn('featured', 2);
                expect(ob_get_clean())->toBe('No');

                // Test empty string
                set_test_post_meta(3, 'featured', '');
                ob_start();
                $columns->renderColumn('featured', 3);
                expect(ob_get_clean())->toBe('No');

                // Test 'no'
                set_test_post_meta(4, 'featured', 'no');
                ob_start();
                $columns->renderColumn('featured', 4);
                expect(ob_get_clean())->toBe('No');
            });

            it('returns self for chaining', function () {
                $columns = Columns::for(postTypes: 'product');
                $result = $columns->addBoolean(key: 'featured', label: 'Featured');

                expect($result)->toBe($columns);
            });

        });

        describe('addImage()', function () {

            it('displays image from URL', function () {
                $columns = Columns::for(postTypes: 'product')
                    ->addImage(key: 'thumbnail', label: 'Thumbnail');

                $columns->register();
                set_test_post_meta(123, 'thumbnail', 'https://example.com/image.jpg');

                ob_start();
                $columns->renderColumn('thumbnail', 123);
                $output = ob_get_clean();

                expect($output)->toContain('<img')
                    ->and($output)->toContain('src="https://example.com/image.jpg"')
                    ->and($output)->toContain('width="50"')
                    ->and($output)->toContain('height="50"');
            });

            it('displays image with custom dimensions', function () {
                $columns = Columns::for(postTypes: 'product')
                    ->addImage(key: 'thumbnail', label: 'Thumbnail', width: 100, height: 75);

                $columns->register();
                set_test_post_meta(123, 'thumbnail', 'https://example.com/image.jpg');

                ob_start();
                $columns->renderColumn('thumbnail', 123);
                $output = ob_get_clean();

                expect($output)->toContain('width="100"')
                    ->and($output)->toContain('height="75"');
            });

            it('displays image from attachment ID', function () {
                $columns = Columns::for(postTypes: 'product')
                    ->addImage(key: 'thumbnail_id', label: 'Thumbnail', isAttachmentId: true);

                $columns->register();
                set_test_post_meta(123, 'thumbnail_id', '456');
                set_test_attachment(456, 'https://example.com/thumb.jpg', 'thumbnail');

                ob_start();
                $columns->renderColumn('thumbnail_id', 123);
                $output = ob_get_clean();

                expect($output)->toContain('src="https://example.com/thumb.jpg"');
            });

            it('returns empty string for missing image URL', function () {
                $columns = Columns::for(postTypes: 'product')
                    ->addImage(key: 'thumbnail', label: 'Thumbnail');

                $columns->register();

                ob_start();
                $columns->renderColumn('thumbnail', 123);
                $output = ob_get_clean();

                expect($output)->toBe('');
            });

            it('returns empty string for invalid attachment ID', function () {
                $columns = Columns::for(postTypes: 'product')
                    ->addImage(key: 'thumbnail_id', label: 'Thumbnail', isAttachmentId: true);

                $columns->register();
                set_test_post_meta(123, 'thumbnail_id', '999');

                ob_start();
                $columns->renderColumn('thumbnail_id', 123);
                $output = ob_get_clean();

                expect($output)->toBe('');
            });

            it('returns self for chaining', function () {
                $columns = Columns::for(postTypes: 'product');
                $result = $columns->addImage(key: 'thumbnail', label: 'Thumbnail');

                expect($result)->toBe($columns);
            });

        });

        describe('addList()', function () {

            it('displays array as comma-separated list', function () {
                $columns = Columns::for(postTypes: 'product')
                    ->addList(key: 'tags', label: 'Tags');

                $columns->register();
                set_test_post_meta(123, 'tags', ['red', 'blue', 'green']);

                ob_start();
                $columns->renderColumn('tags', 123);
                $output = ob_get_clean();

                expect($output)->toBe('red, blue, green');
            });

            it('displays array with custom separator', function () {
                $columns = Columns::for(postTypes: 'product')
                    ->addList(key: 'tags', label: 'Tags', separator: ' | ');

                $columns->register();
                set_test_post_meta(123, 'tags', ['red', 'blue', 'green']);

                ob_start();
                $columns->renderColumn('tags', 123);
                $output = ob_get_clean();

                expect($output)->toBe('red | blue | green');
            });

            it('limits number of displayed items', function () {
                $columns = Columns::for(postTypes: 'product')
                    ->addList(key: 'tags', label: 'Tags', limit: 2);

                $columns->register();
                set_test_post_meta(123, 'tags', ['red', 'blue', 'green', 'yellow']);

                ob_start();
                $columns->renderColumn('tags', 123);
                $output = ob_get_clean();

                expect($output)->toBe('red, blue, ...');
            });

            it('handles non-array values gracefully', function () {
                $columns = Columns::for(postTypes: 'product')
                    ->addList(key: 'tags', label: 'Tags');

                $columns->register();
                set_test_post_meta(123, 'tags', 'single-value');

                ob_start();
                $columns->renderColumn('tags', 123);
                $output = ob_get_clean();

                expect($output)->toBe('single-value');
            });

            it('returns empty string for empty array', function () {
                $columns = Columns::for(postTypes: 'product')
                    ->addList(key: 'tags', label: 'Tags');

                $columns->register();
                set_test_post_meta(123, 'tags', []);

                ob_start();
                $columns->renderColumn('tags', 123);
                $output = ob_get_clean();

                expect($output)->toBe('');
            });

            it('returns empty string for missing value', function () {
                $columns = Columns::for(postTypes: 'product')
                    ->addList(key: 'tags', label: 'Tags');

                $columns->register();

                ob_start();
                $columns->renderColumn('tags', 123);
                $output = ob_get_clean();

                expect($output)->toBe('');
            });

            it('returns self for chaining', function () {
                $columns = Columns::for(postTypes: 'product');
                $result = $columns->addList(key: 'tags', label: 'Tags');

                expect($result)->toBe($columns);
            });

        });

        describe('Chaining Type Helpers', function () {

            it('can chain multiple type helpers together', function () {
                $columns = Columns::for(postTypes: 'product')
                    ->addCurrency(key: 'price', label: 'Price')
                    ->addDate(key: 'release_date', label: 'Release Date')
                    ->addBoolean(key: 'featured', label: 'Featured')
                    ->addImage(key: 'thumbnail', label: 'Image')
                    ->addList(key: 'tags', label: 'Tags')
                    ->sortable(keys: ['price', 'release_date']);

                expect($columns)->toBeInstanceOf(Columns::class);
                
                $columns->register();
            });

        });

    });


    describe('Static Factory', function () {

        it('can create instance with static factory for single post type', function () {
            $columns = Columns::for(postTypes: 'product');
            expect($columns)->toBeInstanceOf(Columns::class);
        });

        it('can create instance with static factory for multiple post types', function () {
            $columns = Columns::for(postTypes: ['product', 'event']);
            expect($columns)->toBeInstanceOf(Columns::class);
        });

        it('converts string to array internally', function () {
            $columns = Columns::for(postTypes: 'product');
            expect($columns)->toBeInstanceOf(Columns::class);
        });

    });

    describe('Fluent API', function () {

        it('returns self for chaining with add()', function () {
            $columns = Columns::for(postTypes: 'product');
            $result = $columns->add(key: 'price', label: 'Price');
            expect($result)->toBe($columns);
        });

        it('returns self for chaining with sortable()', function () {
            $columns = Columns::for(postTypes: 'product');
            $result = $columns->sortable(keys: 'price');
            expect($result)->toBe($columns);
        });

        it('returns self for chaining with priority()', function () {
            $columns = Columns::for(postTypes: 'product');
            $result = $columns->priority(value: 15);
            expect($result)->toBe($columns);
        });

        it('can chain all methods together', function () {
            $columns = Columns::for(postTypes: 'product')
                ->add(key: 'price', label: 'Price')
                ->add(key: 'sku', label: 'SKU')
                ->sortable(keys: ['price', 'sku'])
                ->priority(value: 15);

            expect($columns)->toBeInstanceOf(Columns::class);
        });

    });

    describe('Adding Columns', function () {

        it('adds single column', function () {
            $columns = Columns::for(postTypes: 'product')
                ->add(key: 'product_price', label: 'Price');

            $columns->register();

            $existingColumns = ['title' => 'Title'];
            $result = $columns->addColumns($existingColumns);

            expect($result)->toHaveKey('title')
                ->and($result)->toHaveKey('product_price')
                ->and($result['product_price'])->toBe('Price');
        });

        it('adds multiple columns', function () {
            $columns = Columns::for(postTypes: 'product')
                ->add(key: 'product_price', label: 'Price')
                ->add(key: 'product_sku', label: 'SKU')
                ->add(key: 'custom_field', label: 'Custom Field');

            $existingColumns = ['title' => 'Title'];
            $result = $columns->addColumns($existingColumns);

            expect($result)->toHaveKey('product_price')
                ->and($result)->toHaveKey('product_sku')
                ->and($result)->toHaveKey('custom_field');
        });

        it('throws exception for empty key', function () {
            expect(fn() => Columns::for(postTypes: 'product')
                ->add(key: '', label: 'Test')
            )->toThrow(\InvalidArgumentException::class, 'Column key cannot be empty');
        });

        it('throws exception for empty label', function () {
            expect(fn() => Columns::for(postTypes: 'product')
                ->add(key: 'test', label: '')
            )->toThrow(\InvalidArgumentException::class, 'Column label cannot be empty');
        });

    });

    describe('Sortable Columns', function () {

        it('makes single column sortable with string', function () {
            $columns = Columns::for(postTypes: 'product')
                ->add(key: 'price', label: 'Price')
                ->sortable(keys: 'price');

            $existingColumns = ['title' => 'title'];
            $result = $columns->addSortableColumns($existingColumns);

            expect($result)->toHaveKey('price')
                ->and($result['price'])->toBe('price');
        });

        it('makes multiple columns sortable with array', function () {
            $columns = Columns::for(postTypes: 'product')
                ->add(key: 'price', label: 'Price')
                ->add(key: 'sku', label: 'SKU')
                ->sortable(keys: ['price', 'sku']);

            $existingColumns = ['title' => 'title'];
            $result = $columns->addSortableColumns($existingColumns);

            expect($result)->toHaveKey('price')
                ->and($result)->toHaveKey('sku');
        });

        it('handles empty sortable columns gracefully', function () {
            $columns = Columns::for(postTypes: 'product')
                ->add(key: 'price', label: 'Price');

            $existingColumns = ['title' => 'title'];
            $result = $columns->addSortableColumns($existingColumns);

            expect($result)->toBe($existingColumns);
        });

    });

    describe('Custom Renderers', function () {

        it('uses custom renderer when provided', function () {
            $columns = Columns::for(postTypes: 'product')
                ->add(
                    key: 'product_price',
                    label: 'Price',
                    render: fn(int $postId): string => '$' . number_format((float)get_post_meta($postId, 'product_price', true), 2)
                );

            $columns->register();
            set_test_post_meta(123, 'product_price', '99.99');

            ob_start();
            $columns->renderColumn('product_price', 123);
            $output = ob_get_clean();

            expect($output)->toBe('$99.99');
        });

        it('uses custom renderer with complex logic', function () {
            $columns = Columns::for(postTypes: 'product')
                ->add(
                    key: 'stock',
                    label: 'Stock',
                    render: function(int $postId): string {
                        $stock = get_post_meta($postId, 'stock', true);
                        return $stock > 0 ? "In Stock ($stock)" : 'Out of Stock';
                    }
                );

            $columns->register();
            set_test_post_meta(456, 'stock', '10');

            ob_start();
            $columns->renderColumn('stock', 456);
            $output = ob_get_clean();

            expect($output)->toBe('In Stock (10)');
        });

        it('uses default renderer when no custom renderer provided', function () {
            $columns = Columns::for(postTypes: 'product')
                ->add(key: 'custom_field', label: 'Custom Field');

            $columns->register();
            set_test_post_meta(123, 'custom_field', 'Custom Value');

            ob_start();
            $columns->renderColumn('custom_field', 123);
            $output = ob_get_clean();

            expect($output)->toBe('Custom Value');
        });

        it('renders nothing for undefined column', function () {
            $columns = Columns::for(postTypes: 'product')
                ->add(key: 'price', label: 'Price');

            $columns->register();

            ob_start();
            $columns->renderColumn('undefined_column', 123);
            $output = ob_get_clean();

            expect($output)->toBe('');
        });

        it('renders empty string when post meta does not exist', function () {
            $columns = Columns::for(postTypes: 'product')
                ->add(key: 'custom_field', label: 'Custom Field');

            $columns->register();

            ob_start();
            $columns->renderColumn('custom_field', 999);
            $output = ob_get_clean();

            expect($output)->toBe('');
        });

    });

    describe('WordPress Integration', function () {

        it('registers filters and actions for single post type', function () {
            global $wp_filter;

            Columns::for(postTypes: 'product')
                ->add(key: 'price', label: 'Price')
                ->register();

            expect($wp_filter)->toHaveKey('manage_product_posts_columns');
            expect($wp_filter)->toHaveKey('manage_product_posts_custom_column');
            expect($wp_filter)->toHaveKey('manage_edit-product_sortable_columns');
        });

        it('registers filters for multiple post types', function () {
            global $wp_filter;

            Columns::for(postTypes: ['product', 'event'])
                ->add(key: 'price', label: 'Price')
                ->register();

            expect($wp_filter)->toHaveKey('manage_product_posts_columns');
            expect($wp_filter)->toHaveKey('manage_event_posts_columns');
        });

        it('handles post type transformation for post', function () {
            global $wp_filter;

            Columns::for(postTypes: 'post')
                ->add(key: 'custom', label: 'Custom')
                ->register();

            expect($wp_filter)->toHaveKey('manage_posts_posts_columns');
            expect($wp_filter)->toHaveKey('manage_posts_posts_custom_column');
        });

        it('handles post type transformation for page', function () {
            global $wp_filter;

            Columns::for(postTypes: 'page')
                ->add(key: 'custom', label: 'Custom')
                ->register();

            expect($wp_filter)->toHaveKey('manage_pages_posts_columns');
            expect($wp_filter)->toHaveKey('manage_pages_posts_custom_column');
        });

        it('respects custom priority', function () {
            global $wp_filter;

            Columns::for(postTypes: 'post')
                ->add(key: 'test_field', label: 'Test')
                ->priority(value: 20)
                ->register();

            expect($wp_filter['manage_posts_posts_columns'])->toHaveKey(20);
        });

    });

    describe('Named Arguments', function () {

        it('accepts named arguments in any order for add()', function () {
            $columns = Columns::for(postTypes: 'product')
                ->add(label: 'Price', key: 'price'); // Reversed order

            expect($columns)->toBeInstanceOf(Columns::class);
        });

        it('accepts named arguments for sortable()', function () {
            $columns = Columns::for(postTypes: 'product')
                ->sortable(keys: ['price', 'sku']);

            expect($columns)->toBeInstanceOf(Columns::class);
        });

        it('accepts named arguments for priority()', function () {
            $columns = Columns::for(postTypes: 'product')
                ->priority(value: 20);

            expect($columns)->toBeInstanceOf(Columns::class);
        });

    });

    describe('Real-world Usage', function () {

        it('handles complete product columns setup', function () {
            global $wp_filter;

            Columns::for(postTypes: 'product')
                ->add(
                    key: 'product_price',
                    label: 'Price',
                    render: fn(int $postId): string => '$' . number_format((float)get_post_meta($postId, 'product_price', true), 2)
                )
                ->add(key: 'product_sku', label: 'SKU')
                ->add(
                    key: 'product_stock',
                    label: 'Stock',
                    render: function(int $postId): string {
                        $stock = (int) get_post_meta($postId, 'product_stock', true);
                        return $stock > 0 ? sprintf('✓ %d', $stock) : '✗ Out';
                    }
                )
                ->sortable(keys: ['product_price', 'product_sku'])
                ->priority(value: 15)
                ->register();

            expect($wp_filter)->toHaveKey('manage_product_posts_columns');
            expect($wp_filter['manage_product_posts_columns'])->toHaveKey(15);
        });

    });

});
