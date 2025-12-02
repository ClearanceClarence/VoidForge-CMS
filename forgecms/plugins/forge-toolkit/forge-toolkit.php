<?php
/**
 * Plugin Name: Forge Toolkit
 * Description: Essential content tags for Forge CMS - buttons, alerts, columns, accordions, tabs, and more
 * Version: 1.0.0
 * Author: Forge CMS
 */

defined('CMS_ROOT') or die('Direct access not allowed');

// =========================================================================
// Plugin Activation - Create Demo Page
// =========================================================================
add_action('plugin_activate_forge-toolkit', function() {
    // Check if demo page already exists
    $existingPage = Post::findBySlug('toolkit-demo', 'page');
    if ($existingPage) {
        return;
    }
    
    // Create demo page content
    $content = <<<HTML
<p>This page demonstrates all the content tags available in the <strong>Forge Toolkit</strong> plugin. Use these tags in your posts and pages to create rich, interactive content.</p>

<h2>Buttons</h2>
<p>Create styled buttons with different styles:</p>

{button href="#" style="primary"}Primary Button{/button} {button href="#" style="secondary"}Secondary{/button} {button href="#" style="success"}Success{/button} {button href="#" style="danger"}Danger{/button} {button href="#" style="outline"}Outline{/button}

<p><strong>Usage:</strong> <code>{button href="/contact" style="primary"}Click Me{/button}</code></p>

{divider}

<h2>Alerts</h2>
<p>Display important messages with different alert types:</p>

{alert type="info" title="Information"}This is an informational alert. Use it to highlight helpful tips or notes.{/alert}

{alert type="success" title="Success"}Great job! Your action was completed successfully.{/alert}

{alert type="warning" title="Warning"}Be careful! This action might have consequences.{/alert}

{alert type="danger" title="Error"}Something went wrong. Please try again.{/alert}

<p><strong>Usage:</strong> <code>{alert type="info" title="Note"}Your message here{/alert}</code></p>

{divider}

<h2>Columns</h2>
<p>Create responsive column layouts:</p>

{columns gap="2rem"}
{col}
<h3>Column One</h3>
<p>This is the first column. Content here will take up equal space with other columns.</p>
{/col}
{col}
<h3>Column Two</h3>
<p>This is the second column. Great for side-by-side comparisons or features.</p>
{/col}
{col}
<h3>Column Three</h3>
<p>This is the third column. The layout automatically adjusts based on the number of columns.</p>
{/col}
{/columns}

<p><strong>Usage:</strong> <code>{columns}{col}First{/col}{col}Second{/col}{/columns}</code></p>

{divider}

<h2>Code Blocks</h2>
<p>Display code with proper formatting:</p>

{code lang="php"}<?php
// Example PHP code
function greet(\$name) {
    return "Hello, " . \$name . "!";
}

echo greet("World");{/code}

<p><strong>Usage:</strong> <code>{code lang="php"}your code here{/code}</code></p>

{divider}

<h2>Blockquotes</h2>
<p>Beautiful quotes with attribution:</p>

{quote author="Steve Jobs" source="Stanford Commencement, 2005"}Your time is limited, don't waste it living someone else's life. Don't be trapped by dogma, which is living the result of other people's thinking.{/quote}

<p><strong>Usage:</strong> <code>{quote author="Name" source="Source"}Quote text{/quote}</code></p>

{divider}

<h2>Cards</h2>
<p>Content cards for featured items:</p>

{columns gap="1.5rem"}
{col}
{card title="Getting Started"}Learn the basics of Forge CMS and how to create your first page.{/card}
{/col}
{col}
{card title="Customization"}Discover how to customize your site with themes and plugins.{/card}
{/col}
{/columns}

<p><strong>Usage:</strong> <code>{card title="Title" image="/path.jpg"}Content{/card}</code></p>

{divider}

<h2>Badges</h2>
<p>Colored labels for status or categories:</p>

<p>{badge color="blue"}Featured{/badge} {badge color="green"}New{/badge} {badge color="red"}Hot{/badge} {badge color="yellow"}Sale{/badge} {badge color="purple"}Premium{/badge} {badge color="gray"}Draft{/badge}</p>

<p><strong>Usage:</strong> <code>{badge color="green"}New{/badge}</code></p>

{divider}

<h2>Icons</h2>
<p>Inline SVG icons:</p>

<p>{icon name="star" color="#f59e0b"} {icon name="heart" color="#ef4444"} {icon name="check" color="#10b981"} {icon name="mail" color="#6366f1"} {icon name="phone" color="#6366f1"} {icon name="location" color="#6366f1"}</p>

<p><strong>Usage:</strong> <code>{icon name="star" size="24" color="#f59e0b"}</code></p>

{divider}

<h2>YouTube Embed</h2>
<p>Embed YouTube videos responsively:</p>

<p><strong>Usage:</strong> <code>{youtube id="VIDEO_ID"}</code> or <code>{youtube url="https://youtube.com/watch?v=..."}</code></p>

{divider}

<h2>Utility Tags</h2>

<p><strong>Current Year:</strong> Copyright © {year} - automatically updates!</p>
<p><strong>Site Name:</strong> Welcome to {sitename}</p>
<p><strong>Spacer:</strong> Add vertical space with <code>{spacer height="2rem"}</code></p>
<p><strong>Divider:</strong> Add horizontal lines with <code>{divider}</code></p>

{spacer height="2rem"}

{alert type="success"}You're all set! Start using these tags in your content to create beautiful, engaging pages.{/alert}
HTML;

    // Create the demo page
    Post::create([
        'post_type' => 'page',
        'title' => 'Toolkit Demo',
        'slug' => 'toolkit-demo',
        'content' => $content,
        'status' => 'published',
        'author_id' => 1,
    ]);
});

// Deactivation - optionally remove demo page
add_action('plugin_deactivate_forge-toolkit', function() {
    // Optionally trash the demo page
    $page = Post::findBySlug('toolkit-demo', 'page');
    if ($page) {
        Post::update($page['id'], ['status' => 'trash']);
    }
});

// =========================================================================
// Content Tags
// =========================================================================

/**
 * Button Tag
 * Usage: {button href="/contact" style="primary"}Contact Us{/button}
 * Styles: primary, secondary, success, danger, outline
 */
register_tag('button', function($attrs, $content) {
    $href = $attrs['href'] ?? '#';
    $style = $attrs['style'] ?? 'primary';
    $target = $attrs['target'] ?? '';
    $class = $attrs['class'] ?? '';
    
    $styles = [
        'primary' => 'background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff;',
        'secondary' => 'background: #f1f5f9; color: #475569;',
        'success' => 'background: #10b981; color: #fff;',
        'danger' => 'background: #ef4444; color: #fff;',
        'outline' => 'background: transparent; border: 2px solid #6366f1; color: #6366f1;',
    ];
    
    $btnStyle = $styles[$style] ?? $styles['primary'];
    $targetAttr = $target ? ' target="' . esc($target) . '"' : '';
    
    return '<a href="' . esc($href) . '"' . $targetAttr . ' class="forge-btn ' . esc($class) . '" style="display: inline-block; padding: 0.75rem 1.5rem; border-radius: 8px; text-decoration: none; font-weight: 600; transition: all 0.2s; ' . $btnStyle . '">' . $content . '</a>';
}, ['has_content' => true, 'description' => 'Styled button link']);


/**
 * Alert Tag
 * Usage: {alert type="info"}This is an alert message{/alert}
 * Types: info, success, warning, danger
 */
register_tag('alert', function($attrs, $content) {
    $type = $attrs['type'] ?? 'info';
    $title = $attrs['title'] ?? '';
    
    $styles = [
        'info' => ['bg' => '#eff6ff', 'border' => '#3b82f6', 'text' => '#1e40af', 'icon' => 'ℹ'],
        'success' => ['bg' => '#f0fdf4', 'border' => '#22c55e', 'text' => '#166534', 'icon' => '✓'],
        'warning' => ['bg' => '#fffbeb', 'border' => '#f59e0b', 'text' => '#92400e', 'icon' => '⚠'],
        'danger' => ['bg' => '#fef2f2', 'border' => '#ef4444', 'text' => '#991b1b', 'icon' => '✕'],
    ];
    
    $s = $styles[$type] ?? $styles['info'];
    
    $html = '<div style="padding: 1rem 1.25rem; border-radius: 8px; border-left: 4px solid ' . $s['border'] . '; background: ' . $s['bg'] . '; color: ' . $s['text'] . '; margin: 1rem 0;">';
    if ($title) {
        $html .= '<strong style="display: block; margin-bottom: 0.25rem;">' . $s['icon'] . ' ' . esc($title) . '</strong>';
    }
    $html .= $content;
    $html .= '</div>';
    
    return $html;
}, ['has_content' => true, 'description' => 'Alert box with different types']);


/**
 * Columns Tag
 * Usage: {columns gap="2rem"}{col}First{/col}{col}Second{/col}{/columns}
 */
register_tag('columns', function($attrs, $content) {
    $gap = $attrs['gap'] ?? '1.5rem';
    $cols = $attrs['cols'] ?? 'auto';
    
    // Count {col} tags to determine columns
    preg_match_all('/\{col\}/', $content, $matches);
    $colCount = count($matches[0]) ?: 2;
    
    $gridCols = $cols === 'auto' ? "repeat($colCount, 1fr)" : $cols;
    
    // Process inner {col} tags
    $content = preg_replace('/\{col\}/', '<div style="min-width: 0;">', $content);
    $content = preg_replace('/\{\/col\}/', '</div>', $content);
    
    return '<div style="display: grid; grid-template-columns: ' . $gridCols . '; gap: ' . esc($gap) . '; margin: 1rem 0;">' . $content . '</div>';
}, ['has_content' => true, 'description' => 'Responsive column layout']);


/**
 * Highlight/Code Tag
 * Usage: {code}console.log('hello');{/code}
 * Or: {code lang="php"}<?php echo "hi"; ?>{/code}
 */
register_tag('code', function($attrs, $content) {
    $lang = $attrs['lang'] ?? '';
    
    return '<pre style="background: #1e293b; color: #e2e8f0; padding: 1rem 1.25rem; border-radius: 8px; overflow-x: auto; margin: 1rem 0; font-family: monospace; font-size: 0.875rem;">' . 
           ($lang ? '<code data-lang="' . esc($lang) . '">' : '<code>') . 
           esc($content) . 
           '</code></pre>';
}, ['has_content' => true, 'description' => 'Code block with syntax highlighting']);


/**
 * Quote/Blockquote Tag
 * Usage: {quote author="John Doe" source="Book Title"}Quote text here{/quote}
 */
register_tag('quote', function($attrs, $content) {
    $author = $attrs['author'] ?? '';
    $source = $attrs['source'] ?? '';
    
    $html = '<blockquote style="border-left: 4px solid #6366f1; padding: 1rem 1.5rem; margin: 1.5rem 0; background: #f8fafc; border-radius: 0 8px 8px 0; font-style: italic;">';
    $html .= '<p style="margin: 0; font-size: 1.125rem; line-height: 1.7;">' . $content . '</p>';
    
    if ($author || $source) {
        $html .= '<footer style="margin-top: 0.75rem; font-style: normal; font-size: 0.875rem; color: #64748b;">';
        $html .= '— ';
        if ($author) $html .= '<strong>' . esc($author) . '</strong>';
        if ($author && $source) $html .= ', ';
        if ($source) $html .= '<cite>' . esc($source) . '</cite>';
        $html .= '</footer>';
    }
    
    $html .= '</blockquote>';
    return $html;
}, ['has_content' => true, 'description' => 'Styled blockquote with attribution']);


/**
 * Card Tag
 * Usage: {card title="Card Title" image="/path/to/image.jpg"}Card content{/card}
 */
register_tag('card', function($attrs, $content) {
    $title = $attrs['title'] ?? '';
    $image = $attrs['image'] ?? '';
    $link = $attrs['link'] ?? '';
    
    $html = '<div style="background: #fff; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); overflow: hidden; margin: 1rem 0;">';
    
    if ($image) {
        $html .= '<img src="' . esc($image) . '" alt="" style="width: 100%; height: 200px; object-fit: cover;">';
    }
    
    $html .= '<div style="padding: 1.25rem;">';
    
    if ($title) {
        if ($link) {
            $html .= '<h3 style="margin: 0 0 0.75rem; font-size: 1.25rem;"><a href="' . esc($link) . '" style="color: inherit; text-decoration: none;">' . esc($title) . '</a></h3>';
        } else {
            $html .= '<h3 style="margin: 0 0 0.75rem; font-size: 1.25rem;">' . esc($title) . '</h3>';
        }
    }
    
    $html .= '<div style="color: #64748b; line-height: 1.6;">' . $content . '</div>';
    $html .= '</div></div>';
    
    return $html;
}, ['has_content' => true, 'description' => 'Content card with optional image']);


/**
 * Divider Tag
 * Usage: {divider} or {divider style="dashed" color="#ccc"}
 */
register_tag('divider', function($attrs) {
    $style = $attrs['style'] ?? 'solid';
    $color = $attrs['color'] ?? '#e2e8f0';
    $spacing = $attrs['spacing'] ?? '2rem';
    
    return '<hr style="border: none; border-top: 1px ' . esc($style) . ' ' . esc($color) . '; margin: ' . esc($spacing) . ' 0;">';
}, ['has_content' => false, 'description' => 'Horizontal divider line']);


/**
 * Icon Tag
 * Usage: {icon name="star" size="24" color="#f59e0b"}
 * Available icons: star, heart, check, x, arrow-right, mail, phone, location
 */
register_tag('icon', function($attrs) {
    $name = $attrs['name'] ?? 'star';
    $size = $attrs['size'] ?? '20';
    $color = $attrs['color'] ?? 'currentColor';
    
    $icons = [
        'star' => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>',
        'heart' => '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>',
        'check' => '<polyline points="20 6 9 17 4 12"></polyline>',
        'x' => '<line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line>',
        'arrow-right' => '<line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline>',
        'mail' => '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline>',
        'phone' => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>',
        'location' => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle>',
    ];
    
    $path = $icons[$name] ?? $icons['star'];
    
    return '<svg width="' . esc($size) . '" height="' . esc($size) . '" viewBox="0 0 24 24" fill="none" stroke="' . esc($color) . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: inline-block; vertical-align: middle;">' . $path . '</svg>';
}, ['has_content' => false, 'description' => 'SVG icon']);


/**
 * Badge Tag
 * Usage: {badge color="green"}New{/badge}
 */
register_tag('badge', function($attrs, $content) {
    $color = $attrs['color'] ?? 'blue';
    
    $colors = [
        'blue' => ['bg' => '#dbeafe', 'text' => '#1e40af'],
        'green' => ['bg' => '#dcfce7', 'text' => '#166534'],
        'red' => ['bg' => '#fee2e2', 'text' => '#991b1b'],
        'yellow' => ['bg' => '#fef3c7', 'text' => '#92400e'],
        'purple' => ['bg' => '#f3e8ff', 'text' => '#7c3aed'],
        'gray' => ['bg' => '#f1f5f9', 'text' => '#475569'],
    ];
    
    $c = $colors[$color] ?? $colors['blue'];
    
    return '<span style="display: inline-block; padding: 0.25rem 0.75rem; font-size: 0.75rem; font-weight: 600; border-radius: 9999px; background: ' . $c['bg'] . '; color: ' . $c['text'] . ';">' . $content . '</span>';
}, ['has_content' => true, 'description' => 'Colored badge/label']);


/**
 * YouTube Embed Tag
 * Usage: {youtube id="dQw4w9WgXcQ"} or {youtube url="https://youtube.com/watch?v=..."}
 */
register_tag('youtube', function($attrs) {
    $id = $attrs['id'] ?? '';
    
    // Extract ID from URL if provided
    if (empty($id) && !empty($attrs['url'])) {
        preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $attrs['url'], $matches);
        $id = $matches[1] ?? '';
    }
    
    if (empty($id)) {
        return '<!-- YouTube: Invalid video ID -->';
    }
    
    return '<div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; margin: 1rem 0; border-radius: 12px;">
        <iframe src="https://www.youtube.com/embed/' . esc($id) . '" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0;" allowfullscreen></iframe>
    </div>';
}, ['has_content' => false, 'description' => 'Embed YouTube video']);


/**
 * Spacer Tag
 * Usage: {spacer height="2rem"}
 */
register_tag('spacer', function($attrs) {
    $height = $attrs['height'] ?? '2rem';
    return '<div style="height: ' . esc($height) . ';"></div>';
}, ['has_content' => false, 'description' => 'Vertical spacing']);


/**
 * Current Year Tag
 * Usage: Copyright © {year} My Company
 */
register_tag('year', function() {
    return date('Y');
}, ['has_content' => false, 'description' => 'Current year']);


/**
 * Site Name Tag
 * Usage: Welcome to {sitename}
 */
register_tag('sitename', function() {
    return esc(getOption('site_name', 'Forge CMS'));
}, ['has_content' => false, 'description' => 'Site name from settings']);
