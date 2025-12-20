<?php
/**
 * Core Helper Functions
 */

defined('CMS_ROOT') or die('Direct access not allowed');

// Backwards compatibility: Define constants that may be missing from older config.php files
if (!defined('UPLOADS_PATH')) {
    define('UPLOADS_PATH', defined('UPLOAD_PATH') ? UPLOAD_PATH : CMS_ROOT . '/uploads');
}
if (!defined('UPLOADS_URL')) {
    define('UPLOADS_URL', defined('UPLOAD_URL') ? UPLOAD_URL : SITE_URL . '/uploads');
}
if (!defined('THEMES_PATH')) {
    define('THEMES_PATH', defined('THEME_PATH') ? THEME_PATH : CMS_ROOT . '/themes');
}
if (!defined('THEMES_URL')) {
    define('THEMES_URL', SITE_URL . '/themes');
}
if (!defined('PLUGINS_PATH')) {
    define('PLUGINS_PATH', defined('PLUGIN_PATH') ? PLUGIN_PATH : CMS_ROOT . '/plugins');
}
if (!defined('PLUGINS_URL')) {
    define('PLUGINS_URL', SITE_URL . '/plugins');
}
if (!defined('INCLUDES_PATH')) {
    define('INCLUDES_PATH', CMS_ROOT . '/includes');
}

/**
 * Safe wrapper for Plugin::doAction - only calls if Plugin class exists
 */
function safe_do_action(string $hook, ...$args): void
{
    if (class_exists('Plugin')) {
        Plugin::doAction($hook, ...$args);
    }
}

/**
 * Safe wrapper for Plugin::applyFilters - returns value unchanged if Plugin class doesn't exist
 */
function safe_apply_filters(string $hook, $value, ...$args)
{
    if (class_exists('Plugin')) {
        return Plugin::applyFilters($hook, $value, ...$args);
    }
    return $value;
}

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
function formatDate(?string $date, ?string $format = null): string
{
    if (empty($date)) {
        return '';
    }
    // Use site's configured date format if none specified
    if ($format === null) {
        $format = getOption('date_format', 'M j, Y');
        // Sanitize format - only allow valid PHP date format characters
        $validChars = 'dDjlNSwzWFmMntLoYyaABgGhHisuveIOPTZcrU ,-./';
        $format = preg_replace('/[^' . preg_quote($validChars, '/') . ']/', '', $format);
        if (empty($format)) {
            $format = 'M j, Y';
        }
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
    // Allow filtering of option value before retrieval (only if Plugin class is loaded)
    if (class_exists('Plugin')) {
        $preValue = Plugin::applyFilters('pre_get_option_' . $name, null, $default);
        if ($preValue !== null) {
            return $preValue;
        }
    }
    
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
    // Allow filtering of option value before save (only if Plugin class is loaded)
    if (class_exists('Plugin')) {
        $value = Plugin::applyFilters('pre_update_option_' . $name, $value, $name);
    }
    
    $oldValue = getOption($name);
    
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
    
    // Fire option updated action (only if Plugin class is loaded)
    if (class_exists('Plugin')) {
        Plugin::doAction('option_updated', $name, $value, $oldValue);
    }
}

/**
 * Delete option from database
 */
function deleteOption(string $name): bool
{
    $table = Database::table('options');
    $existing = Database::queryOne(
        "SELECT id FROM {$table} WHERE option_name = ?",
        [$name]
    );
    
    if ($existing) {
        Database::query("DELETE FROM {$table} WHERE option_name = ?", [$name]);
        return true;
    }
    
    return false;
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
    
    registerAdminSubmenu('tools', 'api-keys', [
        'label' => 'API Keys',
        'url' => ADMIN_URL . '/api-keys.php',
        'capability' => 'admin',
    ]);
}

/**
 * Get SVG icon for admin menu
 */
function getAdminMenuIcon(string $icon, int $size = 20): string
{
    $icons = [
        // Core navigation
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
        'download' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line>',
        'grid' => '<rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect>',
        'box' => '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line>',
        'tag' => '<path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line>',
        'star' => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>',
        'heart' => '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>',
        'shopping-bag' => '<path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path>',
        'calendar' => '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line>',
        'map-pin' => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle>',
        'video' => '<polygon points="23 7 16 12 23 17 23 7"></polygon><rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect>',
        'music' => '<path d="M9 18V5l12-2v13"></path><circle cx="6" cy="18" r="3"></circle><circle cx="18" cy="16" r="3"></circle>',
        'book' => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>',
        
        // Additional icons
        'menu' => '<line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line>',
        'sliders' => '<line x1="4" y1="21" x2="4" y2="14"></line><line x1="4" y1="10" x2="4" y2="3"></line><line x1="12" y1="21" x2="12" y2="12"></line><line x1="12" y1="8" x2="12" y2="3"></line><line x1="20" y1="21" x2="20" y2="16"></line><line x1="20" y1="12" x2="20" y2="3"></line><line x1="1" y1="14" x2="7" y2="14"></line><line x1="9" y1="8" x2="15" y2="8"></line><line x1="17" y1="16" x2="23" y2="16"></line>',
        'puzzle' => '<path d="M19.439 7.85c-.049.322.059.648.289.878l1.568 1.568c.47.47.706 1.087.706 1.704s-.235 1.233-.706 1.704l-1.611 1.611a.98.98 0 0 1-.837.276c-.47-.07-.802-.48-.968-.925a2.501 2.501 0 1 0-3.214 3.214c.446.166.855.497.925.968a.979.979 0 0 1-.276.837l-1.61 1.61a2.404 2.404 0 0 1-1.705.707 2.402 2.402 0 0 1-1.704-.706l-1.568-1.568a1.026 1.026 0 0 0-.877-.29c-.493.074-.84.504-1.02.968a2.5 2.5 0 1 1-3.237-3.237c.464-.18.894-.527.967-1.02a1.026 1.026 0 0 0-.289-.877l-1.568-1.568A2.402 2.402 0 0 1 1.998 12c0-.617.236-1.234.706-1.704L4.23 8.77c.24-.24.581-.353.917-.303.515.077.877.528 1.073 1.01a2.5 2.5 0 1 0 3.259-3.259c-.482-.196-.933-.558-1.01-1.073-.05-.336.062-.676.303-.917l1.525-1.525A2.402 2.402 0 0 1 12 1.998c.617 0 1.234.236 1.704.706l1.568 1.568c.23.23.556.338.877.29.493-.074.84-.504 1.02-.968a2.5 2.5 0 1 1 3.237 3.237c-.464.18-.894.527-.967 1.02Z"></path>',
        'user' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle>',
        'home' => '<path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline>',
        'mail' => '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline>',
        'phone' => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>',
        'globe' => '<circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>',
        'link' => '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>',
        'clock' => '<circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline>',
        'search' => '<circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>',
        'plus' => '<line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line>',
        'minus' => '<line x1="5" y1="12" x2="19" y2="12"></line>',
        'x' => '<line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line>',
        'check' => '<polyline points="20 6 9 17 4 12"></polyline>',
        'alert-circle' => '<circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line>',
        'info' => '<circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line>',
        'trash' => '<polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>',
        'edit' => '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>',
        'copy' => '<rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>',
        'folder' => '<path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>',
        'archive' => '<polyline points="21 8 21 21 3 21 3 8"></polyline><rect x="1" y="3" width="22" height="5"></rect><line x1="10" y1="12" x2="14" y2="12"></line>',
        'eye' => '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>',
        'eye-off' => '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>',
        'lock' => '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path>',
        'unlock' => '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 9.9-1"></path>',
        'refresh-cw' => '<polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>',
        'share' => '<circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line>',
        'external-link' => '<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line>',
        'code' => '<polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline>',
        'terminal' => '<polyline points="4 17 10 11 4 5"></polyline><line x1="12" y1="19" x2="20" y2="19"></line>',
        'database' => '<ellipse cx="12" cy="5" rx="9" ry="3"></ellipse><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path>',
        'server' => '<rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect><rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect><line x1="6" y1="6" x2="6.01" y2="6"></line><line x1="6" y1="18" x2="6.01" y2="18"></line>',
        'cpu' => '<rect x="4" y="4" width="16" height="16" rx="2" ry="2"></rect><rect x="9" y="9" width="6" height="6"></rect><line x1="9" y1="1" x2="9" y2="4"></line><line x1="15" y1="1" x2="15" y2="4"></line><line x1="9" y1="20" x2="9" y2="23"></line><line x1="15" y1="20" x2="15" y2="23"></line><line x1="20" y1="9" x2="23" y2="9"></line><line x1="20" y1="14" x2="23" y2="14"></line><line x1="1" y1="9" x2="4" y2="9"></line><line x1="1" y1="14" x2="4" y2="14"></line>',
        'zap' => '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>',
        'award' => '<circle cx="12" cy="8" r="7"></circle><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline>',
        'target' => '<circle cx="12" cy="12" r="10"></circle><circle cx="12" cy="12" r="6"></circle><circle cx="12" cy="12" r="2"></circle>',
        'compass' => '<circle cx="12" cy="12" r="10"></circle><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"></polygon>',
        'coffee' => '<path d="M18 8h1a4 4 0 0 1 0 8h-1"></path><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path><line x1="6" y1="1" x2="6" y2="4"></line><line x1="10" y1="1" x2="10" y2="4"></line><line x1="14" y1="1" x2="14" y2="4"></line>',
        'gift' => '<polyline points="20 12 20 22 4 22 4 12"></polyline><rect x="2" y="7" width="20" height="5"></rect><line x1="12" y1="22" x2="12" y2="7"></line><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"></path><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"></path>',
        'briefcase' => '<rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>',
        'truck' => '<rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle>',
        'package' => '<line x1="16.5" y1="9.4" x2="7.5" y2="4.21"></line><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line>',
        'message-circle' => '<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>',
        'bell' => '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path>',
        'sun' => '<circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>',
        'moon' => '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>',
        'cloud' => '<path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"></path>',
        'bar-chart' => '<line x1="12" y1="20" x2="12" y2="10"></line><line x1="18" y1="20" x2="18" y2="4"></line><line x1="6" y1="20" x2="6" y2="16"></line>',
        'pie-chart' => '<path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path><path d="M22 12A10 10 0 0 0 12 2v10z"></path>',
        'activity' => '<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>',
        'headphones' => '<path d="M3 18v-6a9 9 0 0 1 18 0v6"></path><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"></path>',
        'camera' => '<path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle>',
        'mic' => '<path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"></path><path d="M19 10v2a7 7 0 0 1-14 0v-2"></path><line x1="12" y1="19" x2="12" y2="23"></line><line x1="8" y1="23" x2="16" y2="23"></line>',
        'printer' => '<polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect>',
        'save' => '<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline>',
        'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>',
        'key' => '<path d="m21 2-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0 3 3L22 7l-3-3m-3.5 3.5L19 4"></path>',
        'flag' => '<path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path><line x1="4" y1="22" x2="4" y2="15"></line>',
        'paperclip' => '<path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path>',
        'bookmark' => '<path d="m19 21-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path>',
        'thumbs-up' => '<path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"></path>',
        'thumbs-down' => '<path d="M10 15v4a3 3 0 0 0 3 3l4-9V2H5.72a2 2 0 0 0-2 1.7l-1.38 9a2 2 0 0 0 2 2.3zm7-13h2.67A2.31 2.31 0 0 1 22 4v7a2.31 2.31 0 0 1-2.33 2H17"></path>',
        'monitor' => '<rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line>',
        'smartphone' => '<rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line>',
        'wifi' => '<path d="M5 12.55a11 11 0 0 1 14.08 0"></path><path d="M1.42 9a16 16 0 0 1 21.16 0"></path><path d="M8.53 16.11a6 6 0 0 1 6.95 0"></path><line x1="12" y1="20" x2="12.01" y2="20"></line>',
        'feather' => '<path d="M20.24 12.24a6 6 0 0 0-8.49-8.49L5 10.5V19h8.5z"></path><line x1="16" y1="8" x2="2" y2="22"></line><line x1="17.5" y1="15" x2="9" y2="15"></line>',
        'send' => '<line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>',
        'trending-up' => '<polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline>',
    ];
    
    $path = $icons[$icon] ?? $icons['file'];
    
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $path . '</svg>';
}

// =========================================================================
// Custom Field Functions
// =========================================================================

/**
 * Get a custom field value for a post
 * 
 * Usage: get_custom_field('price', $post_id)
 * 
 * @param string $key The field key
 * @param int $postId The post ID
 * @param mixed $default Default value if field doesn't exist
 * @return mixed The field value or default
 */
function get_custom_field(string $key, int $postId, $default = null)
{
    $table = Database::table('postmeta');
    $result = Database::queryOne(
        "SELECT meta_value FROM {$table} WHERE post_id = ? AND meta_key = ?",
        [$postId, $key]
    );
    
    if ($result) {
        $value = $result['meta_value'];
        // Try to decode JSON (for arrays/objects)
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && (is_array($decoded) || is_object($decoded))) {
            return $decoded;
        }
        return $value;
    }
    
    return $default;
}

/**
 * Set a custom field value for a post
 * 
 * Usage: set_custom_field('price', 29.99, $post_id)
 * 
 * @param string $key The field key
 * @param mixed $value The value to set
 * @param int $postId The post ID
 * @return bool Success
 */
function set_custom_field(string $key, $value, int $postId): bool
{
    $table = Database::table('postmeta');
    
    // Encode arrays/objects as JSON
    if (is_array($value) || is_object($value)) {
        $value = json_encode($value);
    }
    
    // Check if exists
    $existing = Database::queryOne(
        "SELECT id FROM {$table} WHERE post_id = ? AND meta_key = ?",
        [$postId, $key]
    );
    
    if ($existing) {
        return Database::update($table, ['meta_value' => $value], 'id = ?', [$existing['id']]);
    } else {
        return Database::insert($table, [
            'post_id' => $postId,
            'meta_key' => $key,
            'meta_value' => $value
        ]);
    }
}

/**
 * Delete a custom field from a post
 * 
 * @param string $key The field key
 * @param int $postId The post ID
 * @return bool Success
 */
function delete_custom_field(string $key, int $postId): bool
{
    $table = Database::table('postmeta');
    return Database::delete($table, 'post_id = ? AND meta_key = ?', [$postId, $key]);
}

/**
 * Get all custom fields for a post
 * 
 * @param int $postId The post ID
 * @return array Associative array of key => value
 */
function get_all_custom_fields(int $postId): array
{
    $table = Database::table('postmeta');
    $results = Database::query(
        "SELECT meta_key, meta_value FROM {$table} WHERE post_id = ?",
        [$postId]
    );
    
    $fields = [];
    foreach ($results as $row) {
        $value = $row['meta_value'];
        // Try to decode JSON
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && (is_array($decoded) || is_object($decoded))) {
            $fields[$row['meta_key']] = $decoded;
        } else {
            $fields[$row['meta_key']] = $value;
        }
    }
    
    return $fields;
}

/**
 * Get the custom post type configuration
 * 
 * @param string $slug Post type slug
 * @return array|null Configuration or null if not found
 */
function get_post_type_config(string $slug): ?array
{
    $customPostTypes = getOption('custom_post_types');
    if (!is_array($customPostTypes)) {
        return null;
    }
    return $customPostTypes[$slug] ?? null;
}

/**
 * Get custom fields definition for a post type
 * 
 * @param string $postType Post type slug
 * @return array Array of field definitions
 */
function get_post_type_fields(string $postType): array
{
    $fields = [];
    
    // Get fields from post type config
    $config = get_post_type_config($postType);
    $typeFields = $config['fields'] ?? [];
    foreach ($typeFields as $field) {
        $field['source'] = 'post_type';
        // Prefix key with post type
        $field['key'] = $postType . '_' . $field['key'];
        $fields[] = $field;
    }
    
    // Get fields from custom field groups that apply to this post type
    $fieldGroups = getOption('custom_field_groups', []);
    foreach ($fieldGroups as $groupId => $group) {
        if (in_array($postType, $group['locations'] ?? [])) {
            foreach ($group['fields'] ?? [] as $field) {
                $field['source'] = 'field_group';
                $field['group_id'] = $groupId;
                $field['group_title'] = $group['title'];
                // Prefix key with post type
                $field['key'] = $postType . '_' . $field['key'];
                $fields[] = $field;
            }
        }
    }
    
    return $fields;
}

// =========================================================================
// Security Salt Generation (Built-in API)
// =========================================================================

/**
 * Generate a cryptographically secure random string
 */
function generateSecuritySalt(int $length = 64): string
{
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+[]{}|;:,.<>?';
    $salt = '';
    $max = strlen($chars) - 1;
    
    for ($i = 0; $i < $length; $i++) {
        $salt .= $chars[random_int(0, $max)];
    }
    
    return $salt;
}

/**
 * Generate all security salts as PHP constants
 */
function generateSecuritySalts(): string
{
    $keys = [
        'AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY',
        'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT',
        'SESSION_KEY', 'CSRF_KEY', 'API_KEY', 'ENCRYPTION_KEY'
    ];
    
    $output = "/**\n";
    $output .= " * VoidForge CMS Security Keys and Salts\n";
    $output .= " * Generated: " . date('Y-m-d H:i:s T') . "\n";
    $output .= " * \n";
    $output .= " * You can regenerate these at: " . SITE_URL . "/api/salts\n";
    $output .= " * Copy these to your config.php file.\n";
    $output .= " */\n\n";
    
    foreach ($keys as $key) {
        $salt = generateSecuritySalt(64);
        $output .= "define('" . $key . "', '" . $salt . "');\n";
    }
    
    return $output;
}

/**
 * Generate security salts as array (for JSON API)
 */
function generateSecuritySaltsArray(): array
{
    $keys = [
        'AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY',
        'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT',
        'SESSION_KEY', 'CSRF_KEY', 'API_KEY', 'ENCRYPTION_KEY'
    ];
    
    $salts = [];
    foreach ($keys as $key) {
        $salts[$key] = generateSecuritySalt(64);
    }
    
    return $salts;
}

// =====================================================
// NONCE FUNCTIONS (for forms)
// =====================================================

/**
 * Create a nonce (number used once) for form protection
 * 
 * @param string $action The action name for the nonce
 * @param int $lifetime Lifetime in seconds (default 12 hours)
 * @return string The nonce token
 */
function createNonce(string $action, int $lifetime = 43200): string
{
    $salt = defined('NONCE_SALT') && NONCE_SALT ? NONCE_SALT : 'voidforge_default_nonce_salt';
    $tick = ceil(time() / $lifetime);
    $userId = 0;
    
    if (class_exists('User')) {
        $user = User::current();
        if ($user) {
            $userId = $user['id'];
        }
    }
    
    $token = hash_hmac('sha256', $tick . '|' . $action . '|' . $userId, $salt);
    return substr($token, 0, 32);
}

/**
 * Verify a nonce token
 * 
 * @param string $nonce The nonce to verify
 * @param string $action The action name for the nonce
 * @param int $lifetime Lifetime in seconds (default 12 hours)
 * @return bool True if valid, false otherwise
 */
function verifyNonce(string $nonce, string $action, int $lifetime = 43200): bool
{
    if (empty($nonce)) {
        return false;
    }
    
    $salt = defined('NONCE_SALT') && NONCE_SALT ? NONCE_SALT : 'voidforge_default_nonce_salt';
    $userId = 0;
    
    if (class_exists('User')) {
        $user = User::current();
        if ($user) {
            $userId = $user['id'];
        }
    }
    
    // Check current tick and previous tick (allows for edge case timing)
    $tick = ceil(time() / $lifetime);
    
    for ($i = 0; $i <= 1; $i++) {
        $expected = hash_hmac('sha256', ($tick - $i) . '|' . $action . '|' . $userId, $salt);
        if (hash_equals(substr($expected, 0, 32), $nonce)) {
            return true;
        }
    }
    
    return false;
}

// =====================================================
// TEMPLATE HELPER FUNCTIONS
// =====================================================

/**
 * Get the site URL
 */
function site_url(string $path = ''): string
{
    $url = defined('SITE_URL') ? SITE_URL : '';
    if ($path) {
        $url = rtrim($url, '/') . '/' . ltrim($path, '/');
    }
    return $url;
}

/**
 * Get the site name
 */
function get_site_name(): string
{
    return getOption('site_name', 'VoidForge');
}

/**
 * Get the site description
 */
function get_site_description(): string
{
    return getOption('site_description', 'A modern content management system');
}

/**
 * Get the page title
 */
function get_page_title(): string
{
    global $post;
    
    $siteName = get_site_name();
    $separator = ' — ';
    
    if (isset($post) && !empty($post['title'])) {
        return $post['title'] . $separator . $siteName;
    }
    
    // Check for archive/taxonomy pages
    if (isset($GLOBALS['taxonomy_term']) && !empty($GLOBALS['taxonomy_term']['name'])) {
        return $GLOBALS['taxonomy_term']['name'] . $separator . $siteName;
    }
    
    return $siteName;
}

/**
 * Output the head action hook
 */
function vf_head(): void
{
    safe_do_action('vf_head');
}

/**
 * Output the footer action hook
 */
function vf_footer(): void
{
    safe_do_action('vf_footer');
}

/**
 * Check if a URL matches the current URL
 */
function is_current_url(string $url): bool
{
    $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $checkPath = parse_url($url, PHP_URL_PATH);
    
    // Normalize paths
    $currentPath = rtrim($currentPath, '/') ?: '/';
    $checkPath = rtrim($checkPath, '/') ?: '/';
    
    return $currentPath === $checkPath;
}

/**
 * Get the content for the current post (uses global $post)
 */
function the_content(): string
{
    global $post;
    
    if (!isset($post) || !is_array($post)) {
        return '';
    }
    
    $content = $post['content'] ?? '';
    
    // If Anvil Live is editing, don't render blocks normally - let AnvilLive handle it
    if (class_exists('AnvilLive') && AnvilLive::isEditing()) {
        // Pass raw content to the filter, AnvilLive will handle rendering
        if (class_exists('Plugin')) {
            $content = Plugin::applyFilters('the_content', $content, $post);
        }
        return $content;
    }
    
    // Check if content is Anvil blocks (JSON)
    $isAnvilContent = false;
    if (class_exists('Anvil') && !empty($content) && $content[0] === '[') {
        $blocks = Anvil::parseBlocks($content);
        if (!empty($blocks)) {
            $content = Anvil::renderBlocks($blocks);
            $isAnvilContent = true;
        }
    }
    
    // Apply content filter
    if (class_exists('Plugin')) {
        $content = Plugin::applyFilters('the_content', $content, $post);
    }
    
    // Apply Anvil Live page settings wrapper if this was Anvil content
    if ($isAnvilContent && class_exists('AnvilLive') && !empty($post['id'])) {
        $content = anvil_wrap_content_with_page_settings($content, $post['id']);
    }
    
    return $content;
}

/**
 * Wrap content with Anvil Live page settings
 */
function anvil_wrap_content_with_page_settings(string $content, int $postId): string
{
    $settings = AnvilLive::getPageSettings($postId);
    
    // Check contentWidthFull properly (can be bool, string, or int)
    $isFull = $settings['contentWidthFull'] ?? false;
    $isFullWidth = ($isFull === true || $isFull === 'true' || $isFull === '1' || $isFull === 1);
    
    // Check if we have any custom settings
    $hasCustomSettings = $isFullWidth || 
                         ($settings['contentWidth'] ?? '1200') !== '1200' ||
                         ($settings['paddingTop'] ?? '0') !== '0' ||
                         ($settings['paddingRight'] ?? '0') !== '0' ||
                         ($settings['paddingBottom'] ?? '0') !== '0' ||
                         ($settings['paddingLeft'] ?? '0') !== '0';
    
    if (!$hasCustomSettings) {
        return $content;
    }
    
    $styles = [];
    $classes = ['anvil-content-wrapper'];
    
    // Content width
    if ($isFullWidth) {
        $classes[] = 'anvil-full-width';
        // Full viewport width with breakout
        $styles[] = 'width: 100vw';
        $styles[] = 'position: relative';
        $styles[] = 'left: 50%';
        $styles[] = 'right: 50%';
        $styles[] = 'margin-left: -50vw';
        $styles[] = 'margin-right: -50vw';
    } else {
        $width = $settings['contentWidth'] ?? '1200';
        $unit = $settings['contentWidthUnit'] ?? 'px';
        $styles[] = 'max-width: ' . esc($width) . esc($unit);
        $styles[] = 'width: 100%';
        
        // Margin for non-full-width
        $mUnit = $settings['marginUnit'] ?? 'px';
        $mTop = $settings['marginTop'] ?? '0';
        $mRight = $settings['marginRight'] ?? 'auto';
        $mBottom = $settings['marginBottom'] ?? '0';
        $mLeft = $settings['marginLeft'] ?? 'auto';
        
        $mTopVal = $mTop === 'auto' ? 'auto' : esc($mTop) . esc($mUnit);
        $mRightVal = $mRight === 'auto' ? 'auto' : esc($mRight) . esc($mUnit);
        $mBottomVal = $mBottom === 'auto' ? 'auto' : esc($mBottom) . esc($mUnit);
        $mLeftVal = $mLeft === 'auto' ? 'auto' : esc($mLeft) . esc($mUnit);
        
        $styles[] = 'margin: ' . $mTopVal . ' ' . $mRightVal . ' ' . $mBottomVal . ' ' . $mLeftVal;
    }
    
    // Padding (applies to both full and non-full width)
    $pUnit = $settings['paddingUnit'] ?? 'px';
    $pTop = $settings['paddingTop'] ?? '0';
    $pRight = $settings['paddingRight'] ?? '0';
    $pBottom = $settings['paddingBottom'] ?? '0';
    $pLeft = $settings['paddingLeft'] ?? '0';
    
    if ($pTop !== '0' || $pRight !== '0' || $pBottom !== '0' || $pLeft !== '0') {
        $styles[] = 'padding: ' . esc($pTop) . esc($pUnit) . ' ' . esc($pRight) . esc($pUnit) . ' ' . esc($pBottom) . esc($pUnit) . ' ' . esc($pLeft) . esc($pUnit);
    }
    
    $styles[] = 'box-sizing: border-box';
    
    $classAttr = implode(' ', $classes);
    $styleAttr = implode('; ', $styles);
    
    return '<div class="' . $classAttr . '" style="' . $styleAttr . '">' . $content . '</div>';
}

/**
 * Get body classes
 */
function body_class(): string
{
    global $post;
    
    $classes = [];
    
    // Add post type class
    if (isset($post['post_type'])) {
        $classes[] = 'post-type-' . $post['post_type'];
        $classes[] = $post['post_type'] . '-' . ($post['id'] ?? 0);
    }
    
    // Add template class
    if (isset($GLOBALS['template'])) {
        $classes[] = 'template-' . $GLOBALS['template'];
    }
    
    // Check if home
    $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if ($currentPath === '/' || $currentPath === '') {
        $classes[] = 'home';
    }
    
    // Check if logged in
    if (class_exists('User') && User::isLoggedIn()) {
        $classes[] = 'logged-in';
    }
    
    // Apply filter
    $classes = safe_apply_filters('body_class', $classes);
    
    // Add Anvil Live editor class if in editor mode
    if (class_exists('AnvilLive') && AnvilLive::isEditorMode()) {
        $classes[] = 'anvil-live-editing';
    }
    
    return implode(' ', array_filter($classes));
}

// ============================================================================
// Anvil Live Helper Functions
// ============================================================================

/**
 * Get the Anvil Live edit URL for a post
 */
function get_anvil_live_edit_url($post): string
{
    if (!class_exists('AnvilLive')) {
        return '';
    }
    
    if (is_int($post)) {
        $post = Post::find($post);
    }
    
    if (!$post) {
        return '';
    }
    
    return AnvilLive::getEditUrl($post);
}

/**
 * Check if currently in Anvil Live editor mode
 */
function is_anvil_live_editing(): bool
{
    return class_exists('AnvilLive') && AnvilLive::isEditorMode();
}

/**
 * Output the backend "Edit with Anvil Live" button
 */
function anvil_live_backend_button($post): string
{
    if (!class_exists('AnvilLive')) {
        return '';
    }
    
    if (is_int($post)) {
        $post = Post::find($post);
    }
    
    if (!$post) {
        return '';
    }
    
    return AnvilLive::renderBackendButton($post);
}

/**
 * Output the frontend edit bar manually
 */
function anvil_live_edit_bar(): void
{
    if (class_exists('AnvilLive')) {
        AnvilLive::maybeRenderEditBar();
    }
}
