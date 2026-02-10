<?php

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Mock WordPress functions
if (!function_exists('add_filter')) {
    function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {
        global $wp_filter;
        if (!isset($wp_filter)) {
            $wp_filter = [];
        }
        if (!isset($wp_filter[$hook])) {
            $wp_filter[$hook] = [];
        }
        if (!isset($wp_filter[$hook][$priority])) {
            $wp_filter[$hook][$priority] = [];
        }
        $wp_filter[$hook][$priority][] = $callback;
        return true;
    }
}

if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
        return add_filter($hook, $callback, $priority, $accepted_args);
    }
}

if (!function_exists('get_post_meta')) {
    function get_post_meta($post_id, $key = '', $single = false) {
        global $wp_post_meta;
        if (!isset($wp_post_meta)) {
            $wp_post_meta = [];
        }
        if (!isset($wp_post_meta[$post_id])) {
            return $single ? '' : [];
        }
        if ($key === '') {
            return $wp_post_meta[$post_id];
        }
        if (!isset($wp_post_meta[$post_id][$key])) {
            return $single ? '' : [];
        }
        $value = $wp_post_meta[$post_id][$key];
        return $single ? $value : [$value];
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters($hook, $value, ...$args) {
        global $wp_filter;
        if (!isset($wp_filter[$hook])) {
            return $value;
        }
        foreach ($wp_filter[$hook] as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                $value = call_user_func_array($callback, array_merge([$value], $args));
            }
        }
        return $value;
    }
}

if (!function_exists('do_action')) {
    function do_action($hook, ...$args) {
        global $wp_filter;
        if (!isset($wp_filter[$hook])) {
            return;
        }
        foreach ($wp_filter[$hook] as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                call_user_func_array($callback, $args);
            }
        }
    }
}

if (!function_exists('update_post_meta')) {
    function update_post_meta($post_id, $meta_key, $meta_value, $prev_value = '') {
        global $wp_post_meta;
        if (!isset($wp_post_meta)) {
            $wp_post_meta = [];
        }
        if (!isset($wp_post_meta[$post_id])) {
            $wp_post_meta[$post_id] = [];
        }
        $wp_post_meta[$post_id][$meta_key] = $meta_value;
        return true;
    }
}

if (!function_exists('delete_post_meta')) {
    function delete_post_meta($post_id, $meta_key, $meta_value = '') {
        global $wp_post_meta;
        if (isset($wp_post_meta[$post_id][$meta_key])) {
            unset($wp_post_meta[$post_id][$meta_key]);
            return true;
        }
        return false;
    }
}

if (!function_exists('register_post_meta')) {
    function register_post_meta($post_type, $meta_key, $args = []) {
        global $wp_registered_post_meta;
        if (!isset($wp_registered_post_meta)) {
            $wp_registered_post_meta = [];
        }
        if (!isset($wp_registered_post_meta[$post_type])) {
            $wp_registered_post_meta[$post_type] = [];
        }
        $wp_registered_post_meta[$post_type][$meta_key] = $args;
        return true;
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can($capability, ...$args) {
        global $wp_current_user_capabilities;
        if (!isset($wp_current_user_capabilities)) {
            $wp_current_user_capabilities = [];
        }
        return in_array($capability, $wp_current_user_capabilities, true);
    }
}

// Helper function to set post meta for testing
function set_test_post_meta($post_id, $key, $value) {
    global $wp_post_meta;
    if (!isset($wp_post_meta)) {
        $wp_post_meta = [];
    }
    if (!isset($wp_post_meta[$post_id])) {
        $wp_post_meta[$post_id] = [];
    }
    $wp_post_meta[$post_id][$key] = $value;
}

if (!function_exists('wp_get_attachment_image_url')) {
    function wp_get_attachment_image_url($attachment_id, $size = 'thumbnail', $icon = false) {
        global $wp_attachments;
        if (!isset($wp_attachments)) {
            $wp_attachments = [];
        }
        if (!isset($wp_attachments[$attachment_id])) {
            return false;
        }
        return $wp_attachments[$attachment_id][$size] ?? $wp_attachments[$attachment_id]['url'] ?? false;
    }
}

if (!function_exists('esc_url')) {
    function esc_url($url) {
        return htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

// Helper function to set attachment for testing
function set_test_attachment($attachment_id, $url, $size = 'thumbnail') {
    global $wp_attachments;
    if (!isset($wp_attachments)) {
        $wp_attachments = [];
    }
    if (!isset($wp_attachments[$attachment_id])) {
        $wp_attachments[$attachment_id] = [];
    }
    $wp_attachments[$attachment_id][$size] = $url;
    $wp_attachments[$attachment_id]['url'] = $url;
}

// Helper function to set user capabilities for testing
function set_current_user_capabilities(array $capabilities) {
    global $wp_current_user_capabilities;
    $wp_current_user_capabilities = $capabilities;
}

// Helper function to clear WordPress globals for testing
function clear_wordpress_globals() {
    global $wp_filter, $wp_post_meta, $wp_attachments, $wp_registered_post_meta, $wp_current_user_capabilities, $wp_scripts, $typenow;
    $wp_filter = [];
    $wp_post_meta = [];
    $wp_attachments = [];
    $wp_registered_post_meta = [];
    $wp_current_user_capabilities = [];
    $wp_scripts = [];
    $typenow = null;
}

// Mock WordPress script functions for block editor
if (!function_exists('wp_register_script')) {
    function wp_register_script($handle, $src, $deps = [], $ver = false, $in_footer = false) {
        global $wp_scripts;
        if (!isset($wp_scripts)) {
            $wp_scripts = [];
        }
        $wp_scripts[$handle] = [
            'src' => $src,
            'deps' => $deps,
            'ver' => $ver,
            'in_footer' => $in_footer,
            'inline' => [],
            'enqueued' => false
        ];
        return true;
    }
}

if (!function_exists('wp_add_inline_script')) {
    function wp_add_inline_script($handle, $data, $position = 'after') {
        global $wp_scripts;
        if (!isset($wp_scripts)) {
            $wp_scripts = [];
        }
        if (!isset($wp_scripts[$handle])) {
            $wp_scripts[$handle] = ['inline' => []];
        }
        if (!isset($wp_scripts[$handle]['inline'])) {
            $wp_scripts[$handle]['inline'] = [];
        }
        if (!isset($wp_scripts[$handle]['inline'][$position])) {
            $wp_scripts[$handle]['inline'][$position] = [];
        }
        $wp_scripts[$handle]['inline'][$position][] = $data;
        return true;
    }
}

if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script($handle, $src = '', $deps = [], $ver = false, $in_footer = false) {
        global $wp_scripts;
        if (!isset($wp_scripts)) {
            $wp_scripts = [];
        }
        if (!isset($wp_scripts[$handle])) {
            wp_register_script($handle, $src, $deps, $ver, $in_footer);
        }
        $wp_scripts[$handle]['enqueued'] = true;
        return true;
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_js')) {
    function esc_js($text) {
        return addslashes($text);
    }
}

if (!function_exists('get_post')) {
    function get_post($post_id = null) {
        return (object) ['ID' => $post_id];
    }
}

if (!defined('DOING_AUTOSAVE')) {
    define('DOING_AUTOSAVE', false);
}
