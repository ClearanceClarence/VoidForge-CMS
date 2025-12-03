<?php
/**
 * Core Helper Functions
 */

defined('CMS_ROOT') or die('Direct access not allowed');

/**
 * Escape HTML output
 */
function esc(string $string): string
{
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Trim text to a specified number of words
 */
function wp_trim_words(string $text, int $numWords = 55, string $more = '...'): string
{
    $text = strip_tags($text);
    $words = preg_split('/\s+/', $text, $numWords + 1);
    
    if (count($words) > $numWords) {
        array_pop($words);
        $text = implode(' ', $words) . $more;
    } else {
        $text = implode(' ', $words);
    }
    
    return $text;
}

/**
 * Generate a URL-friendly slug
 */
function slugify(string $text): string
{
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    
    return $text ?: 'untitled';
}

/**
 * Ensure slug is unique for a post type
 */
function uniqueSlug(string $slug, string $postType, $excludeId = null): string
{
    $originalSlug = $slug;
    $counter = 1;
    $table = Database::table('posts');
    
    while (true) {
        $sql = "SELECT id FROM {$table} WHERE slug = ? AND post_type = ?";
        $params = [$slug, $postType];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $existing = Database::queryOne($sql, $params);
        
        if (!$existing) {
            break;
        }
        
        $slug = $originalSlug . '-' . $counter;
        $counter++;
    }
    
    return $slug;
}

/**
 * Redirect to a URL
 */
function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

/**
 * Get current URL
 */
function currentUrl(): string
{
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Flash messages
 */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'][$type] = $message;
}

function getFlash(?string $type = null): mixed
{
    if ($type !== null) {
        $message = $_SESSION['flash'][$type] ?? null;
        unset($_SESSION['flash'][$type]);
        return $message;
    }
    
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

/**
 * Get Gravatar URL for email
 */
function getGravatarUrl(string $email, int $size = 80): string
{
    $hash = md5(strtolower(trim($email)));
    return "https://www.gravatar.com/avatar/{$hash}?s={$size}&d=mp";
}

/**
 * CSRF token handling
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . csrfToken() . '">';
}

function verifyCsrf(?string $token = null): bool
{
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? $_GET['csrf'] ?? '';
    }
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

/**
 * Format date for display
 */
function formatDate(string $date, string $format = 'M j, Y'): string
{
    return date($format, strtotime($date));
}

/**
 * Format date for datetime input
 */
function formatDatetime(string $date): string
{
    return date('Y-m-d\TH:i', strtotime($date));
}

/**
 * Truncate text
 */
function truncate(string $text, int $length = 150, string $suffix = '...'): string
{
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    
    return mb_substr($text, 0, $length) . $suffix;
}

/**
 * Get option from database
 * @return mixed
 */
function getOption(string $name, $default = null)
{
    $table = Database::table('options');
    $option = Database::queryOne(
        "SELECT option_value FROM {$table} WHERE option_name = ?",
        [$name]
    );
    
    if ($option) {
        $value = $option['option_value'];
        $decoded = json_decode($value, true);
        return $decoded !== null ? $decoded : $value;
    }
    
    return $default;
}

/**
 * Set option in database
 */
function setOption(string $name, $value): void
{
    if (is_array($value) || is_object($value)) {
        $value = json_encode($value);
    }
    
    $table = Database::table('options');
    $existing = Database::queryOne(
        "SELECT id FROM {$table} WHERE option_name = ?",
        [$name]
    );
    
    if ($existing) {
        Database::update(Database::table('options'), ['option_value' => $value], 'option_name = ?', [$name]);
    } else {
        Database::insert(Database::table('options'), [
            'option_name' => $name,
            'option_value' => $value
        ]);
    }
}

/**
 * Pagination helper
 */
function paginate(int $total, int $perPage, int $currentPage): array
{
    $totalPages = (int) ceil($total / $perPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;
    
    return [
        'total' => $total,
        'per_page' => $perPage,
        'current_page' => $currentPage,
        'total_pages' => $totalPages,
        'offset' => $offset,
        'has_previous' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages,
    ];
}

/**
 * Human readable file size
 */
if (!function_exists('formatFileSize')) {
    function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}

/**
 * Get file extension
 */
function getExtension(string $filename): string
{
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

/**
 * Check if file is an image
 */
function isImage(string $mimeType): bool
{
    return str_starts_with($mimeType, 'image/');
}

/**
 * Output content with tags processed
 * Use in themes: <?= content($post['content']) ?>
 */
function content(string $content): string
{
    // Process plugin tags
    $content = process_tags($content);
    
    // Apply content filters
    return apply_filters('the_content', $content);
}

/**
 * Sanitize filename
 */
function sanitizeFilename(string $filename): string
{
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '-', $filename);
    $filename = preg_replace('/-+/', '-', $filename);
    return trim($filename, '-');
}
