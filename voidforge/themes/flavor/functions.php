<?php
/**
 * Flavor Theme Functions
 * 
 * @package Flavor
 * @version 1.0.0
 */

defined('CMS_ROOT') or die('Direct access not allowed');

/**
 * Get theme settings
 */
function flavor_get_settings(): array
{
    return getOption('theme_settings_flavor', []);
}

/**
 * Enqueue Google Fonts
 */
Plugin::addAction('vf_head', function() {
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
    echo '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&family=Merriweather:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">';
}, 5);

/**
 * Add custom CSS variables from theme settings
 */
Plugin::addAction('vf_head', function() {
    $settings = flavor_get_settings();
    $accentColor = $settings['accent_color'] ?? '#6366f1';
    $contentWidth = $settings['content_width'] ?? 'default';
    
    $widths = [
        'narrow' => '680px',
        'default' => '780px',
        'wide' => '920px'
    ];
    
    $width = $widths[$contentWidth] ?? '780px';
    
    echo '<style>
        :root {
            --color-accent: ' . esc($accentColor) . ';
            --color-accent-hover: ' . flavor_adjust_brightness($accentColor, -15) . ';
            --color-accent-light: ' . flavor_hex_to_rgba($accentColor, 0.1) . ';
            --content-width: ' . $width . ';
        }
    </style>';
}, 20);

/**
 * Adjust color brightness
 */
function flavor_adjust_brightness(string $hex, int $percent): string
{
    $hex = ltrim($hex, '#');
    
    if (strlen($hex) === 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    $r = max(0, min(255, $r + ($r * $percent / 100)));
    $g = max(0, min(255, $g + ($g * $percent / 100)));
    $b = max(0, min(255, $b + ($b * $percent / 100)));
    
    return sprintf('#%02x%02x%02x', $r, $g, $b);
}

/**
 * Convert hex to rgba
 */
function flavor_hex_to_rgba(string $hex, float $alpha): string
{
    $hex = ltrim($hex, '#');
    
    if (strlen($hex) === 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    return "rgba($r, $g, $b, $alpha)";
}

/**
 * Get excerpt with custom length
 */
function flavor_excerpt(array $post, int $length = 160): string
{
    $content = $post['content'] ?? '';
    
    // Strip blocks JSON if present
    if (str_starts_with($content, '[{')) {
        $blocks = Anvil::parseBlocks($content);
        $content = Anvil::renderBlocks($blocks);
    }
    
    // Strip tags and get plain text
    $content = strip_tags($content);
    $content = preg_replace('/\s+/', ' ', $content);
    $content = trim($content);
    
    if (strlen($content) <= $length) {
        return $content;
    }
    
    return substr($content, 0, $length) . '…';
}

/**
 * Get reading time
 */
function flavor_reading_time(array $post): int
{
    $content = $post['content'] ?? '';
    
    if (str_starts_with($content, '[{')) {
        $blocks = Anvil::parseBlocks($content);
        $content = Anvil::renderBlocks($blocks);
    }
    
    $wordCount = str_word_count(strip_tags($content));
    $minutes = max(1, ceil($wordCount / 200));
    
    return $minutes;
}

/**
 * Format date
 */
function flavor_date(string $date): string
{
    return date('M j, Y', strtotime($date));
}

/**
 * Check if we should show author
 */
function flavor_show_author(): bool
{
    $settings = flavor_get_settings();
    return ($settings['show_author'] ?? true) !== false;
}

/**
 * Check if we should show date
 */
function flavor_show_date(): bool
{
    $settings = flavor_get_settings();
    return ($settings['show_date'] ?? true) !== false;
}

/**
 * Render a single comment with replies
 */
function flavor_render_comment(array $comment, int $depth = 0): void
{
    $maxDepth = (int) getOption('comment_max_depth', 3);
    $authorName = Comment::getAuthorName($comment);
    $gravatar = Comment::getGravatar($comment, 48);
    $marginLeft = $depth * 40;
    ?>
    <div class="comment" id="comment-<?php echo $comment['id']; ?>" 
         style="margin-left: <?php echo $marginLeft; ?>px; margin-bottom: 24px; padding: 20px; background: var(--color-bg-alt); border-radius: var(--radius-lg);">
        <div style="display: flex; gap: 16px;">
            <img src="<?php echo esc($gravatar); ?>" alt="<?php echo esc($authorName); ?>" 
                 style="width: 48px; height: 48px; border-radius: 50%; flex-shrink: 0;">
            <div style="flex: 1; min-width: 0;">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                    <strong style="font-weight: 600;"><?php echo esc($authorName); ?></strong>
                    <span style="font-size: 0.8125rem; color: var(--color-text-light);">
                        <?php echo flavor_date($comment['created_at']); ?>
                    </span>
                </div>
                <div style="line-height: 1.6; color: var(--color-text);">
                    <?php echo $comment['content']; ?>
                </div>
                <?php if ($depth < $maxDepth): ?>
                <div style="margin-top: 12px;">
                    <a href="#respond" onclick="replyTo(<?php echo $comment['id']; ?>, '<?php echo esc(addslashes($authorName)); ?>')" 
                       style="font-size: 0.8125rem; font-weight: 500; color: var(--color-accent); text-decoration: none;">
                        Reply
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
    // Render replies recursively
    if (!empty($comment['replies'])) {
        foreach ($comment['replies'] as $reply) {
            flavor_render_comment($reply, $depth + 1);
        }
    }
}
