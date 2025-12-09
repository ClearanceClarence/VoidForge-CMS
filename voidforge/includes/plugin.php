<?php
/**
 * Plugin System - VoidForge CMS v0.1.2
 * 
 * Provides a comprehensive plugin API including:
 * - Hooks & Filters
 * - Shortcodes
 * - Plugin Settings API
 * - Admin Notices
 * - Asset Enqueueing
 * - AJAX Handlers
 * - Scheduled Tasks
 * - Widgets
 * - REST API Extensions
 */

defined('CMS_ROOT') or die('Direct access not allowed');

class Plugin
{
    /** @var array Registered actions */
    private static array $actions = [];
    
    /** @var array Registered filters */
    private static array $filters = [];
    
    /** @var array Registered shortcodes */
    private static array $shortcodes = [];
    
    /** @var array Registered content tags */
    private static array $tags = [];
    
    /** @var array Loaded plugins */
    private static array $plugins = [];
    
    /** @var array Plugin metadata cache */
    private static array $pluginData = [];
    
    /** @var array Registered admin pages */
    private static array $adminPages = [];
    
    /** @var array Registered admin notices */
    private static array $adminNotices = [];
    
    /** @var array Registered scripts */
    private static array $scripts = [];
    
    /** @var array Registered styles */
    private static array $styles = [];
    
    /** @var array Registered AJAX handlers */
    private static array $ajaxHandlers = [];
    
    /** @var array Registered widgets */
    private static array $widgets = [];
    
    /** @var array Registered REST routes */
    private static array $restRoutes = [];
    
    /** @var array Registered cron jobs */
    private static array $cronJobs = [];
    
    /** @var array Plugin settings schemas */
    private static array $settingsSchemas = [];

    // =========================================================================
    // Initialization
    // =========================================================================

    /**
     * Initialize plugin system and load active plugins
     */
    public static function init(): void
    {
        $pluginsDir = CMS_ROOT . '/plugins';
        
        if (!is_dir($pluginsDir)) {
            mkdir($pluginsDir, 0755, true);
        }
        
        // Get active plugins from database
        $activePlugins = self::getActivePlugins();
        
        // Load each active plugin
        foreach ($activePlugins as $pluginSlug) {
            self::load($pluginSlug);
        }
        
        // Fire plugins loaded action
        self::doAction('plugins_loaded');
        
        // Process scheduled tasks
        self::processCronJobs();
    }

    /**
     * Load a plugin by slug
     */
    public static function load(string $slug): bool
    {
        $pluginFile = CMS_ROOT . '/plugins/' . $slug . '/' . $slug . '.php';
        
        if (!file_exists($pluginFile)) {
            return false;
        }
        
        // Check requirements before loading
        $header = self::getPluginHeader($pluginFile);
        if (!self::checkRequirements($header)) {
            return false;
        }
        
        // Load plugin
        require_once $pluginFile;
        
        // Cache plugin data
        self::$plugins[$slug] = true;
        self::$pluginData[$slug] = $header;
        
        // Fire plugin loaded action
        self::doAction('plugin_loaded_' . $slug);
        
        return true;
    }

    // =========================================================================
    // Actions & Filters
    // =========================================================================

    /**
     * Register an action hook
     */
    public static function addAction(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        if (!isset(self::$actions[$hook])) {
            self::$actions[$hook] = [];
        }
        
        self::$actions[$hook][] = [
            'callback' => $callback,
            'priority' => $priority,
            'accepted_args' => $acceptedArgs,
        ];
        
        usort(self::$actions[$hook], fn($a, $b) => $a['priority'] <=> $b['priority']);
    }

    /**
     * Remove an action hook
     */
    public static function removeAction(string $hook, callable $callback, int $priority = 10): bool
    {
        if (!isset(self::$actions[$hook])) {
            return false;
        }
        
        foreach (self::$actions[$hook] as $key => $action) {
            if ($action['callback'] === $callback && $action['priority'] === $priority) {
                unset(self::$actions[$hook][$key]);
                return true;
            }
        }
        
        return false;
    }

    /**
     * Execute an action hook
     */
    public static function doAction(string $hook, ...$args): void
    {
        if (!isset(self::$actions[$hook])) {
            return;
        }
        
        foreach (self::$actions[$hook] as $action) {
            $callArgs = array_slice($args, 0, $action['accepted_args']);
            call_user_func_array($action['callback'], $callArgs);
        }
    }

    /**
     * Register a filter hook
     */
    public static function addFilter(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        if (!isset(self::$filters[$hook])) {
            self::$filters[$hook] = [];
        }
        
        self::$filters[$hook][] = [
            'callback' => $callback,
            'priority' => $priority,
            'accepted_args' => $acceptedArgs,
        ];
        
        usort(self::$filters[$hook], fn($a, $b) => $a['priority'] <=> $b['priority']);
    }

    /**
     * Apply a filter hook
     */
    public static function applyFilters(string $hook, mixed $value, ...$args): mixed
    {
        if (!isset(self::$filters[$hook])) {
            return $value;
        }
        
        foreach (self::$filters[$hook] as $filter) {
            $callArgs = array_merge([$value], array_slice($args, 0, $filter['accepted_args'] - 1));
            $value = call_user_func_array($filter['callback'], $callArgs);
        }
        
        return $value;
    }

    /**
     * Check if action has hooks
     */
    public static function hasAction(string $hook): bool
    {
        return !empty(self::$actions[$hook]);
    }

    /**
     * Check if filter has hooks
     */
    public static function hasFilter(string $hook): bool
    {
        return !empty(self::$filters[$hook]);
    }

    /**
     * Get number of times an action has been fired
     */
    public static function didAction(string $hook): int
    {
        static $counts = [];
        return $counts[$hook] ?? 0;
    }

    // =========================================================================
    // Shortcodes
    // =========================================================================

    /**
     * Register a shortcode
     * 
     * @param string $tag Shortcode tag (e.g., 'button')
     * @param callable $callback Function that returns output
     */
    public static function addShortcode(string $tag, callable $callback): void
    {
        self::$shortcodes[strtolower($tag)] = $callback;
    }

    /**
     * Remove a shortcode
     */
    public static function removeShortcode(string $tag): void
    {
        unset(self::$shortcodes[strtolower($tag)]);
    }

    /**
     * Check if shortcode exists
     */
    public static function shortcodeExists(string $tag): bool
    {
        return isset(self::$shortcodes[strtolower($tag)]);
    }

    /**
     * Process shortcodes in content
     * 
     * Supports: [tag], [tag attr="value"], [tag]content[/tag]
     */
    public static function doShortcode(string $content): string
    {
        if (empty(self::$shortcodes) || strpos($content, '[') === false) {
            return $content;
        }

        // Build pattern for all registered shortcodes
        $tagNames = array_keys(self::$shortcodes);
        $tagRegex = implode('|', array_map('preg_quote', $tagNames));
        
        // Match shortcodes with content: [tag]...[/tag]
        $pattern = '/\[(' . $tagRegex . ')(\s+[^\]]*?)?\](.*?)\[\/\1\]/s';
        $content = preg_replace_callback($pattern, function($matches) {
            $tag = strtolower($matches[1]);
            $attrs = self::parseShortcodeAttrs($matches[2] ?? '');
            $innerContent = $matches[3] ?? '';
            return self::executeShortcode($tag, $attrs, $innerContent);
        }, $content);
        
        // Match self-closing shortcodes: [tag] or [tag attr="value"]
        $pattern = '/\[(' . $tagRegex . ')(\s+[^\]]*?)?\]/';
        $content = preg_replace_callback($pattern, function($matches) {
            $tag = strtolower($matches[1]);
            $attrs = self::parseShortcodeAttrs($matches[2] ?? '');
            return self::executeShortcode($tag, $attrs, '');
        }, $content);
        
        return $content;
    }

    /**
     * Parse shortcode attributes
     */
    private static function parseShortcodeAttrs(string $attrString): array
    {
        $attrs = [];
        $attrString = trim($attrString);
        
        if (empty($attrString)) {
            return $attrs;
        }

        // Match: attr="value", attr='value', attr=value
        $pattern = '/([a-zA-Z_][a-zA-Z0-9_-]*)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s\]]+))/';
        
        if (preg_match_all($pattern, $attrString, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $key = $match[1];
                $value = $match[2] ?? $match[3] ?? $match[4] ?? '';
                $attrs[$key] = $value;
            }
        }

        return $attrs;
    }

    /**
     * Execute a shortcode callback
     */
    private static function executeShortcode(string $tag, array $attrs, string $content): string
    {
        if (!isset(self::$shortcodes[$tag])) {
            return '';
        }

        try {
            $result = call_user_func(self::$shortcodes[$tag], $attrs, $content, $tag);
            return is_string($result) ? $result : '';
        } catch (\Throwable $e) {
            if (defined('CMS_DEBUG') && CMS_DEBUG) {
                return '<!-- Shortcode error [' . $tag . ']: ' . esc($e->getMessage()) . ' -->';
            }
            return '';
        }
    }

    /**
     * Get all registered shortcodes
     */
    public static function getShortcodes(): array
    {
        return array_keys(self::$shortcodes);
    }

    // =========================================================================
    // Content Tags (Legacy support)
    // =========================================================================

    /**
     * Register a content tag (curly brace syntax)
     */
    public static function registerTag(string $name, callable $callback, array $options = []): void
    {
        $name = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', $name));
        
        self::$tags[$name] = [
            'callback' => $callback,
            'has_content' => $options['has_content'] ?? false,
            'description' => $options['description'] ?? '',
        ];
    }

    /**
     * Process content tags and shortcodes
     */
    public static function processContent(string $content): string
    {
        // Process shortcodes first
        $content = self::doShortcode($content);
        
        // Then process legacy tags
        if (empty(self::$tags) || strpos($content, '{') === false) {
            return $content;
        }

        // Process tags with content: {tag}...{/tag}
        foreach (self::$tags as $name => $tag) {
            if ($tag['has_content']) {
                $pattern = '/\{' . preg_quote($name, '/') . '(\s+[^}]*)?\}(.*?)\{\/' . preg_quote($name, '/') . '\}/s';
                $content = preg_replace_callback($pattern, function($matches) use ($name, $tag) {
                    $attrs = self::parseShortcodeAttrs($matches[1] ?? '');
                    $innerContent = $matches[2] ?? '';
                    return self::executeTag($name, $attrs, $innerContent);
                }, $content);
            }
        }

        // Process self-closing tags
        $pattern = '/\{([a-zA-Z0-9_-]+)(\s+[^}]*)?\}/';
        $content = preg_replace_callback($pattern, function($matches) {
            $name = strtolower($matches[1]);
            if (!isset(self::$tags[$name]) || self::$tags[$name]['has_content']) {
                return $matches[0];
            }
            $attrs = self::parseShortcodeAttrs($matches[2] ?? '');
            return self::executeTag($name, $attrs, '');
        }, $content);

        return $content;
    }

    private static function executeTag(string $name, array $attrs, string $content): string
    {
        if (!isset(self::$tags[$name])) {
            return '';
        }

        try {
            $result = call_user_func(self::$tags[$name]['callback'], $attrs, $content, $name);
            return is_string($result) ? $result : '';
        } catch (\Throwable $e) {
            return '';
        }
    }

    public static function getTags(): array
    {
        return self::$tags;
    }

    // =========================================================================
    // Plugin Settings API
    // =========================================================================

    /**
     * Register plugin settings
     * 
     * @param string $pluginSlug Plugin identifier
     * @param array $schema Settings schema definition
     */
    public static function registerSettings(string $pluginSlug, array $schema): void
    {
        self::$settingsSchemas[$pluginSlug] = $schema;
    }

    /**
     * Get plugin settings schema
     */
    public static function getSettingsSchema(string $pluginSlug): array
    {
        return self::$settingsSchemas[$pluginSlug] ?? [];
    }

    /**
     * Get a plugin setting
     */
    public static function getSetting(string $pluginSlug, string $key, mixed $default = null): mixed
    {
        $settings = getOption('plugin_settings_' . $pluginSlug, []);
        return $settings[$key] ?? $default;
    }

    /**
     * Set a plugin setting
     */
    public static function setSetting(string $pluginSlug, string $key, mixed $value): void
    {
        $settings = getOption('plugin_settings_' . $pluginSlug, []);
        $settings[$key] = $value;
        setOption('plugin_settings_' . $pluginSlug, $settings);
    }

    /**
     * Get all plugin settings
     */
    public static function getSettings(string $pluginSlug): array
    {
        return getOption('plugin_settings_' . $pluginSlug, []);
    }

    /**
     * Save all plugin settings
     */
    public static function saveSettings(string $pluginSlug, array $settings): void
    {
        setOption('plugin_settings_' . $pluginSlug, $settings);
    }

    /**
     * Delete plugin settings (on uninstall)
     */
    public static function deleteSettings(string $pluginSlug): void
    {
        deleteOption('plugin_settings_' . $pluginSlug);
    }

    // =========================================================================
    // Admin Pages
    // =========================================================================

    /**
     * Register an admin page
     */
    public static function registerAdminPage(string $slug, array $config): void
    {
        self::$adminPages[$slug] = array_merge([
            'title' => $slug,
            'menu_title' => $config['title'] ?? $slug,
            'icon' => 'puzzle',
            'parent' => null,
            'capability' => 'admin',
            'callback' => null,
            'position' => 99,
            'plugin' => null,
        ], $config);
    }

    /**
     * Get all registered admin pages
     */
    public static function getAdminPages(): array
    {
        return self::$adminPages;
    }

    /**
     * Get a specific admin page
     */
    public static function getAdminPage(string $slug): ?array
    {
        return self::$adminPages[$slug] ?? null;
    }

    /**
     * Render an admin page
     */
    public static function renderAdminPage(string $slug): bool
    {
        $page = self::$adminPages[$slug] ?? null;
        
        if (!$page || !is_callable($page['callback'])) {
            return false;
        }
        
        call_user_func($page['callback']);
        return true;
    }

    // =========================================================================
    // Admin Notices
    // =========================================================================

    /**
     * Add an admin notice
     * 
     * @param string $message Notice message
     * @param string $type Notice type: success, error, warning, info
     * @param bool $dismissible Can be dismissed
     */
    public static function addNotice(string $message, string $type = 'info', bool $dismissible = true): void
    {
        self::$adminNotices[] = [
            'message' => $message,
            'type' => $type,
            'dismissible' => $dismissible,
        ];
    }

    /**
     * Get all admin notices
     */
    public static function getNotices(): array
    {
        return self::$adminNotices;
    }

    /**
     * Render admin notices HTML
     */
    public static function renderNotices(): string
    {
        $html = '';
        foreach (self::$adminNotices as $notice) {
            $class = 'notice notice-' . esc($notice['type']);
            if ($notice['dismissible']) {
                $class .= ' is-dismissible';
            }
            $html .= '<div class="' . $class . '"><p>' . esc($notice['message']) . '</p></div>';
        }
        return $html;
    }

    // =========================================================================
    // Asset Enqueueing
    // =========================================================================

    /**
     * Enqueue a script
     */
    public static function enqueueScript(string $handle, string $src, array $deps = [], string $version = '', bool $inFooter = true): void
    {
        self::$scripts[$handle] = [
            'src' => $src,
            'deps' => $deps,
            'version' => $version ?: CMS_VERSION,
            'in_footer' => $inFooter,
        ];
    }

    /**
     * Enqueue a style
     */
    public static function enqueueStyle(string $handle, string $src, array $deps = [], string $version = ''): void
    {
        self::$styles[$handle] = [
            'src' => $src,
            'deps' => $deps,
            'version' => $version ?: CMS_VERSION,
        ];
    }

    /**
     * Get enqueued scripts
     */
    public static function getScripts(): array
    {
        return self::$scripts;
    }

    /**
     * Get enqueued styles
     */
    public static function getStyles(): array
    {
        return self::$styles;
    }

    /**
     * Render enqueued styles HTML
     */
    public static function renderStyles(): string
    {
        $html = '';
        foreach (self::$styles as $handle => $style) {
            $src = $style['src'];
            if ($style['version']) {
                $src .= (strpos($src, '?') !== false ? '&' : '?') . 'ver=' . $style['version'];
            }
            $html .= '<link rel="stylesheet" id="' . esc($handle) . '-css" href="' . esc($src) . '">' . "\n";
        }
        return $html;
    }

    /**
     * Render enqueued scripts HTML
     */
    public static function renderScripts(bool $footer = false): string
    {
        $html = '';
        foreach (self::$scripts as $handle => $script) {
            if ($script['in_footer'] !== $footer) {
                continue;
            }
            $src = $script['src'];
            if ($script['version']) {
                $src .= (strpos($src, '?') !== false ? '&' : '?') . 'ver=' . $script['version'];
            }
            $html .= '<script id="' . esc($handle) . '-js" src="' . esc($src) . '"></script>' . "\n";
        }
        return $html;
    }

    // =========================================================================
    // AJAX Handlers
    // =========================================================================

    /**
     * Register an AJAX handler
     * 
     * @param string $action Action name
     * @param callable $callback Handler function
     * @param bool $nopriv Allow non-logged-in users
     */
    public static function registerAjax(string $action, callable $callback, bool $nopriv = false): void
    {
        self::$ajaxHandlers[$action] = [
            'callback' => $callback,
            'nopriv' => $nopriv,
        ];
    }

    /**
     * Handle AJAX request
     */
    public static function handleAjax(string $action): void
    {
        if (!isset(self::$ajaxHandlers[$action])) {
            wp_send_json_error(['message' => 'Invalid action'], 400);
        }

        $handler = self::$ajaxHandlers[$action];
        
        // Check authentication if required
        if (!$handler['nopriv'] && !User::isLoggedIn()) {
            self::sendJsonError(['message' => 'Authentication required'], 401);
        }

        try {
            call_user_func($handler['callback']);
        } catch (\Throwable $e) {
            self::sendJsonError(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get registered AJAX handlers
     */
    public static function getAjaxHandlers(): array
    {
        return self::$ajaxHandlers;
    }

    /**
     * Send JSON success response
     */
    public static function sendJsonSuccess(mixed $data = null, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }

    /**
     * Send JSON error response
     */
    public static function sendJsonError(mixed $data = null, int $code = 400): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'data' => $data]);
        exit;
    }

    // =========================================================================
    // Widgets
    // =========================================================================

    /**
     * Register a widget
     */
    public static function registerWidget(string $id, array $config): void
    {
        self::$widgets[$id] = array_merge([
            'title' => $id,
            'description' => '',
            'callback' => null,
            'settings' => [],
        ], $config);
    }

    /**
     * Get registered widgets
     */
    public static function getWidgets(): array
    {
        return self::$widgets;
    }

    /**
     * Render a widget
     */
    public static function renderWidget(string $id, array $args = []): string
    {
        if (!isset(self::$widgets[$id]) || !is_callable(self::$widgets[$id]['callback'])) {
            return '';
        }

        try {
            ob_start();
            call_user_func(self::$widgets[$id]['callback'], $args);
            return ob_get_clean();
        } catch (\Throwable $e) {
            return '';
        }
    }

    // =========================================================================
    // REST API
    // =========================================================================

    /**
     * Register a REST API route
     */
    public static function registerRestRoute(string $namespace, string $route, array $config): void
    {
        $key = $namespace . '/' . ltrim($route, '/');
        self::$restRoutes[$key] = array_merge([
            'methods' => ['GET'],
            'callback' => null,
            'permission_callback' => null,
        ], $config);
    }

    /**
     * Get REST routes
     */
    public static function getRestRoutes(): array
    {
        return self::$restRoutes;
    }

    /**
     * Handle REST request - returns true if handled, false if not
     */
    public static function handleRestRequest(string $path): bool
    {
        if (empty(self::$restRoutes)) {
            return false;
        }
        
        foreach (self::$restRoutes as $route => $config) {
            $pattern = preg_replace('/\{([^}]+)\}/', '(?P<$1>[^/]+)', $route);
            if (preg_match('#^' . $pattern . '$#', $path, $matches)) {
                $method = $_SERVER['REQUEST_METHOD'];
                
                if (!in_array($method, (array)$config['methods'])) {
                    self::sendJsonError(['message' => 'Method not allowed'], 405);
                }

                // Check permission
                if ($config['permission_callback'] && !call_user_func($config['permission_callback'])) {
                    self::sendJsonError(['message' => 'Forbidden'], 403);
                }

                // Extract params
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                
                try {
                    $result = call_user_func($config['callback'], $params);
                    self::sendJsonSuccess($result);
                } catch (\Throwable $e) {
                    self::sendJsonError(['message' => $e->getMessage()], 500);
                }
            }
        }

        return false;
    }

    // =========================================================================
    // Cron / Scheduled Tasks
    // =========================================================================

    /**
     * Schedule a recurring task
     */
    public static function scheduleCron(string $hook, string $interval, callable $callback): void
    {
        self::$cronJobs[$hook] = [
            'interval' => $interval,
            'callback' => $callback,
        ];
    }

    /**
     * Unschedule a cron job
     */
    public static function unscheduleCron(string $hook): void
    {
        unset(self::$cronJobs[$hook]);
        deleteOption('cron_last_run_' . $hook);
    }

    /**
     * Process due cron jobs
     */
    public static function processCronJobs(): void
    {
        $intervals = [
            'hourly' => 3600,
            'twicedaily' => 43200,
            'daily' => 86400,
            'weekly' => 604800,
        ];

        foreach (self::$cronJobs as $hook => $job) {
            $lastRun = (int)getOption('cron_last_run_' . $hook, 0);
            $interval = $intervals[$job['interval']] ?? 86400;
            
            if (time() - $lastRun >= $interval) {
                try {
                    call_user_func($job['callback']);
                    setOption('cron_last_run_' . $hook, time());
                } catch (\Throwable $e) {
                    // Log error silently
                }
            }
        }
    }

    // =========================================================================
    // Plugin Management
    // =========================================================================

    /**
     * Get list of active plugins
     */
    public static function getActivePlugins(): array
    {
        $plugins = getOption('active_plugins', []);
        
        if (is_string($plugins)) {
            $plugins = json_decode($plugins, true);
        }
        
        return is_array($plugins) ? $plugins : [];
    }

    /**
     * Activate a plugin
     */
    public static function activate(string $slug): array
    {
        $pluginFile = CMS_ROOT . '/plugins/' . $slug . '/' . $slug . '.php';
        
        if (!file_exists($pluginFile)) {
            return ['success' => false, 'error' => 'Plugin not found'];
        }
        
        // Check requirements
        $header = self::getPluginHeader($pluginFile);
        $reqCheck = self::checkRequirements($header, true);
        if ($reqCheck !== true) {
            return ['success' => false, 'error' => $reqCheck];
        }
        
        $activePlugins = self::getActivePlugins();
        
        if (in_array($slug, $activePlugins)) {
            return ['success' => false, 'error' => 'Plugin already active'];
        }
        
        // Load the plugin to run activation hook
        require_once $pluginFile;
        
        // Run activation hook
        self::doAction('plugin_activate_' . $slug);
        self::doAction('plugin_activated', $slug);
        
        $activePlugins[] = $slug;
        setOption('active_plugins', json_encode($activePlugins));
        
        return ['success' => true];
    }

    /**
     * Deactivate a plugin
     */
    public static function deactivate(string $slug): array
    {
        $activePlugins = self::getActivePlugins();
        
        if (!in_array($slug, $activePlugins)) {
            return ['success' => false, 'error' => 'Plugin not active'];
        }
        
        // Run deactivation hook
        self::doAction('plugin_deactivate_' . $slug);
        self::doAction('plugin_deactivated', $slug);
        
        $activePlugins = array_filter($activePlugins, fn($p) => $p !== $slug);
        setOption('active_plugins', json_encode(array_values($activePlugins)));
        
        return ['success' => true];
    }

    /**
     * Uninstall a plugin (delete settings and files)
     */
    public static function uninstall(string $slug): array
    {
        // Deactivate first
        self::deactivate($slug);
        
        // Run uninstall hook
        self::doAction('plugin_uninstall_' . $slug);
        
        // Delete plugin settings
        self::deleteSettings($slug);
        
        // Delete plugin folder
        $pluginDir = CMS_ROOT . '/plugins/' . $slug;
        if (is_dir($pluginDir)) {
            self::deleteDirectory($pluginDir);
        }
        
        return ['success' => true];
    }

    /**
     * Delete a directory recursively
     */
    private static function deleteDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? self::deleteDirectory($path) : unlink($path);
        }
        
        return rmdir($dir);
    }

    /**
     * Get all available plugins
     */
    public static function getAll(): array
    {
        $plugins = [];
        $pluginsDir = CMS_ROOT . '/plugins';
        
        if (!is_dir($pluginsDir)) {
            return $plugins;
        }
        
        $dirs = scandir($pluginsDir);
        $activePlugins = self::getActivePlugins();
        
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..' || $dir === '.gitkeep') {
                continue;
            }
            
            $pluginFile = $pluginsDir . '/' . $dir . '/' . $dir . '.php';
            
            if (!file_exists($pluginFile)) {
                continue;
            }
            
            $header = self::getPluginHeader($pluginFile);
            $hasSettings = isset(self::$settingsSchemas[$dir]) || 
                          (isset(self::$adminPages[$dir . '-settings']));
            
            $plugins[] = [
                'slug' => $dir,
                'name' => $header['name'] ?? $dir,
                'description' => $header['description'] ?? '',
                'version' => $header['version'] ?? '1.0.0',
                'author' => $header['author'] ?? '',
                'author_uri' => $header['author_uri'] ?? '',
                'plugin_uri' => $header['plugin_uri'] ?? '',
                'requires_php' => $header['requires_php'] ?? '',
                'requires_cms' => $header['requires_cms'] ?? '',
                'active' => in_array($dir, $activePlugins),
                'has_settings' => $hasSettings,
                'file' => $pluginFile,
            ];
        }
        
        return $plugins;
    }

    /**
     * Get plugin header information
     */
    public static function getPluginHeader(string $file): array
    {
        if (!file_exists($file)) {
            return [];
        }
        
        $content = file_get_contents($file, false, null, 0, 4096);
        
        $headers = [
            'name' => 'Plugin Name',
            'description' => 'Description',
            'version' => 'Version',
            'author' => 'Author',
            'author_uri' => 'Author URI',
            'plugin_uri' => 'Plugin URI',
            'requires_php' => 'Requires PHP',
            'requires_cms' => 'Requires CMS',
            'license' => 'License',
            'text_domain' => 'Text Domain',
        ];
        
        $result = [];
        
        foreach ($headers as $key => $label) {
            if (preg_match('/^[\s\*]*' . preg_quote($label, '/') . ':\s*(.+)$/mi', $content, $matches)) {
                $result[$key] = trim($matches[1]);
            }
        }
        
        return $result;
    }

    /**
     * Check plugin requirements
     */
    public static function checkRequirements(array $header, bool $returnError = false): bool|string
    {
        // Check PHP version
        if (!empty($header['requires_php'])) {
            if (version_compare(PHP_VERSION, $header['requires_php'], '<')) {
                $error = 'Requires PHP ' . $header['requires_php'] . ' or higher';
                return $returnError ? $error : false;
            }
        }
        
        // Check CMS version
        if (!empty($header['requires_cms'])) {
            if (version_compare(CMS_VERSION, $header['requires_cms'], '<')) {
                $error = 'Requires VoidForge CMS ' . $header['requires_cms'] . ' or higher';
                return $returnError ? $error : false;
            }
        }
        
        return true;
    }

    /**
     * Check if a plugin is active
     */
    public static function isActive(string $slug): bool
    {
        return in_array($slug, self::getActivePlugins());
    }

    /**
     * Get loaded plugins
     */
    public static function getLoaded(): array
    {
        return array_keys(self::$plugins);
    }

    /**
     * Get plugin data
     */
    public static function getPluginData(string $slug): array
    {
        return self::$pluginData[$slug] ?? [];
    }

    // =========================================================================
    // Database Helpers
    // =========================================================================

    /**
     * Create a database table for a plugin
     */
    public static function createTable(string $tableName, string $sql): bool
    {
        $fullTableName = Database::table($tableName);
        $createSql = "CREATE TABLE IF NOT EXISTS `{$fullTableName}` ({$sql}) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        try {
            Database::query($createSql);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Drop a database table
     */
    public static function dropTable(string $tableName): bool
    {
        $fullTableName = Database::table($tableName);
        
        try {
            Database::query("DROP TABLE IF EXISTS `{$fullTableName}`");
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}

// =========================================================================
// Helper Functions
// =========================================================================

function add_action(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
{
    Plugin::addAction($hook, $callback, $priority, $acceptedArgs);
}

function remove_action(string $hook, callable $callback, int $priority = 10): bool
{
    return Plugin::removeAction($hook, $callback, $priority);
}

function do_action(string $hook, ...$args): void
{
    Plugin::doAction($hook, ...$args);
}

function has_action(string $hook): bool
{
    return Plugin::hasAction($hook);
}

function add_filter(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
{
    Plugin::addFilter($hook, $callback, $priority, $acceptedArgs);
}

function apply_filters(string $hook, mixed $value, ...$args): mixed
{
    return Plugin::applyFilters($hook, $value, ...$args);
}

function has_filter(string $hook): bool
{
    return Plugin::hasFilter($hook);
}

// Shortcodes
function add_shortcode(string $tag, callable $callback): void
{
    Plugin::addShortcode($tag, $callback);
}

function remove_shortcode(string $tag): void
{
    Plugin::removeShortcode($tag);
}

function shortcode_exists(string $tag): bool
{
    return Plugin::shortcodeExists($tag);
}

function do_shortcode(string $content): string
{
    return Plugin::doShortcode($content);
}

// Legacy tags
function register_tag(string $name, callable $callback, array $options = []): void
{
    Plugin::registerTag($name, $callback, $options);
}

function process_tags(string $content): string
{
    return Plugin::processContent($content);
}

// Admin pages
function add_admin_page(string $slug, array $config): void
{
    // Default parent to 'plugins' if not specified
    // Set parent to null or '' to make it a top-level menu
    if (!array_key_exists('parent', $config)) {
        $config['parent'] = 'plugins';
    }
    Plugin::registerAdminPage($slug, $config);
}

function get_admin_pages(): array
{
    return Plugin::getAdminPages();
}

// Admin notices
function add_admin_notice(string $message, string $type = 'info', bool $dismissible = true): void
{
    Plugin::addNotice($message, $type, $dismissible);
}

// Assets
function enqueue_script(string $handle, string $src, array $deps = [], string $version = '', bool $inFooter = true): void
{
    Plugin::enqueueScript($handle, $src, $deps, $version, $inFooter);
}

function enqueue_style(string $handle, string $src, array $deps = [], string $version = ''): void
{
    Plugin::enqueueStyle($handle, $src, $deps, $version);
}

// Settings
function register_plugin_settings(string $pluginSlug, array $schema): void
{
    Plugin::registerSettings($pluginSlug, $schema);
}

function get_plugin_setting(string $pluginSlug, string $key, mixed $default = null): mixed
{
    return Plugin::getSetting($pluginSlug, $key, $default);
}

function set_plugin_setting(string $pluginSlug, string $key, mixed $value): void
{
    Plugin::setSetting($pluginSlug, $key, $value);
}

// AJAX
function register_ajax_handler(string $action, callable $callback, bool $nopriv = false): void
{
    Plugin::registerAjax($action, $callback, $nopriv);
}

function wp_send_json_success(mixed $data = null, int $code = 200): void
{
    Plugin::sendJsonSuccess($data, $code);
}

function wp_send_json_error(mixed $data = null, int $code = 400): void
{
    Plugin::sendJsonError($data, $code);
}

// Widgets
function register_widget(string $id, array $config): void
{
    Plugin::registerWidget($id, $config);
}

// REST API
function register_rest_route(string $namespace, string $route, array $config): void
{
    Plugin::registerRestRoute($namespace, $route, $config);
}

// Cron
function schedule_event(string $hook, string $interval, callable $callback): void
{
    Plugin::scheduleCron($hook, $interval, $callback);
}

function unschedule_event(string $hook): void
{
    Plugin::unscheduleCron($hook);
}

// Database
function create_plugin_table(string $tableName, string $sql): bool
{
    return Plugin::createTable($tableName, $sql);
}

function drop_plugin_table(string $tableName): bool
{
    return Plugin::dropTable($tableName);
}
