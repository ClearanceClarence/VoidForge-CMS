<?php
/**
 * Plugin Name: Hello World
 * Description: A simple example plugin that adds a greeting shortcode and admin page.
 * Version: 1.0.0
 * Author: VoidForge
 * Author URI: https://voidforge.dev
 * Requires PHP: 8.0
 * Requires CMS: 0.1.2
 */

defined('CMS_ROOT') or die;

// Add a simple shortcode: [hello name="World"]
add_shortcode('hello', function($atts) {
    $name = $atts['name'] ?? 'World';
    return '<p style="padding: 1rem; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff; border-radius: 8px; font-weight: 600;">Hello, ' . esc($name) . '! 👋</p>';
});

// Add admin page under Plugins menu
add_admin_page('hello-world-settings', [
    'title' => 'Hello World',
    'menu_title' => 'Hello World',
    'icon' => 'smile',
    'callback' => function() {
        ?>
        <div style="max-width: 600px;">
            <h1 style="font-size: 1.75rem; font-weight: 700; margin: 0 0 1rem 0;">👋 Hello World Plugin</h1>
            <p style="color: var(--text-muted); margin-bottom: 2rem;">This is a simple example plugin for VoidForge CMS.</p>
            
            <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 1.5rem;">
                <h2 style="font-size: 1.125rem; margin: 0 0 1rem 0;">Usage</h2>
                <p style="margin-bottom: 1rem;">Add this shortcode to any post or page:</p>
                <code style="display: block; background: #1e293b; color: #e2e8f0; padding: 1rem; border-radius: 8px; font-family: monospace;">[hello name="Your Name"]</code>
            </div>
        </div>
        <?php
    }
]);
