<?php
/**
 * Plugin Name: Hello World
 * Description: A simple example plugin demonstrating the Forge CMS plugin system.
 * Version: 1.0.0
 * Author: Forge CMS
 */

// Prevent direct access
defined('CMS_ROOT') or die('Direct access not allowed');

// Add action when plugins are loaded
add_action('plugins_loaded', function() {
    // Plugin is now active and ready
});

// Example: Add content to the footer
add_action('theme_footer', function() {
    echo '<p style="text-align: center; padding: 1rem; color: #666; font-size: 0.875rem;">👋 Hello from the Hello World plugin!</p>';
});

// Example: Filter post content
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
