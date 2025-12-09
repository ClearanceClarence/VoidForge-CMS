<?php
/**
 * Default Theme Functions
 * VoidForge CMS
 */

defined('CMS_ROOT') or die;

// Theme setup
add_action('init', function() {
    // Theme is ready
});

/**
 * Get site title
 */
function default_site_title(): string
{
    return getOption('site_title', 'VoidForge');
}

/**
 * Format date
 */
function default_date(string $date): string
{
    $format = getOption('date_format', 'F j, Y');
    return date($format, strtotime($date));
}

/**
 * Get excerpt from content
 */
function default_excerpt(string $content, int $length = 160): string
{
    $text = strip_tags($content);
    $text = preg_replace('/\s+/', ' ', $text);
    
    if (strlen($text) > $length) {
        $text = substr($text, 0, $length);
        $text = substr($text, 0, strrpos($text, ' '));
        $text .= '...';
    }
    
    return $text;
}
