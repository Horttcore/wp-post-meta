<?php
namespace RalfHortt\Meta;

class Meta
{
    protected string $objectType = 'post';
    protected array $objectSubtypes = [];
    protected array $fields = [];
    protected array $columns = [];
    protected array $quickEditFields = [];
    protected array $metaBoxes = [];

    /**
     * Create a new RestMeta instance for specified object type and subtypes
     *
     * @param string $objectType Object type (default: 'post')
     * @param string|array $objectSubtypes Post type(s) to register fields for
     * @return self
     */
    public static function for(string $objectType = 'post', string|array $objectSubtypes = []): self
    {
        $instance = new self();
        $instance->objectType = $objectType;
        $instance->objectSubtypes = (array) $objectSubtypes;
        return $instance;
    }

    /**
     * Add a meta field to the REST API
     *
     * @param string $key Meta key to expose
     * @param string $type Data type: 'string', 'boolean', 'integer', 'number', 'array', 'object'
     * @param string|null $description Description for API documentation
     * @param callable|null $getCallback Custom callback for retrieving value: fn(array $object, string $fieldName, WP_REST_Request $request): mixed
     * @param callable|null $updateCallback Custom callback for updating value: fn(mixed $value, WP_Post $object, string $fieldName): bool|WP_Error
     * @param callable|null $authCallback Custom authorization check: fn(bool $allowed, string $context, int $objectId, string $fieldName): bool
     * @return self
     * @throws \InvalidArgumentException If key or type is invalid
     */
    public function add(
        string $key,
        string $type = 'string',
        ?string $description = null,
        ?callable $getCallback = null,
        ?callable $updateCallback = null,
        ?callable $authCallback = null
    ): self {
        if (empty($key)) {
            throw new \InvalidArgumentException('Meta key cannot be empty');
        }

        $validTypes = ['string', 'boolean', 'integer', 'number', 'array', 'object'];
        if (!in_array($type, $validTypes, true)) {
            throw new \InvalidArgumentException(
                'Type must be one of: ' . implode(', ', $validTypes)
            );
        }

        $this->fields[$key] = [
            'type' => $type,
            'description' => $description ?? '',
            'single' => true,
            'multiline' => false,
            'show_in_rest' => [
                'schema' => [
                    'type' => $type,
                    'description' => $description ?? '',
                ],
            ],
            'get_callback' => $getCallback,
            'update_callback' => $updateCallback,
            'auth_callback' => $authCallback,
        ];

        return $this;
    }

    /**
     * Add a string field to the REST API
     *
     * @param string $key Meta key to expose
     * @param string|null $description Description for API documentation
     * @param callable|null $getCallback Custom get callback
     * @param callable|null $updateCallback Custom update callback
     * @param callable|null $authCallback Custom authorization callback
     * @return self
     */
    public function addString(
        string $key,
        ?string $description = null,
        ?callable $getCallback = null,
        ?callable $updateCallback = null,
        ?callable $authCallback = null
    ): self {
        return $this->add(
            key: $key,
            type: 'string',
            description: $description,
            getCallback: $getCallback,
            updateCallback: $updateCallback,
            authCallback: $authCallback
        );
    }

    /**
     * Add a multiline text field to the REST API
     *
     * @param string $key Meta key to expose
     * @param string|null $description Description for API documentation
     * @param callable|null $getCallback Custom get callback
     * @param callable|null $updateCallback Custom update callback
     * @param callable|null $authCallback Custom authorization callback
     * @return self
     */
    public function addText(
        string $key,
        ?string $description = null,
        ?callable $getCallback = null,
        ?callable $updateCallback = null,
        ?callable $authCallback = null
    ): self {
        $this->add(
            key: $key,
            type: 'string',
            description: $description,
            getCallback: $getCallback,
            updateCallback: $updateCallback,
            authCallback: $authCallback
        );

        $this->fields[$key]['multiline'] = true;

        return $this;
    }

    /**
     * Add a boolean field to the REST API with automatic conversion
     *
     * @param string $key Meta key to expose
     * @param string|null $description Description for API documentation
     * @param callable|null $authCallback Custom authorization callback
     * @return self
     */
    public function addBoolean(
        string $key,
        ?string $description = null,
        ?callable $authCallback = null
    ): self {
        return $this->add(
            key: $key,
            type: 'boolean',
            description: $description,
            getCallback: function ($object) use ($key): bool {
                $value = get_post_meta($object['id'], $key, true);
                return in_array($value, [true, 1, '1', 'yes', 'on', 'true'], true);
            },
            updateCallback: function ($value, $object) use ($key): bool {
                return update_post_meta($object->ID, $key, (bool) $value ? '1' : '0');
            },
            authCallback: $authCallback
        );
    }

    /**
     * Add an integer field to the REST API with automatic conversion
     *
     * @param string $key Meta key to expose
     * @param string|null $description Description for API documentation
     * @param callable|null $authCallback Custom authorization callback
     * @return self
     */
    public function addInteger(
        string $key,
        ?string $description = null,
        ?callable $authCallback = null
    ): self {
        return $this->add(
            key: $key,
            type: 'integer',
            description: $description,
            getCallback: function ($object) use ($key): int {
                return (int) get_post_meta($object['id'], $key, true);
            },
            updateCallback: function ($value, $object) use ($key): bool {
                return update_post_meta($object->ID, $key, (int) $value);
            },
            authCallback: $authCallback
        );
    }

    /**
     * Add a number (float) field to the REST API with automatic conversion
     *
     * @param string $key Meta key to expose
     * @param string|null $description Description for API documentation
     * @param callable|null $authCallback Custom authorization callback
     * @return self
     */
    public function addNumber(
        string $key,
        ?string $description = null,
        ?callable $authCallback = null
    ): self {
        return $this->add(
            key: $key,
            type: 'number',
            description: $description,
            getCallback: function ($object) use ($key): float {
                return (float) get_post_meta($object['id'], $key, true);
            },
            updateCallback: function ($value, $object) use ($key): bool {
                return update_post_meta($object->ID, $key, (float) $value);
            },
            authCallback: $authCallback
        );
    }

    /**
     * Add an array field to the REST API
     *
     * @param string $key Meta key to expose
     * @param string|null $description Description for API documentation
     * @param callable|null $authCallback Custom authorization callback
     * @return self
     */
    public function addArray(
        string $key,
        ?string $description = null,
        ?callable $authCallback = null
    ): self {
        return $this->add(
            key: $key,
            type: 'array',
            description: $description,
            getCallback: function ($object) use ($key): array {
                $value = get_post_meta($object['id'], $key, true);
                return is_array($value) ? $value : [];
            },
            updateCallback: function ($value, $object) use ($key): bool {
                return update_post_meta($object->ID, $key, (array) $value);
            },
            authCallback: $authCallback
        );
    }

    /**
     * Add a date field to the REST API with ISO 8601 format
     *
     * @param string $key Meta key to expose
     * @param string|null $description Description for API documentation
     * @param string $inputFormat Expected format of stored date, or 'timestamp' for Unix timestamps (default: 'Y-m-d')
     * @param callable|null $authCallback Custom authorization callback
     * @return self
     */
    public function addDate(
        string $key,
        ?string $description = null,
        string $inputFormat = 'Y-m-d',
        ?callable $authCallback = null
    ): self {
        return $this->add(
            key: $key,
            type: 'string',
            description: $description,
            getCallback: function ($object) use ($key, $inputFormat): ?string {
                $value = get_post_meta($object['id'], $key, true);
                
                if (empty($value)) {
                    return null;
                }

                try {
                    if ($inputFormat === 'timestamp') {
                        $date = new \DateTime('@' . (int) $value);
                    } else {
                        $date = \DateTime::createFromFormat($inputFormat, $value);
                    }
                    
                    return $date ? $date->format('c') : null; // ISO 8601
                } catch (\Exception $e) {
                    return null;
                }
            },
            updateCallback: function ($value, $object) use ($key, $inputFormat): bool {
                if (empty($value)) {
                    return delete_post_meta($object->ID, $key);
                }

                try {
                    $date = new \DateTime($value);
                    
                    if ($inputFormat === 'timestamp') {
                        $stored = $date->getTimestamp();
                    } else {
                        $stored = $date->format($inputFormat);
                    }
                    
                    return update_post_meta($object->ID, $key, $stored);
                } catch (\Exception $e) {
                    return false;
                }
            },
            authCallback: $authCallback
        );
    }

    /**
     * Require specific capability for editing all fields
     *
     * @param string $capability WordPress capability (e.g., 'edit_posts', 'manage_options')
     * @return self
     */
    public function needsCapability(string $capability): self
    {
        $authCallback = function (bool $allowed, string $context) use ($capability): bool {
            if ($context === 'edit') {
                return current_user_can($capability);
            }
            return $allowed;
        };

        foreach ($this->fields as $key => $field) {
            if ($field['auth_callback'] === null) {
                $this->fields[$key]['auth_callback'] = $authCallback;
            }
        }

        return $this;
    }

    /**
     * Add a column to admin list tables for the registered post types
     *
     * @param string $key Meta key to display in column
     * @param string $label Column header label
     * @param callable|null $render Custom render callback: fn(int $postId, string $key): string
     * @param bool $sortable Whether the column should be sortable
     * @return self
     */
    public function addColumn(
        string $key,
        string $label,
        ?callable $render = null,
        bool $sortable = false
    ): self {
        if (empty($key)) {
            throw new \InvalidArgumentException('Column key cannot be empty');
        }

        if (empty($label)) {
            throw new \InvalidArgumentException('Column label cannot be empty');
        }

        $this->columns[] = [
            'key' => $key,
            'label' => $label,
            'render' => $render,
            'sortable' => $sortable,
        ];

        return $this;
    }

    /**
     * Enable quick edit for specific fields
     *
     * @param string|array $keys Meta key(s) to enable quick edit for
     * @return self
     */
    public function showInQuickEdit(string|array $keys): self
    {
        $keys = (array) $keys;
        
        foreach ($keys as $key) {
            if (!isset($this->fields[$key])) {
                throw new \InvalidArgumentException("Field '{$key}' must be added before enabling quick edit");
            }
            
            if (!in_array($key, $this->quickEditFields, true)) {
                $this->quickEditFields[] = $key;
            }
        }

        return $this;
    }

    /**
     * Add field(s) to a Gutenberg sidebar meta box
     *
     * @param string|array $keys Field key(s) to show in editor
     * @param string $title Meta box title (required)
     * @param string|null $metaBoxId Unique ID (auto-generated if null)
     * @param string $context 'side' or 'normal' (default: 'side')
     * @return self
     * @throws \InvalidArgumentException If title is empty or fields don't exist
     */
    public function showInEditor(
        string|array $keys,
        string $title,
        ?string $metaBoxId = null,
        string $context = 'side'
    ): self {
        // Validate title
        if (empty(trim($title))) {
            throw new \InvalidArgumentException('Meta box title is required');
        }

        // Normalize keys
        $keys = (array) $keys;

        // Filter to only simple types (silently skip arrays/objects)
        $validFields = [];
        foreach ($keys as $key) {
            if (!isset($this->fields[$key])) {
                throw new \InvalidArgumentException("Field '{$key}' must be added before showing in editor");
            }

            $type = $this->fields[$key]['type'];
            if (in_array($type, ['string', 'boolean', 'integer', 'number'], true)) {
                $validFields[] = $key;
            }
            // Array/object types are silently skipped
        }

        // If no valid fields after filtering, return early
        if (empty($validFields)) {
            return $this;
        }

        // Generate or validate meta box ID
        $metaBoxId = $metaBoxId ?: $this->generateMetaBoxId($validFields);

        // Store configuration
        $this->metaBoxes[$metaBoxId] = [
            'title' => $title,
            'context' => $context,
            'fields' => $validFields
        ];

        return $this;
    }

    /**
     * Generate a meta box ID from field names
     *
     * @param array $keys Field keys
     * @return string Generated meta box ID
     */
    protected function generateMetaBoxId(array $keys): string
    {
        // Create from field names: 'meta-box-sku-price-stock'
        // Limit to 3 keys to avoid overly long IDs
        $slug = implode('-', array_slice($keys, 0, 3));
        return 'meta-box-' . $this->sanitizeKey($slug);
    }

    /**
     * Sanitize a string for use as a key
     *
     * @param string $key Key to sanitize
     * @return string Sanitized key
     */
    protected function sanitizeKey(string $key): string
    {
        return strtolower(preg_replace('/[^a-zA-Z0-9_\-]/', '', $key));
    }

    /**
     * Register admin columns for the registered post types
     *
     * @return void
     */
    protected function registerColumns(): void
    {
        if (empty($this->columns) || empty($this->objectSubtypes)) {
            return;
        }

        $columnsInstance = Columns::for(postTypes: $this->objectSubtypes);

        foreach ($this->columns as $config) {
            $columnsInstance->add(
                key: $config['key'],
                label: $config['label'],
                render: $config['render']
            );

            if ($config['sortable']) {
                $columnsInstance->sortable(keys: $config['key']);
            }
        }

        $columnsInstance->register();
    }

    /**
     * Register quick edit fields for the registered post types
     *
     * @return void
     */
    protected function registerQuickEdit(): void
    {
        if (empty($this->quickEditFields) || empty($this->objectSubtypes)) {
            return;
        }

        foreach ($this->objectSubtypes as $postType) {
            // Add quick edit fields to the admin screen
            add_action("quick_edit_custom_box", function ($columnName, $postType) {
                if (!in_array($columnName, $this->quickEditFields, true)) {
                    return;
                }

                if (!isset($this->fields[$columnName])) {
                    return;
                }

                $field = $this->fields[$columnName];
                $fieldId = 'meta_' . $columnName;
                $label = $field['description'] ?: ucwords(str_replace(['_', '-'], ' ', $columnName));

                echo '<fieldset class="inline-edit-col-left"><div class="inline-edit-col">';
                echo '<label class="alignleft"><span class="title">' . esc_html($label) . '</span>';

                $this->renderQuickEditField($columnName, $field, $fieldId);

                echo '</label></div></fieldset>';
            }, 10, 2);

            // Save quick edit data
            add_action("save_post_{$postType}", function ($postId) {
                if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                    return;
                }

                foreach ($this->quickEditFields as $key) {
                    if (!isset($_POST[$key])) {
                        continue;
                    }

                    if (!isset($this->fields[$key])) {
                        continue;
                    }

                    $field = $this->fields[$key];
                    $value = $_POST[$key];

                    // Use update callback if provided
                    if ($field['update_callback'] !== null) {
                        $post = get_post($postId);
                        call_user_func($field['update_callback'], $value, $post, $key);
                    } else {
                        // Default type conversion
                        $value = $this->convertQuickEditValue($value, $field['type']);
                        update_post_meta($postId, $key, $value);
                    }
                }
            }, 10, 1);

            // Enqueue admin script to populate quick edit fields
            add_action('admin_footer', function () use ($postType) {
                global $pagenow, $typenow;
                
                if ($pagenow !== 'edit.php' || $typenow !== $postType) {
                    return;
                }
                ?>
                <script type="text/javascript">
                jQuery(function($) {
                    var $inline_editor = inlineEditPost.edit;
                    inlineEditPost.edit = function(id) {
                        $inline_editor.apply(this, arguments);
                        
                        var post_id = 0;
                        if (typeof(id) == 'object') {
                            post_id = parseInt(this.getId(id));
                        }
                        
                        if (post_id > 0) {
                            var $row = $('#post-' + post_id);
                            <?php foreach ($this->quickEditFields as $key): ?>
                            var <?php echo esc_js($key); ?> = $row.find('.<?php echo esc_js($key); ?>').text();
                            $(':input[name="<?php echo esc_js($key); ?>"]', '.inline-edit-row').val(<?php echo esc_js($key); ?>);
                            <?php endforeach; ?>
                        }
                    };
                });
                </script>
                <?php
            });
        }
    }

    /**
     * Render quick edit field based on type
     *
     * @param string $key Field key
     * @param array $field Field configuration
     * @param string $fieldId HTML field ID
     * @return void
     */
    protected function renderQuickEditField(string $key, array $field, string $fieldId): void
    {
        $type = $field['type'];

        switch ($type) {
            case 'boolean':
                echo '<input type="checkbox" name="' . esc_attr($key) . '" id="' . esc_attr($fieldId) . '" value="1" />';
                break;

            case 'integer':
            case 'number':
                $inputType = $type === 'integer' ? 'number' : 'text';
                $step = $type === 'integer' ? '1' : 'any';
                echo '<input type="' . esc_attr($inputType) . '" name="' . esc_attr($key) . '" id="' . esc_attr($fieldId) . '" step="' . esc_attr($step) . '" class="widefat" />';
                break;

            case 'string':
                if (!empty($field['multiline'])) {
                    echo '<textarea name="' . esc_attr($key) . '" id="' . esc_attr($fieldId) . '" class="widefat" rows="4"></textarea>';
                    break;
                }

                echo '<input type="text" name="' . esc_attr($key) . '" id="' . esc_attr($fieldId) . '" class="widefat" />';
                break;

            default:
                echo '<input type="text" name="' . esc_attr($key) . '" id="' . esc_attr($fieldId) . '" class="widefat" />';
                break;
        }
    }

    /**
     * Convert quick edit value based on field type
     *
     * @param mixed $value Input value
     * @param string $type Field type
     * @return mixed Converted value
     */
    protected function convertQuickEditValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => $value ? '1' : '0',
            'integer' => (int) $value,
            'number' => (float) $value,
            default => $value,
        };
    }

    /**
     * Register all meta fields with WordPress REST API
     *
     * @return void
     */
    public function register(): void
    {
        foreach ($this->fields as $key => $config) {
            $args = [
                'type' => $config['type'],
                'description' => $config['description'],
                'single' => $config['single'],
                'show_in_rest' => $config['show_in_rest'],
            ];

            // Add get_callback if provided
            if ($config['get_callback'] !== null) {
                $args['show_in_rest']['get_callback'] = $config['get_callback'];
            }

            // Add update_callback if provided
            if ($config['update_callback'] !== null) {
                $args['show_in_rest']['update_callback'] = $config['update_callback'];
            }

            // Add auth_callback if provided
            if ($config['auth_callback'] !== null) {
                $args['auth_callback'] = $config['auth_callback'];
            }

            // Register post meta
            register_post_meta(
                $this->objectSubtypes ? implode(',', $this->objectSubtypes) : '',
                $key,
                $args
            );
        }

        // Register columns if any were added
        $this->registerColumns();
        
        // Register quick edit if any fields were enabled
        $this->registerQuickEdit();
        
        // Register meta boxes if any were configured
        $this->registerMetaBoxes();
    }

    /**
     * Register Gutenberg meta boxes for the registered post types
     *
     * @return void
     */
    protected function registerMetaBoxes(): void
    {
        if (empty($this->metaBoxes) || empty($this->objectSubtypes)) {
            return;
        }

        foreach ($this->objectSubtypes as $postType) {
            add_action('enqueue_block_editor_assets', function() use ($postType) {
                global $typenow;

                // Only enqueue for the specific post type
                if ($typenow !== $postType) {
                    return;
                }

                $this->enqueueEditorAssets($postType);
            });
        }
    }

    /**
     * Enqueue editor assets for meta boxes
     *
     * @param string $postType Post type
     * @return void
     */
    protected function enqueueEditorAssets(string $postType): void
    {
        $handle = "meta-boxes-{$postType}";

        // Register empty script for dependencies
        wp_register_script(
            $handle,
            false, // No file
            ['wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-i18n'],
            false,
            true
        );

        // Generate and add inline script
        $script = $this->generateMetaBoxScript();
        wp_add_inline_script($handle, $script);

        wp_enqueue_script($handle);
    }

    /**
     * Generate JavaScript for meta boxes
     *
     * @return string JavaScript code
     */
    protected function generateMetaBoxScript(): string
    {
        $scripts = [];

        foreach ($this->metaBoxes as $metaBoxId => $config) {
            $scripts[] = $this->generateSingleMetaBox($metaBoxId, $config);
        }

        return implode("\n\n", $scripts);
    }

    /**
     * Generate JavaScript for a single meta box
     *
     * @param string $metaBoxId Meta box ID
     * @param array $config Meta box configuration
     * @return string JavaScript code
     */
    protected function generateSingleMetaBox(string $metaBoxId, array $config): string
    {
        $title = addslashes($config['title']);
        $componentName = $this->generateComponentName($metaBoxId);
        $panelName = $metaBoxId . '-panel';

        // Generate field controls (each wrapped in PanelRow for WordPress default spacing)
        $fieldControls = [];
        foreach ($config['fields'] as $fieldKey) {
            if (!isset($this->fields[$fieldKey])) {
                continue;
            }

            $field = $this->fields[$fieldKey];
            $control = $this->generateFieldControl($fieldKey, $field);
            $fieldControls[] = "el(PanelRow, null, {$control})";
        }

        $fieldsJs = implode(",\n            ", $fieldControls);

        // Determine the panel type based on context
        $panelComponent = $config['context'] === 'side' 
            ? 'PluginDocumentSettingPanel' 
            : 'PluginDocumentSettingPanel';

        return <<<JS
(function(wp) {
    const { registerPlugin } = wp.plugins;
    const { {$panelComponent} } = wp.editPost;
    const { useSelect, useDispatch } = wp.data;
    const { TextControl, TextareaControl, ToggleControl, PanelRow, __experimentalNumberControl: NumberControl } = wp.components;
    const { createElement: el } = wp.element;
    
    const {$componentName} = function() {
        const meta = useSelect(function(select) {
            return select('core/editor').getEditedPostAttribute('meta') || {};
        }, []);
        
        const { editPost } = useDispatch('core/editor');
        
        const updateMeta = function(key, value) {
            var newMeta = {};
            newMeta[key] = value;
            editPost({ meta: newMeta });
        };
        
        return el({$panelComponent}, {
            name: '{$panelName}',
            title: '{$title}',
            className: '{$metaBoxId}'
        },
            {$fieldsJs}
        );
    };
    
    registerPlugin('{$metaBoxId}', {
        render: {$componentName},
        icon: 'admin-generic'
    });
})(window.wp);
JS;
    }

    /**
     * Generate a React component name from meta box ID
     *
     * @param string $metaBoxId Meta box ID
     * @return string Component name in PascalCase
     */
    protected function generateComponentName(string $metaBoxId): string
    {
        // Convert 'meta-box-product-info' to 'MetaBoxProductInfo'
        $parts = explode('-', $metaBoxId);
        $parts = array_map('ucfirst', $parts);
        return implode('', $parts) . 'Panel';
    }

    /**
     * Generate JavaScript expression for reading a meta value (supports hyphenated keys)
     *
     * @param string $key Meta key
     * @return string JavaScript expression e.g. meta["first-name"]
     */
    protected function metaValueExpression(string $key): string
    {
        $keyJs = json_encode($key);

        return 'meta[' . $keyJs . ']';
    }

    /**
     * Generate JavaScript for a single field control
     *
     * @param string $key Field key
     * @param array $field Field configuration
     * @return string JavaScript code
     */
    protected function generateFieldControl(string $key, array $field): string
    {
        $type = $field['type'];
        $label = addslashes($field['description'] ?: ucwords(str_replace(['_', '-'], ' ', $key)));

        return match ($type) {
            'boolean' => $this->generateBooleanControl($key, $label),
            'integer' => $this->generateNumberControl($key, $label, '1'),
            'number' => $this->generateNumberControl($key, $label, 'any'),
            'string' => !empty($field['multiline'])
                ? $this->generateTextareaControl($key, $label)
                : $this->generateTextControl($key, $label),
            default => $this->generateTextControl($key, $label),
        };
    }

    /**
     * Generate text control JavaScript
     *
     * @param string $key Field key
     * @param string $label Field label
     * @return string JavaScript code
     */
    protected function generateTextControl(string $key, string $label): string
    {
        $metaValue = $this->metaValueExpression($key);

        return "el(TextControl, {
                label: '{$label}',
                value: {$metaValue} || '',
                onChange: function(value) { updateMeta(" . json_encode($key) . ", value); }
            })";
    }

    /**
     * Generate textarea control JavaScript
     *
     * @param string $key Field key
     * @param string $label Field label
     * @return string JavaScript code
     */
    protected function generateTextareaControl(string $key, string $label): string
    {
        $metaValue = $this->metaValueExpression($key);

        return "el(TextareaControl, {
                label: '{$label}',
                value: {$metaValue} || '',
                onChange: function(value) { updateMeta(" . json_encode($key) . ", value); }
            })";
    }

    /**
     * Generate boolean (toggle) control JavaScript
     *
     * @param string $key Field key
     * @param string $label Field label
     * @return string JavaScript code
     */
    protected function generateBooleanControl(string $key, string $label): string
    {
        $metaValue = $this->metaValueExpression($key);

        return "el(ToggleControl, {
                label: '{$label}',
                checked: !!{$metaValue},
                onChange: function(value) { updateMeta(" . json_encode($key) . ", value); }
            })";
    }

    /**
     * Generate number control JavaScript
     *
     * @param string $key Field key
     * @param string $label Field label
     * @param string $step Step value ('1' for integer, 'any' for float)
     * @return string JavaScript code
     */
    protected function generateNumberControl(string $key, string $label, string $step): string
    {
        $parseFunc = $step === '1' ? 'parseInt' : 'parseFloat';
        $metaValue = $this->metaValueExpression($key);

        return "el(NumberControl, {
                label: '{$label}',
                value: {$metaValue} || '',
                onChange: function(value) { updateMeta(" . json_encode($key) . ", {$parseFunc}(value) || 0); },
                step: '{$step}'
            })";
    }

    /**
     * Get all registered fields
     *
     * @return array
     */
    public function getFields(): array
    {
        return $this->fields;
    }
}
