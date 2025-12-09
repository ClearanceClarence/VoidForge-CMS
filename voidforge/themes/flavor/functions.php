<?php
/**
 * Flavor Theme Functions
 * Default theme for VoidForge CMS
 */

defined('CMS_ROOT') or die;

// Theme setup
add_action('init', function() {
    // Enqueue theme styles
    enqueue_theme_style('flavor-style', 'style.css', [], '1.0.0');
});

/**
 * Get site title with optional tagline
 */
function flavor_site_title(bool $withTagline = false): string
{
    $title = getOption('site_title', 'VoidForge');
    if ($withTagline) {
        $tagline = getOption('site_tagline', '');
        if ($tagline) {
            $title .= ' — ' . $tagline;
        }
    }
    return $title;
}

/**
 * Format post date
 */
function flavor_date(string $date): string
{
    $format = getOption('date_format', 'F j, Y');
    return date($format, strtotime($date));
}

/**
 * Get excerpt from content
 */
function flavor_excerpt(string $content, int $length = 160): string
{
    $text = strip_tags($content);
    $text = preg_replace('/\s+/', ' ', $text);
    
    if (strlen($text) > $length) {
        $text = substr($text, 0, $length);
        $text = substr($text, 0, strrpos($text, ' '));
        $text .= '…';
    }
    
    return $text;
}

/**
 * Get reading time estimate
 */
function flavor_reading_time(string $content): string
{
    $words = str_word_count(strip_tags($content));
    $minutes = ceil($words / 200);
    return $minutes . ' min read';
}

/**
 * Check if we're on the homepage
 */
function flavor_is_home(): bool
{
    $uri = trim($_SERVER['REQUEST_URI'] ?? '', '/');
    $uri = strtok($uri, '?');
    return empty($uri) || $uri === 'index.php';
}

/**
 * Get navigation menu
 */
function flavor_nav_menu(): array
{
    // Get published pages for navigation
    $pages = Post::query([
        'type' => 'page',
        'status' => 'published',
        'limit' => 10,
        'orderby' => 'title',
        'order' => 'ASC'
    ]);
    
    $menu = [
        ['label' => 'Home', 'url' => SITE_URL, 'active' => flavor_is_home()]
    ];
    
    foreach ($pages as $page) {
        $url = SITE_URL . '/' . ($page['slug'] ?? sanitizeSlug($page['title']));
        $menu[] = [
            'label' => $page['title'],
            'url' => $url,
            'active' => false
        ];
    }
    
    return $menu;
}
