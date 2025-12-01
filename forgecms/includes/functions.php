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
function uniqueSlug(string $slug, string $postType, ?int $excludeId = null): string
{
    $originalSlug = $slug;
    $counter = 1;
    
    while (true) {
        $sql = "SELECT id FROM posts WHERE slug = ? AND post_type = ?";
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
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
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

function verifyCsrf(): bool
{
    $token = $_POST['csrf_token'] ?? '';
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
 */
function getOption(string $name, $default = null)
{
    $option = Database::queryOne(
        "SELECT option_value FROM options WHERE option_name = ?",
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
    
    $existing = Database::queryOne(
        "SELECT id FROM options WHERE option_name = ?",
        [$name]
    );
    
    if ($existing) {
        Database::update('options', ['option_value' => $value], 'option_name = ?', [$name]);
    } else {
        Database::insert('options', [
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
 * Sanitize filename
 */
function sanitizeFilename(string $filename): string
{
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '-', $filename);
    $filename = preg_replace('/-+/', '-', $filename);
    return trim($filename, '-');
}
