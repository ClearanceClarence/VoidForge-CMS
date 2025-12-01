<?php
/**
 * Plugin System - Forge CMS
 * Provides hooks and filters for extending CMS functionality
 */

defined('CMS_ROOT') or die('Direct access not allowed');

class Plugin
{
    /** @var array Registered actions */
    private static array $actions = [];
    
    /** @var array Registered filters */
    private static array $filters = [];
    
    /** @var array Loaded plugins */
    private static array $plugins = [];

    /**
     * Initialize plugin system and load active plugins
     */
    public static function init(): void
    {
        $pluginsDir = CMS_ROOT . '/plugins';
        
        if (!is_dir($pluginsDir)) {
            return;
        }
        
        // Get active plugins from database
        $activePlugins = self::getActivePlugins();
        
        foreach ($activePlugins as $pluginSlug) {
            self::load($pluginSlug);
        }
        
        // Run init action
        self::doAction('plugins_loaded');
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
        
        // Load plugin
        require_once $pluginFile;
        
        self::$plugins[$slug] = true;
        
        return true;
    }

    /**
     * Register an action hook
     */
    public static function addAction(string $hook, callable $callback, int $priority = 10): void
    {
        if (!isset(self::$actions[$hook])) {
            self::$actions[$hook] = [];
        }
        
        self::$actions[$hook][] = [
            'callback' => $callback,
            'priority' => $priority,
        ];
        
        // Sort by priority
        usort(self::$actions[$hook], fn($a, $b) => $a['priority'] <=> $b['priority']);
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
            call_user_func_array($action['callback'], $args);
        }
    }

    /**
     * Register a filter hook
     */
    public static function addFilter(string $hook, callable $callback, int $priority = 10): void
    {
        if (!isset(self::$filters[$hook])) {
            self::$filters[$hook] = [];
        }
        
        self::$filters[$hook][] = [
            'callback' => $callback,
            'priority' => $priority,
        ];
        
        // Sort by priority
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
            $value = call_user_func($filter['callback'], $value, ...$args);
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
     * Get list of active plugins from database
     */
    public static function getActivePlugins(): array
    {
        // getOption already decodes JSON, so we get an array directly
        $plugins = getOption('active_plugins', []);
        
        // Handle case where it might be stored as string
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
        
        $activePlugins = self::getActivePlugins();
        
        if (in_array($slug, $activePlugins)) {
            return ['success' => false, 'error' => 'Plugin already active'];
        }
        
        // Load the plugin to run activation hook
        require_once $pluginFile;
        
        // Run activation hook
        self::doAction('plugin_activate_' . $slug);
        
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
        
        $activePlugins = array_filter($activePlugins, fn($p) => $p !== $slug);
        setOption('active_plugins', json_encode(array_values($activePlugins)));
        
        return ['success' => true];
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
            
            // Read plugin header
            $header = self::getPluginHeader($pluginFile);
            
            $plugins[] = [
                'slug' => $dir,
                'name' => $header['name'] ?? $dir,
                'description' => $header['description'] ?? '',
                'version' => $header['version'] ?? '1.0.0',
                'author' => $header['author'] ?? '',
                'active' => in_array($dir, $activePlugins),
            ];
        }
        
        return $plugins;
    }

    /**
     * Get plugin header information
     */
    public static function getPluginHeader(string $file): array
    {
        $content = file_get_contents($file, false, null, 0, 2048);
        
        $headers = [
            'name' => 'Plugin Name',
            'description' => 'Description',
            'version' => 'Version',
            'author' => 'Author',
            'requires' => 'Requires',
        ];
        
        $result = [];
        
        foreach ($headers as $key => $label) {
            if (preg_match('/^[\s\*]*' . preg_quote($label) . ':\s*(.+)$/mi', $content, $matches)) {
                $result[$key] = trim($matches[1]);
            }
        }
        
        return $result;
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
}

// Helper functions for easier access

/**
 * Add an action hook
 */
function add_action(string $hook, callable $callback, int $priority = 10): void
{
    Plugin::addAction($hook, $callback, $priority);
}

/**
 * Execute an action
 */
function do_action(string $hook, ...$args): void
{
    Plugin::doAction($hook, ...$args);
}

/**
 * Add a filter hook
 */
function add_filter(string $hook, callable $callback, int $priority = 10): void
{
    Plugin::addFilter($hook, $callback, $priority);
}

/**
 * Apply filters
 */
function apply_filters(string $hook, mixed $value, ...$args): mixed
{
    return Plugin::applyFilters($hook, $value, ...$args);
}
