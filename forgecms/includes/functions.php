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
function formatDate(?string $date, string $format = 'M j, Y'): string
{
    if (empty($date)) {
        return '';
    }
    return date($format, strtotime($date));
}

/**
 * Format date for datetime input
 */
function formatDatetime(?string $date): string
{
    if (empty($date)) {
        return '';
    }
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

// =========================================================================
// Admin Menu System
// =========================================================================

/**
 * Global admin menu storage
 */
global $adminMenu;
$adminMenu = [];

/**
 * Register a top-level admin menu item
 * 
 * @param string $id Unique identifier
 * @param array $args Menu arguments: label, icon, url, capability, position
 */
function registerAdminMenu(string $id, array $args): void
{
    global $adminMenu;
    
    $defaults = [
        'id' => $id,
        'label' => $id,
        'icon' => 'file',
        'url' => ADMIN_URL . '/',
        'capability' => 'editor',
        'position' => 50,
        'submenu' => [],
        'badge' => null,
    ];
    
    $adminMenu[$id] = array_merge($defaults, $args);
}

/**
 * Register a submenu item under a parent menu
 * 
 * @param string $parentId Parent menu ID
 * @param string $id Unique identifier for submenu
 * @param array $args Submenu arguments: label, url, capability
 */
function registerAdminSubmenu(string $parentId, string $id, array $args): void
{
    global $adminMenu;
    
    $defaults = [
        'id' => $id,
        'label' => $id,
        'url' => ADMIN_URL . '/',
        'capability' => 'editor',
    ];
    
    if (!isset($adminMenu[$parentId])) {
        return;
    }
    
    $adminMenu[$parentId]['submenu'][$id] = array_merge($defaults, $args);
}

/**
 * Get all registered admin menus sorted by position
 */
function getAdminMenus(): array
{
    global $adminMenu;
    
    $menus = $adminMenu;
    uasort($menus, function($a, $b) {
        return ($a['position'] ?? 50) <=> ($b['position'] ?? 50);
    });
    
    return $menus;
}

/**
 * Check if current user has capability for menu item
 */
function userCanAccessMenu(array $menu): bool
{
    $capability = $menu['capability'] ?? 'editor';
    
    switch ($capability) {
        case 'admin':
            return User::isAdmin();
        case 'editor':
            return User::hasRole('editor') || User::isAdmin();
        case 'author':
            return User::hasRole('author') || User::hasRole('editor') || User::isAdmin();
        default:
            return true;
    }
}

/**
 * Initialize default admin menus
 */
function initDefaultAdminMenus(): void
{
    // Dashboard
    registerAdminMenu('dashboard', [
        'label' => 'Dashboard',
        'icon' => 'dashboard',
        'url' => ADMIN_URL . '/',
        'capability' => 'author',
        'position' => 1,
    ]);
    
    // Media with submenu
    registerAdminMenu('media', [
        'label' => 'Media',
        'icon' => 'image',
        'url' => ADMIN_URL . '/media.php',
        'capability' => 'author',
        'position' => 20,
    ]);
    
    registerAdminSubmenu('media', 'media-library', [
        'label' => 'Library',
        'url' => ADMIN_URL . '/media.php',
    ]);
    
    registerAdminSubmenu('media', 'thumbnails', [
        'label' => 'Thumbnails',
        'url' => ADMIN_URL . '/thumbnails.php',
        'capability' => 'admin',
    ]);
    
    // Design
    registerAdminMenu('design', [
        'label' => 'Design',
        'icon' => 'palette',
        'url' => ADMIN_URL . '/customize.php',
        'capability' => 'admin',
        'position' => 30,
        'badge' => 'Live',
    ]);
    
    // Users
    registerAdminMenu('users', [
        'label' => 'Users',
        'icon' => 'users',
        'url' => ADMIN_URL . '/users.php',
        'capability' => 'admin',
        'position' => 40,
    ]);
    
    // Settings with submenu
    registerAdminMenu('settings', [
        'label' => 'Settings',
        'icon' => 'settings',
        'url' => ADMIN_URL . '/settings.php',
        'capability' => 'admin',
        'position' => 50,
    ]);
    
    registerAdminSubmenu('settings', 'general', [
        'label' => 'General',
        'url' => ADMIN_URL . '/settings.php',
        'capability' => 'admin',
    ]);
    
    registerAdminSubmenu('settings', 'post-types', [
        'label' => 'Post Types',
        'url' => ADMIN_URL . '/post-types.php',
        'capability' => 'admin',
    ]);
    
    // Tools
    registerAdminMenu('tools', [
        'label' => 'Tools',
        'icon' => 'tool',
        'url' => ADMIN_URL . '/update.php',
        'capability' => 'admin',
        'position' => 55,
    ]);
    
    registerAdminSubmenu('tools', 'update', [
        'label' => 'Update',
        'url' => ADMIN_URL . '/update.php',
        'capability' => 'admin',
    ]);
    
    registerAdminSubmenu('tools', 'plugins', [
        'label' => 'Plugins',
        'url' => ADMIN_URL . '/plugins.php',
        'capability' => 'admin',
    ]);
}

/**
 * Get SVG icon for admin menu
 */
function getAdminMenuIcon(string $icon, int $size = 20): string
{
    $icons = [
        'dashboard' => '<rect x="3" y="3" width="7" height="9" rx="1"></rect><rect x="14" y="3" width="7" height="5" rx="1"></rect><rect x="14" y="12" width="7" height="9" rx="1"></rect><rect x="3" y="16" width="7" height="5" rx="1"></rect>',
        'file-text' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line>',
        'file' => '<path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path><polyline points="13 2 13 9 20 9"></polyline>',
        'image' => '<rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline>',
        'users' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>',
        'settings' => '<circle cx="12" cy="12" r="3"></circle><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"></path>',
        'palette' => '<path d="M12 19l7-7 3 3-7 7-3-3z"></path><path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"></path><path d="M2 2l7.586 7.586"></path><circle cx="11" cy="11" r="2"></circle>',
        'layers' => '<polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline>',
        'tool' => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>',
        'upload' => '<polyline points="16 16 12 12 8 16"></polyline><line x1="12" y1="12" x2="12" y2="21"></line><path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"></path>',
        'grid' => '<rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect>',
        'box' => '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line>',
    ];
    
    $path = $icons[$icon] ?? $icons['file'];
    
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $path . '</svg>';
}
