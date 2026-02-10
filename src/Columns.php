<?php
namespace RalfHortt\Meta;

class Columns
{
    protected array $postTypes = [];
    protected array $columns = [];
    protected array $sortableColumns = [];
    protected array $renderers = [];
    protected int $priority = 10;

    /**
     * Create a new Columns instance for specified post type(s)
     *
     * @param string|array $postTypes Single post type or array of post types
     * @return self
     */
    public static function for(string|array $postTypes): self
    {
        $instance = new self();
        $instance->postTypes = (array) $postTypes;
        return $instance;
    }

    /**
     * Add a column to the post list table
     *
     * @param string $key Meta key to display
     * @param string $label Column header label
     * @param callable|null $render Optional custom render function with signature: fn(int $postId): string
     * @return self
     * @throws \InvalidArgumentException If key or label is empty
     */
    public function add(string $key, string $label, ?callable $render = null): self
    {
        if (empty($key)) {
            throw new \InvalidArgumentException('Column key cannot be empty');
        }

        if (empty($label)) {
            throw new \InvalidArgumentException('Column label cannot be empty');
        }

        $this->columns[$key] = $label;

        if ($render !== null) {
            $this->renderers[$key] = $render;
        }

        return $this;
    }

    /**
     * Make column(s) sortable
     *
     * @param string|array $keys Single key or array of keys to make sortable
     * @return self
     */
    public function sortable(string|array $keys): self
    {
        $keys = (array) $keys;
        $this->sortableColumns = array_merge($this->sortableColumns, $keys);
        return $this;
    }

    /**
     * Add a currency column with formatted output
     *
     * @param string $key Meta key to display
     * @param string $label Column header label
     * @param int $decimals Number of decimal places (default: 2)
     * @param string $currencySymbol Currency symbol (default: '$')
     * @param string $symbolPosition Position of symbol: 'before' or 'after' (default: 'before')
     * @return self
     */
    public function addCurrency(
        string $key,
        string $label,
        int $decimals = 2,
        string $currencySymbol = '$',
        string $symbolPosition = 'before'
    ): self {
        return $this->add(
            key: $key,
            label: $label,
            render: function (int $postId) use ($key, $decimals, $currencySymbol, $symbolPosition): string {
                $value = get_post_meta($postId, $key, true);
                
                if ($value === '' || $value === null) {
                    return '';
                }
                
                $formatted = number_format((float) $value, $decimals);
                
                return $symbolPosition === 'after'
                    ? $formatted . $currencySymbol
                    : $currencySymbol . $formatted;
            }
        );
    }

    /**
     * Add a date column with formatted output
     *
     * @param string $key Meta key to display
     * @param string $label Column header label
     * @param string $format PHP date format (default: 'Y-m-d')
     * @param string $inputFormat Expected format of stored date, or 'timestamp' for Unix timestamps (default: 'Y-m-d')
     * @return self
     */
    public function addDate(
        string $key,
        string $label,
        string $format = 'Y-m-d',
        string $inputFormat = 'Y-m-d'
    ): self {
        return $this->add(
            key: $key,
            label: $label,
            render: function (int $postId) use ($key, $format, $inputFormat): string {
                $value = get_post_meta($postId, $key, true);
                
                if ($value === '' || $value === null) {
                    return '';
                }
                
                try {
                    if ($inputFormat === 'timestamp') {
                        $date = new \DateTime('@' . (int) $value);
                    } else {
                        $date = \DateTime::createFromFormat($inputFormat, $value);
                    }
                    
                    return $date ? $date->format($format) : '';
                } catch (\Exception $e) {
                    return '';
                }
            }
        );
    }

    /**
     * Add a boolean column with Yes/No display
     *
     * @param string $key Meta key to display
     * @param string $label Column header label
     * @param string $trueLabel Label for true value (default: 'Yes')
     * @param string $falseLabel Label for false value (default: 'No')
     * @return self
     */
    public function addBoolean(
        string $key,
        string $label,
        string $trueLabel = 'Yes',
        string $falseLabel = 'No'
    ): self {
        return $this->add(
            key: $key,
            label: $label,
            render: function (int $postId) use ($key, $trueLabel, $falseLabel): string {
                $value = get_post_meta($postId, $key, true);
                
                // Handle various truthy/falsy representations
                $isTruthy = in_array($value, [true, 1, '1', 'yes', 'on', 'true'], true);
                
                return $isTruthy ? $trueLabel : $falseLabel;
            }
        );
    }

    /**
     * Add an image column with thumbnail display
     *
     * @param string $key Meta key containing image URL or attachment ID
     * @param string $label Column header label
     * @param int $width Thumbnail width in pixels (default: 50)
     * @param int $height Thumbnail height in pixels (default: 50)
     * @param bool $isAttachmentId Whether the meta value is an attachment ID (default: false)
     * @return self
     */
    public function addImage(
        string $key,
        string $label,
        int $width = 50,
        int $height = 50,
        bool $isAttachmentId = false
    ): self {
        return $this->add(
            key: $key,
            label: $label,
            render: function (int $postId) use ($key, $width, $height, $isAttachmentId): string {
                $value = get_post_meta($postId, $key, true);
                
                if ($value === '' || $value === null) {
                    return '';
                }
                
                if ($isAttachmentId) {
                    $imageUrl = wp_get_attachment_image_url((int) $value, 'thumbnail');
                    if (!$imageUrl) {
                        return '';
                    }
                } else {
                    $imageUrl = $value;
                }
                
                return sprintf(
                    '<img src="%s" width="%d" height="%d" style="object-fit: cover;" alt="" />',
                    esc_url($imageUrl),
                    $width,
                    $height
                );
            }
        );
    }

    /**
     * Add a list column for array or serialized data
     *
     * @param string $key Meta key to display
     * @param string $label Column header label
     * @param string $separator Separator for list items (default: ', ')
     * @param int $limit Maximum number of items to display, 0 for unlimited (default: 0)
     * @return self
     */
    public function addList(
        string $key,
        string $label,
        string $separator = ', ',
        int $limit = 0
    ): self {
        return $this->add(
            key: $key,
            label: $label,
            render: function (int $postId) use ($key, $separator, $limit): string {
                $value = get_post_meta($postId, $key, true);
                
                if ($value === '' || $value === null) {
                    return '';
                }
                
                // Convert to array if not already
                if (!is_array($value)) {
                    return (string) $value;
                }
                
                // Limit items if specified
                if ($limit > 0 && count($value) > $limit) {
                    $value = array_slice($value, 0, $limit);
                    $more = true;
                } else {
                    $more = false;
                }
                
                $output = implode($separator, array_map('esc_html', $value));
                
                if ($more) {
                    $output .= $separator . '...';
                }
                
                return $output;
            }
        );
    }

    /**
     * Set the priority for column registration
     *
     * @param int $value WordPress hook priority (default: 10)
     * @return self
     */
    public function priority(int $value): self
    {
        $this->priority = $value;
        return $this;
    }

    /**
     * Register all columns with WordPress
     *
     * @return void
     */
    public function register(): void
    {
        $this->registerColumns();
        $this->registerSortableColumns();
    }

    /**
     * Register column filters and actions for all post types
     *
     * @return void
     */
    protected function registerColumns(): void
    {
        foreach ($this->postTypes as $postType) {
            $type = match ($postType) {
                'post' => 'posts',
                'page' => 'pages',
                default => $postType
            };

            add_filter("manage_{$type}_posts_columns", [$this, 'addColumns'], $this->priority);
            add_action("manage_{$type}_posts_custom_column", [$this, 'renderColumn'], 10, 2);
        }
    }

    /**
     * Register sortable column filters for all post types
     *
     * @return void
     */
    protected function registerSortableColumns(): void
    {
        foreach ($this->postTypes as $postType) {
            $type = match ($postType) {
                'post' => 'posts',
                'page' => 'pages',
                default => $postType
            };

            add_filter("manage_edit-{$type}_sortable_columns", [$this, 'addSortableColumns']);
        }
    }

    /**
     * WordPress filter callback: Add columns to list table
     *
     * @param array $columns Existing columns
     * @return array Modified columns
     */
    public function addColumns(array $columns): array
    {
        foreach ($this->columns as $key => $label) {
            $columns[$key] = $label;
        }

        return $columns;
    }

    /**
     * WordPress filter callback: Add sortable columns
     *
     * @param array $columns Existing sortable columns
     * @return array Modified sortable columns
     */
    public function addSortableColumns(array $columns): array
    {
        foreach ($this->sortableColumns as $key) {
            $columns[$key] = $key;
        }

        return $columns;
    }

    /**
     * WordPress action callback: Render column content
     *
     * @param string $column Column key
     * @param int $postId Post ID
     * @return void
     */
    public function renderColumn(string $column, int $postId): void
    {
        if (!array_key_exists($column, $this->columns)) {
            return;
        }

        // Check for custom renderer
        if (isset($this->renderers[$column])) {
            echo $this->renderers[$column]($postId);
            return;
        }

        // Default: display meta value
        echo get_post_meta($postId, $column, true);
    }
}
