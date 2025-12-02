<?php
/**
 * Plugin Name: Hello World
 * Description: A simple example plugin demonstrating the Forge CMS plugin and tag system.
 * Version: 1.0.0
 * Author: Forge CMS
 */

// Prevent direct access
defined('CMS_ROOT') or die('Direct access not allowed');

// =========================================================================
// Example: Register a simple tag
// Usage in content: {hello} or {hello name="World"}
// =========================================================================
register_tag('hello', function($attrs) {
    $name = $attrs['name'] ?? 'World';
    return '<span style="color: #6366f1; font-weight: 600;">👋 Hello, ' . esc($name) . '!</span>';
}, ['description' => 'Simple greeting tag']);

// =========================================================================
// Example: Tag with content
// Usage: {highlight color="yellow"}Important text{/highlight}
// =========================================================================
register_tag('highlight', function($attrs, $content) {
    $color = $attrs['color'] ?? 'yellow';
    $colors = [
        'yellow' => '#fef08a',
        'green' => '#bbf7d0', 
        'blue' => '#bfdbfe',
        'pink' => '#fbcfe8',
    ];
    $bg = $colors[$color] ?? $colors['yellow'];
    return '<mark style="background: ' . $bg . '; padding: 0.1em 0.3em; border-radius: 4px;">' . $content . '</mark>';
}, ['has_content' => true, 'description' => 'Highlight text with color']);

// =========================================================================
// Example: Add action hook
// =========================================================================
add_action('plugins_loaded', function() {
    // Plugin is now active and ready
});

// =========================================================================
// Example: Add content to theme footer
// =========================================================================
add_action('theme_footer', function() {
    echo '<p style="text-align: center; padding: 1rem; color: #666; font-size: 0.875rem;">👋 Hello from the Hello World plugin!</p>';
});

// =========================================================================
// Example: Filter post content
// =========================================================================
add_filter('the_content', function($content) {
    // You could modify content here
    return $content;
});

// Activation hook
add_action('plugin_activate_hello-world', function() {
    // Run code when plugin is activated
});

// Deactivation hook  
add_action('plugin_deactivate_hello-world', function() {
    // Run code when plugin is deactivated
});
