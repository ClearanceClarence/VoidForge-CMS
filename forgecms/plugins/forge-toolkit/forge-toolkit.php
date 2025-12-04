<?php
/**
 * Plugin Name: Forge Toolkit
 * Description: Advanced content components for Forge CMS - buttons, alerts, tabs, accordions, modals, progress bars, timelines, and more
 * Version: 2.0.0
 * Author: Forge CMS
 */

defined('CMS_ROOT') or die('Direct access not allowed');

// =========================================================================
// Plugin Activation - Create Demo Page
// =========================================================================
add_action('plugin_activate_forge-toolkit', function() {
    $existingPage = Post::findBySlug('toolkit-demo', 'page');
    if ($existingPage) {
        return;
    }
    
    $content = <<<'HTML'
<p class="lead">Welcome to the <strong>Forge Toolkit</strong> — a comprehensive collection of content components to build beautiful, interactive pages.</p>

{divider style="gradient"}

<h2>{icon name="mouse-pointer"} Buttons</h2>

<h4>Button Styles</h4>
<p>
{button href="#" style="primary"}Primary{/button}
{button href="#" style="secondary"}Secondary{/button}
{button href="#" style="success"}Success{/button}
{button href="#" style="danger"}Danger{/button}
{button href="#" style="warning"}Warning{/button}
{button href="#" style="outline"}Outline{/button}
{button href="#" style="ghost"}Ghost{/button}
</p>

<h4>Button Sizes</h4>
<p>
{button href="#" style="primary" size="sm"}Small{/button}
{button href="#" style="primary" size="md"}Medium{/button}
{button href="#" style="primary" size="lg"}Large{/button}
</p>

<h4>Button with Icons</h4>
<p>
{button href="#" style="primary" icon="download"}Download{/button}
{button href="#" style="success" icon="check"}Confirm{/button}
{button href="#" style="danger" icon="x"}Cancel{/button}
</p>

{code lang="html"}{button href="/contact" style="primary" size="lg" icon="mail"}Contact Us{/button}{/code}

{divider style="gradient"}

<h2>{icon name="bell"} Alerts & Notices</h2>

{alert type="info" title="Information" icon="true"}This is an informational alert for helpful tips or notes.{/alert}

{alert type="success" title="Success" icon="true"}Great job! Your action was completed successfully.{/alert}

{alert type="warning" title="Warning" icon="true"}Be careful! This action might have consequences.{/alert}

{alert type="danger" title="Error" icon="true"}Something went wrong. Please try again.{/alert}

{alert type="info" dismissible="true"}This alert can be dismissed by clicking the X button →{/alert}

{divider style="gradient"}

<h2>{icon name="layout"} Tabs</h2>

{tabs}
{tab title="Overview"}
<h4>Welcome to Tabs</h4>
<p>Tabs help organize content into logical sections. Users can switch between tabs without leaving the page.</p>
{button href="#" style="primary"}Learn More{/button}
{/tab}
{tab title="Features"}
<h4>Key Features</h4>
<ul>
<li>Smooth transitions between tabs</li>
<li>Accessible keyboard navigation</li>
<li>Responsive design for mobile</li>
<li>Support for any HTML content</li>
</ul>
{/tab}
{tab title="Code"}
{code lang="html"}{tabs}
{tab title="First"}Content{/tab}
{tab title="Second"}More{/tab}
{/tabs}{/code}
{/tab}
{/tabs}

{divider style="gradient"}

<h2>{icon name="chevron-down"} Accordions</h2>

{accordion}
{accordion-item title="What is Forge CMS?" open="true"}
Forge CMS is a modern, lightweight content management system built with PHP. It features a clean admin interface and powerful plugin system.
{/accordion-item}
{accordion-item title="How do I install plugins?"}
Navigate to the Plugins page in your admin panel. You can activate built-in plugins or upload new ones.
{/accordion-item}
{accordion-item title="Can I customize the theme?"}
Absolutely! Themes are in the /themes directory. You can modify existing themes or create your own.
{/accordion-item}
{accordion-item title="Is Forge CMS free?"}
Yes! Forge CMS is open-source and free under the MIT license.
{/accordion-item}
{/accordion}

{divider style="gradient"}

<h2>{icon name="loader"} Progress Bars</h2>

{progress value="85" label="Project Completion" color="primary"}
{progress value="92" label="Customer Satisfaction" color="success"}
{progress value="67" label="Tasks Completed" color="warning"}
{progress value="45" label="Bug Fixes Remaining" color="danger"}

<h4>Animated Progress</h4>
{progress value="75" label="Loading..." color="primary" animated="true" striped="true"}

{divider style="gradient"}

<h2>{icon name="clock"} Timeline</h2>

{timeline}
{timeline-item date="January 2024" title="Project Started" icon="flag"}
Initial planning and research phase. Defined project scope.
{/timeline-item}
{timeline-item date="March 2024" title="Development Phase" icon="code"}
Core development began. Built the foundation and admin interface.
{/timeline-item}
{timeline-item date="June 2024" title="Beta Release" icon="package"}
Released beta version. Gathered feedback and fixed issues.
{/timeline-item}
{timeline-item date="September 2024" title="Version 1.0" icon="check-circle" status="current"}
Official stable release with full documentation.
{/timeline-item}
{/timeline}

{divider style="gradient"}

<h2>{icon name="grid"} Cards & Grids</h2>

{grid cols="3" gap="1.5rem"}
{card title="Getting Started" icon="book"}
Learn the basics of Forge CMS and create your first page.
{button href="#" style="outline" size="sm"}Read Guide{/button}
{/card}
{card title="Customization" icon="palette"}
Discover how to customize themes and add plugins.
{button href="#" style="outline" size="sm"}Explore{/button}
{/card}
{card title="API Reference" icon="code"}
Complete documentation for developers.
{button href="#" style="outline" size="sm"}View Docs{/button}
{/card}
{/grid}

{divider style="gradient"}

<h2>{icon name="type"} Typography</h2>

{lead}This is a lead paragraph with larger, more prominent text.{/lead}

<p>Regular paragraph with <strong>bold</strong>, <em>italic</em>, and <code>inline code</code>.</p>

{highlight color="yellow"}Yellow highlighted text for important info.{/highlight}

{highlight color="green"}Green highlight for success messages.{/highlight}

{highlight color="blue"}Blue highlight for notes.{/highlight}

{divider style="gradient"}

<h2>{icon name="message-circle"} Blockquotes</h2>

{quote author="Steve Jobs" source="Stanford, 2005" style="modern"}
Your time is limited, don't waste it living someone else's life.
{/quote}

{divider style="gradient"}

<h2>{icon name="star"} Badges & Labels</h2>

<p>
{badge color="blue"}Featured{/badge}
{badge color="green"}New{/badge}
{badge color="red"}Hot{/badge}
{badge color="yellow"}Sale{/badge}
{badge color="purple"}Premium{/badge}
{badge color="gray"}Draft{/badge}
{badge color="gradient"}Special{/badge}
</p>

<h4>Pill Badges</h4>
<p>
{badge color="blue" pill="true"}v2.0.0{/badge}
{badge color="green" pill="true"}Stable{/badge}
{badge color="purple" pill="true"}Pro{/badge}
</p>

{divider style="gradient"}

<h2>{icon name="code"} Code Blocks</h2>

{code lang="php" title="Example PHP"}<?php
function factorial(int $n): int {
    if ($n <= 1) return 1;
    return $n * factorial($n - 1);
}
echo factorial(5); // 120{/code}

{divider style="gradient"}

<h2>{icon name="columns"} Columns</h2>

{columns gap="2rem"}
{col}
<h4>Column One</h4>
<p>First column content. Great for comparisons.</p>
{/col}
{col}
<h4>Column Two</h4>
<p>Second column. Layout auto-adjusts.</p>
{/col}
{col}
<h4>Column Three</h4>
<p>Third column. Stacks on mobile.</p>
{/col}
{/columns}

{divider style="gradient"}

<h2>{icon name="zap"} Icons</h2>

<p>
{icon name="star" size="24" color="#f59e0b"}
{icon name="heart" size="24" color="#ef4444"}
{icon name="check" size="24" color="#10b981"}
{icon name="mail" size="24" color="#6366f1"}
{icon name="phone" size="24" color="#8b5cf6"}
{icon name="map-pin" size="24" color="#ec4899"}
</p>

{divider style="gradient"}

<h2>{icon name="tool"} Utilities</h2>

<p><strong>Year:</strong> © {year}</p>
<p><strong>Site:</strong> {sitename}</p>
<p><strong>Tooltip:</strong> Hover {tooltip text="This is a tooltip!"}here{/tooltip}</p>

{alert type="success" icon="true"}
You're all set! Use these components to create beautiful pages.
{/alert}
HTML;

    Post::create([
        'post_type' => 'page',
        'title' => 'Toolkit Components Demo',
        'slug' => 'toolkit-demo',
        'content' => $content,
        'status' => 'published',
        'author_id' => 1,
    ]);
});

add_action('plugin_deactivate_forge-toolkit', function() {
    $page = Post::findBySlug('toolkit-demo', 'page');
    if ($page) {
        Post::update($page['id'], ['status' => 'trash']);
    }
});

// =========================================================================
// Helper Function - Get Icon SVG
// =========================================================================
function getForgeIcon($name, $size = 20, $color = 'currentColor') {
    $icons = [
        'star' => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>',
        'heart' => '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>',
        'check' => '<polyline points="20 6 9 17 4 12"></polyline>',
        'check-circle' => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline>',
        'x' => '<line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line>',
        'x-circle' => '<circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line>',
        'arrow-right' => '<line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline>',
        'chevron-down' => '<polyline points="6 9 12 15 18 9"></polyline>',
        'info' => '<circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line>',
        'alert-triangle' => '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line>',
        'mail' => '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline>',
        'phone' => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"></path>',
        'map-pin' => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle>',
        'calendar' => '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line>',
        'clock' => '<circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline>',
        'user' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle>',
        'download' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line>',
        'upload' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line>',
        'image' => '<rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline>',
        'file' => '<path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path><polyline points="13 2 13 9 20 9"></polyline>',
        'folder' => '<path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>',
        'edit' => '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>',
        'trash' => '<polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>',
        'plus' => '<line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line>',
        'eye' => '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>',
        'bell' => '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path>',
        'flag' => '<path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path><line x1="4" y1="22" x2="4" y2="15"></line>',
        'code' => '<polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline>',
        'globe' => '<circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>',
        'zap' => '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>',
        'layout' => '<rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line>',
        'mouse-pointer' => '<path d="M3 3l7.07 16.97 2.51-7.39 7.39-2.51L3 3z"></path><path d="M13 13l6 6"></path>',
        'type' => '<polyline points="4 7 4 4 20 4 20 7"></polyline><line x1="9" y1="20" x2="15" y2="20"></line><line x1="12" y1="4" x2="12" y2="20"></line>',
        'message-circle' => '<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>',
        'package' => '<line x1="16.5" y1="9.4" x2="7.5" y2="4.21"></line><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line>',
        'circle' => '<circle cx="12" cy="12" r="10"></circle>',
        'loader' => '<line x1="12" y1="2" x2="12" y2="6"></line><line x1="12" y1="18" x2="12" y2="22"></line><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line><line x1="2" y1="12" x2="6" y2="12"></line><line x1="18" y1="12" x2="22" y2="12"></line><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line>',
        'grid' => '<rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect>',
        'columns' => '<path d="M12 3h7a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-7m0-18H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h7m0-18v18"></path>',
        'book' => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>',
        'palette' => '<circle cx="13.5" cy="6.5" r=".5"></circle><circle cx="17.5" cy="10.5" r=".5"></circle><circle cx="8.5" cy="7.5" r=".5"></circle><circle cx="6.5" cy="12.5" r=".5"></circle><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.555C21.965 6.012 17.461 2 12 2z"></path>',
        'tool' => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>',
        'refresh' => '<polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>',
    ];
    
    $path = $icons[$name] ?? $icons['circle'];
    return '<svg width="' . esc($size) . '" height="' . esc($size) . '" viewBox="0 0 24 24" fill="none" stroke="' . esc($color) . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: inline-block; vertical-align: middle;">' . $path . '</svg>';
}

// =========================================================================
// Content Tags
// =========================================================================

register_tag('button', function($attrs, $content) {
    $href = $attrs['href'] ?? '#';
    $style = $attrs['style'] ?? 'primary';
    $size = $attrs['size'] ?? 'md';
    $icon = $attrs['icon'] ?? '';
    
    $styles = [
        'primary' => 'background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff;',
        'secondary' => 'background: #f1f5f9; color: #475569;',
        'success' => 'background: linear-gradient(135deg, #10b981, #059669); color: #fff;',
        'danger' => 'background: linear-gradient(135deg, #ef4444, #dc2626); color: #fff;',
        'warning' => 'background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff;',
        'outline' => 'background: transparent; border: 2px solid #6366f1; color: #6366f1;',
        'ghost' => 'background: transparent; color: #6366f1;',
    ];
    
    $sizes = [
        'sm' => 'padding: 0.5rem 1rem; font-size: 0.8125rem;',
        'md' => 'padding: 0.75rem 1.5rem; font-size: 0.875rem;',
        'lg' => 'padding: 1rem 2rem; font-size: 1rem;',
    ];
    
    $iconHtml = $icon ? '<span style="margin-right: 0.5rem;">' . getForgeIcon($icon, 16) . '</span>' : '';
    
    return '<a href="' . esc($href) . '" style="display: inline-flex; align-items: center; border-radius: 8px; text-decoration: none; font-weight: 600; transition: all 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.1); ' . ($styles[$style] ?? $styles['primary']) . ' ' . ($sizes[$size] ?? $sizes['md']) . '">' . $iconHtml . $content . '</a>';
}, ['has_content' => true]);


register_tag('alert', function($attrs, $content) {
    $type = $attrs['type'] ?? 'info';
    $title = $attrs['title'] ?? '';
    $showIcon = ($attrs['icon'] ?? '') === 'true';
    $dismissible = ($attrs['dismissible'] ?? '') === 'true';
    
    $config = [
        'info' => ['bg' => '#eff6ff', 'border' => '#3b82f6', 'text' => '#1e40af', 'icon' => 'info'],
        'success' => ['bg' => '#f0fdf4', 'border' => '#22c55e', 'text' => '#166534', 'icon' => 'check-circle'],
        'warning' => ['bg' => '#fffbeb', 'border' => '#f59e0b', 'text' => '#92400e', 'icon' => 'alert-triangle'],
        'danger' => ['bg' => '#fef2f2', 'border' => '#ef4444', 'text' => '#991b1b', 'icon' => 'x-circle'],
    ];
    
    $c = $config[$type] ?? $config['info'];
    $id = 'alert-' . uniqid();
    
    $html = '<div id="' . $id . '" style="display: flex; gap: 0.75rem; padding: 1rem 1.25rem; background: ' . $c['bg'] . '; border-left: 4px solid ' . $c['border'] . '; border-radius: 0 8px 8px 0; margin: 1rem 0; color: ' . $c['text'] . ';">';
    
    if ($showIcon) {
        $html .= '<div style="flex-shrink: 0;">' . getForgeIcon($c['icon'], 20, $c['border']) . '</div>';
    }
    
    $html .= '<div style="flex: 1;">';
    if ($title) $html .= '<div style="font-weight: 600; margin-bottom: 0.25rem;">' . esc($title) . '</div>';
    $html .= '<div>' . $content . '</div></div>';
    
    if ($dismissible) {
        $html .= '<button onclick="document.getElementById(\'' . $id . '\').remove()" style="background: none; border: none; cursor: pointer; color: ' . $c['text'] . '; opacity: 0.7;">' . getForgeIcon('x', 18) . '</button>';
    }
    
    return $html . '</div>';
}, ['has_content' => true]);


register_tag('tabs', function($attrs, $content) {
    $id = 'tabs-' . uniqid();
    preg_match_all('/\{tab\s+title="([^"]+)"\}(.*?)\{\/tab\}/s', $content, $matches, PREG_SET_ORDER);
    
    if (empty($matches)) return $content;
    
    $html = '<div id="' . $id . '"><div style="display: flex; border-bottom: 2px solid #e2e8f0; margin-bottom: 1.5rem;">';
    
    foreach ($matches as $i => $m) {
        $active = $i === 0 ? 'border-bottom: 2px solid #6366f1; color: #6366f1; margin-bottom: -2px;' : 'color: #64748b;';
        $html .= '<button onclick="switchTab' . $id . '(' . $i . ')" class="tb' . $id . '" style="padding: 0.75rem 1.5rem; background: none; border: none; font-weight: 500; cursor: pointer; ' . $active . '">' . esc($m[1]) . '</button>';
    }
    
    $html .= '</div>';
    
    foreach ($matches as $i => $m) {
        $html .= '<div class="tp' . $id . '" style="display: ' . ($i === 0 ? 'block' : 'none') . ';">' . $m[2] . '</div>';
    }
    
    $html .= '</div><script>function switchTab' . $id . '(n){document.querySelectorAll(".tb' . $id . '").forEach((b,i)=>{b.style.borderBottom=i===n?"2px solid #6366f1":"none";b.style.color=i===n?"#6366f1":"#64748b";b.style.marginBottom=i===n?"-2px":"0"});document.querySelectorAll(".tp' . $id . '").forEach((p,i)=>{p.style.display=i===n?"block":"none"})}</script>';
    
    return $html;
}, ['has_content' => true]);

register_tag('tab', function($a, $c) { return $c; }, ['has_content' => true]);


register_tag('accordion', function($attrs, $content) {
    $id = 'acc-' . uniqid();
    preg_match_all('/\{accordion-item\s+title="([^"]+)"(\s+open="true")?\}(.*?)\{\/accordion-item\}/s', $content, $matches, PREG_SET_ORDER);
    
    if (empty($matches)) return $content;
    
    $html = '<div style="border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden;">';
    
    foreach ($matches as $i => $m) {
        $open = !empty($m[2]);
        $iid = $id . '-' . $i;
        $border = $i > 0 ? 'border-top: 1px solid #e2e8f0;' : '';
        
        $html .= '<div style="' . $border . '">';
        $html .= '<button onclick="var c=document.getElementById(\'c' . $iid . '\'),v=document.getElementById(\'v' . $iid . '\');c.style.display=c.style.display===\'none\'?\'block\':\'none\';v.style.transform=c.style.display===\'none\'?\'rotate(0)\':\' rotate(180deg)\'" style="width: 100%; display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.25rem; background: #f8fafc; border: none; cursor: pointer; text-align: left; font-weight: 600; color: #1e293b;">';
        $html .= '<span>' . esc($m[1]) . '</span>';
        $html .= '<span id="v' . $iid . '" style="transition: transform 0.2s; ' . ($open ? 'transform: rotate(180deg);' : '') . '">' . getForgeIcon('chevron-down', 20) . '</span>';
        $html .= '</button>';
        $html .= '<div id="c' . $iid . '" style="display: ' . ($open ? 'block' : 'none') . '; padding: 1rem 1.25rem; color: #475569;">' . $m[3] . '</div>';
        $html .= '</div>';
    }
    
    return $html . '</div>';
}, ['has_content' => true]);

register_tag('accordion-item', function($a, $c) { return $c; }, ['has_content' => true]);


register_tag('progress', function($attrs) {
    $value = (int)($attrs['value'] ?? 0);
    $label = $attrs['label'] ?? '';
    $color = $attrs['color'] ?? 'primary';
    $striped = ($attrs['striped'] ?? '') === 'true';
    $animated = ($attrs['animated'] ?? '') === 'true';
    
    $colors = [
        'primary' => 'linear-gradient(135deg, #6366f1, #8b5cf6)',
        'success' => 'linear-gradient(135deg, #10b981, #059669)',
        'warning' => 'linear-gradient(135deg, #f59e0b, #d97706)',
        'danger' => 'linear-gradient(135deg, #ef4444, #dc2626)',
    ];
    
    $bg = $colors[$color] ?? $colors['primary'];
    $stripe = $striped ? 'background-image: linear-gradient(45deg, rgba(255,255,255,0.15) 25%, transparent 25%, transparent 50%, rgba(255,255,255,0.15) 50%, rgba(255,255,255,0.15) 75%, transparent 75%); background-size: 1rem 1rem;' : '';
    $anim = $animated ? 'animation: pstripe 1s linear infinite;' : '';
    
    $html = '<div style="margin: 1rem 0;">';
    if ($label) $html .= '<div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 0.875rem; font-weight: 500;"><span>' . esc($label) . '</span><span>' . $value . '%</span></div>';
    $html .= '<div style="height: 10px; background: #e5e7eb; border-radius: 999px; overflow: hidden;"><div style="width: ' . $value . '%; height: 100%; background: ' . $bg . '; border-radius: 999px; ' . $stripe . $anim . '"></div></div></div>';
    
    if ($animated) $html .= '<style>@keyframes pstripe{from{background-position:1rem 0}to{background-position:0 0}}</style>';
    
    return $html;
}, ['has_content' => false]);


register_tag('timeline', function($attrs, $content) {
    preg_match_all('/\{timeline-item\s+([^}]+)\}(.*?)\{\/timeline-item\}/s', $content, $matches, PREG_SET_ORDER);
    
    if (empty($matches)) return $content;
    
    $html = '<div style="position: relative; padding-left: 2rem; margin: 2rem 0;">';
    $html .= '<div style="position: absolute; left: 7px; top: 0; bottom: 0; width: 2px; background: linear-gradient(to bottom, #6366f1, #8b5cf6);"></div>';
    
    foreach ($matches as $m) {
        preg_match_all('/(\w+)="([^"]*)"/', $m[1], $am, PREG_SET_ORDER);
        $a = [];
        foreach ($am as $at) $a[$at[1]] = $at[2];
        
        $date = $a['date'] ?? '';
        $title = $a['title'] ?? '';
        $status = $a['status'] ?? '';
        
        $dot = $status === 'current' ? 'background: linear-gradient(135deg, #6366f1, #8b5cf6); box-shadow: 0 0 0 4px rgba(99,102,241,0.2);' : 'background: #fff; border: 2px solid #6366f1;';
        
        $html .= '<div style="position: relative; padding-bottom: 2rem;">';
        $html .= '<div style="position: absolute; left: -2rem; width: 16px; height: 16px; border-radius: 50%; ' . $dot . '"></div>';
        $html .= '<div style="padding-left: 1rem;">';
        if ($date) $html .= '<div style="font-size: 0.8125rem; color: #6366f1; font-weight: 600; margin-bottom: 0.25rem;">' . esc($date) . '</div>';
        if ($title) $html .= '<div style="font-size: 1.125rem; font-weight: 600; color: #1e293b; margin-bottom: 0.5rem;">' . esc($title) . '</div>';
        $html .= '<div style="color: #64748b;">' . trim($m[2]) . '</div>';
        $html .= '</div></div>';
    }
    
    return $html . '</div>';
}, ['has_content' => true]);

register_tag('timeline-item', function($a, $c) { return $c; }, ['has_content' => true]);


register_tag('grid', function($attrs, $content) {
    $cols = $attrs['cols'] ?? '3';
    $gap = $attrs['gap'] ?? '1.5rem';
    return '<div style="display: grid; grid-template-columns: repeat(' . esc($cols) . ', 1fr); gap: ' . esc($gap) . '; margin: 1.5rem 0;">' . $content . '</div>';
}, ['has_content' => true]);


register_tag('card', function($attrs, $content) {
    $title = $attrs['title'] ?? '';
    $icon = $attrs['icon'] ?? '';
    
    $html = '<div style="background: #fff; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); padding: 1.5rem; height: 100%;">';
    
    if ($icon) {
        $html .= '<div style="width: 48px; height: 48px; background: linear-gradient(135deg, #eff6ff, #dbeafe); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 1rem;">' . getForgeIcon($icon, 24, '#6366f1') . '</div>';
    }
    
    if ($title) $html .= '<h3 style="margin: 0 0 0.75rem; font-size: 1.125rem;">' . esc($title) . '</h3>';
    $html .= '<div style="color: #64748b;">' . $content . '</div></div>';
    
    return $html;
}, ['has_content' => true]);


register_tag('columns', function($attrs, $content) {
    $gap = $attrs['gap'] ?? '1.5rem';
    preg_match_all('/\{col\}/', $content, $m);
    $cols = count($m[0]) ?: 2;
    $content = preg_replace('/\{col\}/', '<div style="min-width: 0;">', $content);
    $content = preg_replace('/\{\/col\}/', '</div>', $content);
    return '<div style="display: grid; grid-template-columns: repeat(' . $cols . ', 1fr); gap: ' . esc($gap) . '; margin: 1rem 0;">' . $content . '</div>';
}, ['has_content' => true]);


register_tag('code', function($attrs, $content) {
    $lang = $attrs['lang'] ?? '';
    $title = $attrs['title'] ?? '';
    
    $html = '<div style="margin: 1rem 0; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">';
    if ($title || $lang) {
        $html .= '<div style="background: #1e293b; padding: 0.75rem 1rem; display: flex; justify-content: space-between; align-items: center;"><span style="color: #94a3b8; font-size: 0.8125rem; font-weight: 500;">' . esc($title ?: $lang) . '</span><div style="display: flex; gap: 6px;"><span style="width: 12px; height: 12px; border-radius: 50%; background: #ef4444;"></span><span style="width: 12px; height: 12px; border-radius: 50%; background: #f59e0b;"></span><span style="width: 12px; height: 12px; border-radius: 50%; background: #22c55e;"></span></div></div>';
    }
    $html .= '<pre style="background: #0f172a; color: #e2e8f0; padding: 1.25rem; margin: 0; overflow-x: auto; font-family: monospace; font-size: 0.875rem;"><code>' . esc($content) . '</code></pre></div>';
    return $html;
}, ['has_content' => true]);


register_tag('quote', function($attrs, $content) {
    $author = $attrs['author'] ?? '';
    $source = $attrs['source'] ?? '';
    $style = $attrs['style'] ?? 'classic';
    
    if ($style === 'modern') {
        $html = '<figure style="margin: 2rem 0; padding: 2rem; background: linear-gradient(135deg, #f8fafc, #f1f5f9); border-radius: 16px; position: relative;">';
        $html .= '<div style="position: absolute; top: 1rem; left: 1.5rem; font-size: 4rem; color: #6366f1; opacity: 0.2; font-family: Georgia;">"</div>';
        $html .= '<blockquote style="margin: 0; padding-left: 1rem; font-size: 1.25rem; font-style: italic; color: #334155;">' . $content . '</blockquote>';
        if ($author) {
            $html .= '<figcaption style="margin-top: 1.5rem; display: flex; align-items: center; gap: 1rem;">';
            $html .= '<div style="width: 48px; height: 48px; background: linear-gradient(135deg, #6366f1, #8b5cf6); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 600;">' . strtoupper(substr($author, 0, 1)) . '</div>';
            $html .= '<div><div style="font-weight: 600; color: #1e293b;">' . esc($author) . '</div>';
            if ($source) $html .= '<div style="font-size: 0.875rem; color: #64748b;">' . esc($source) . '</div>';
            $html .= '</div></figcaption>';
        }
        return $html . '</figure>';
    }
    
    $html = '<blockquote style="border-left: 4px solid #6366f1; padding: 1rem 1.5rem; margin: 1.5rem 0; background: #f8fafc; border-radius: 0 8px 8px 0; font-style: italic;">';
    $html .= '<p style="margin: 0; font-size: 1.125rem; color: #334155;">' . $content . '</p>';
    if ($author) {
        $html .= '<footer style="margin-top: 0.75rem; font-style: normal; font-size: 0.875rem; color: #64748b;">— <strong>' . esc($author) . '</strong>';
        if ($source) $html .= ', <cite>' . esc($source) . '</cite>';
        $html .= '</footer>';
    }
    return $html . '</blockquote>';
}, ['has_content' => true]);


register_tag('lead', function($a, $c) {
    return '<p style="font-size: 1.25rem; line-height: 1.8; color: #475569; margin-bottom: 1.5rem;">' . $c . '</p>';
}, ['has_content' => true]);


register_tag('highlight', function($attrs, $content) {
    $colors = [
        'yellow' => '#fef08a',
        'green' => '#bbf7d0',
        'blue' => '#bfdbfe',
        'pink' => '#fbcfe8',
    ];
    $bg = $colors[$attrs['color'] ?? 'yellow'] ?? $colors['yellow'];
    return '<mark style="background: linear-gradient(to bottom, transparent 50%, ' . $bg . ' 50%); padding: 0 0.25rem;">' . $content . '</mark>';
}, ['has_content' => true]);


register_tag('badge', function($attrs, $content) {
    $color = $attrs['color'] ?? 'blue';
    $pill = ($attrs['pill'] ?? '') === 'true';
    
    $colors = [
        'blue' => ['bg' => '#dbeafe', 'text' => '#1e40af'],
        'green' => ['bg' => '#dcfce7', 'text' => '#166534'],
        'red' => ['bg' => '#fee2e2', 'text' => '#991b1b'],
        'yellow' => ['bg' => '#fef3c7', 'text' => '#92400e'],
        'purple' => ['bg' => '#f3e8ff', 'text' => '#7c3aed'],
        'gray' => ['bg' => '#f1f5f9', 'text' => '#475569'],
        'gradient' => ['bg' => 'linear-gradient(135deg, #6366f1, #8b5cf6)', 'text' => '#fff'],
    ];
    
    $c = $colors[$color] ?? $colors['blue'];
    $bgStyle = strpos($c['bg'], 'gradient') !== false ? $c['bg'] : 'background: ' . $c['bg'];
    
    return '<span style="display: inline-block; padding: 0.25rem 0.75rem; font-size: 0.75rem; font-weight: 600; border-radius: ' . ($pill ? '9999px' : '6px') . '; ' . $bgStyle . '; color: ' . $c['text'] . ';">' . $content . '</span>';
}, ['has_content' => true]);


register_tag('divider', function($attrs) {
    $style = $attrs['style'] ?? 'solid';
    $spacing = $attrs['spacing'] ?? '2rem';
    
    if ($style === 'gradient') {
        return '<hr style="border: none; height: 2px; background: linear-gradient(to right, transparent, #6366f1, #8b5cf6, transparent); margin: ' . esc($spacing) . ' 0;">';
    }
    return '<hr style="border: none; border-top: 1px ' . esc($style) . ' #e2e8f0; margin: ' . esc($spacing) . ' 0;">';
}, ['has_content' => false]);


register_tag('icon', function($attrs) {
    return getForgeIcon($attrs['name'] ?? 'star', $attrs['size'] ?? '20', $attrs['color'] ?? 'currentColor');
}, ['has_content' => false]);


register_tag('tooltip', function($attrs, $content) {
    return '<span style="cursor: help; border-bottom: 1px dashed #6366f1;" title="' . esc($attrs['text'] ?? '') . '">' . $content . '</span>';
}, ['has_content' => true]);


register_tag('youtube', function($attrs) {
    $id = $attrs['id'] ?? '';
    if (empty($id) && !empty($attrs['url'])) {
        preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $attrs['url'], $m);
        $id = $m[1] ?? '';
    }
    if (empty($id)) return '<!-- YouTube: Invalid ID -->';
    return '<div style="position: relative; padding-bottom: 56.25%; height: 0; margin: 1.5rem 0; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.2);"><iframe src="https://www.youtube.com/embed/' . esc($id) . '" style="position: absolute; inset: 0; width: 100%; height: 100%; border: 0;" allowfullscreen></iframe></div>';
}, ['has_content' => false]);


register_tag('spacer', function($attrs) {
    return '<div style="height: ' . esc($attrs['height'] ?? '2rem') . ';"></div>';
}, ['has_content' => false]);


register_tag('year', function() {
    return date('Y');
}, ['has_content' => false]);


register_tag('sitename', function() {
    return esc(getOption('site_name', 'Forge CMS'));
}, ['has_content' => false]);
