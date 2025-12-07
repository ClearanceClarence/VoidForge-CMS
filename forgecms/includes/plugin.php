<?php
/**
 * Plugin System - Forge CMS v1.0.10
 * Provides hooks, filters, and content tags for extending CMS functionality
 */

defined('CMS_ROOT') or die('Direct access not allowed');

class Plugin
{
    /** @var array Registered actions */
    private static array $actions = [];
    
    /** @var array Registered filters */
    private static array $filters = [];
    
    /** @var array Registered content tags */
    private static array $tags = [];
    
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

    // =========================================================================
    // Content Tag System
    // =========================================================================

    /**
     * Register a content tag
     * 
     * Tags can be used in content like: {tagname} or {tagname param="value"}
     * Or with content: {tagname}inner content{/tagname}
     * 
     * @param string $name Tag name (alphanumeric, underscores, hyphens)
     * @param callable $callback Function that returns the replacement HTML
     *                           Receives: (array $attrs, string $content, string $tagName)
     * @param array $options Tag options:
     *                       - 'has_content' => bool (whether tag wraps content)
     *                       - 'description' => string (for documentation)
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
     * Remove a registered tag
     */
    public static function removeTag(string $name): void
    {
        unset(self::$tags[strtolower($name)]);
    }

    /**
     * Check if a tag is registered
     */
    public static function hasTag(string $name): bool
    {
        return isset(self::$tags[strtolower($name)]);
    }

    /**
     * Get all registered tags
     */
    public static function getTags(): array
    {
        return self::$tags;
    }

    /**
     * Process content and replace all tags
     * 
     * Syntax:
     *   {tagname}
     *   {tagname attr="value" attr2="value2"}
     *   {tagname attr="value"}content here{/tagname}
     */
    public static function processContent(string $content): string
    {
        if (empty(self::$tags) || strpos($content, '{') === false) {
            return $content;
        }

        // Process tags with content first: {tag}...{/tag}
        foreach (self::$tags as $name => $tag) {
            if ($tag['has_content']) {
                $pattern = '/\{' . preg_quote($name, '/') . '(\s+[^}]*)?\}(.*?)\{\/' . preg_quote($name, '/') . '\}/s';
                $content = preg_replace_callback($pattern, function($matches) use ($name, $tag) {
                    $attrs = self::parseTagAttributes($matches[1] ?? '');
                    $innerContent = $matches[2] ?? '';
                    return self::executeTag($name, $attrs, $innerContent);
                }, $content);
            }
        }

        // Process self-closing tags: {tag} or {tag attr="value"}
        $pattern = '/\{([a-zA-Z0-9_-]+)(\s+[^}]*)?\}/';
        $content = preg_replace_callback($pattern, function($matches) {
            $name = strtolower($matches[1]);
            
            // Skip if it looks like a closing tag
            if (strpos($name, '/') === 0) {
                return $matches[0];
            }
            
            if (!isset(self::$tags[$name])) {
                return $matches[0]; // Return original if tag not found
            }
            
            // Skip content tags (already processed)
            if (self::$tags[$name]['has_content']) {
                return $matches[0];
            }
            
            $attrs = self::parseTagAttributes($matches[2] ?? '');
            return self::executeTag($name, $attrs, '');
        }, $content);

        return $content;
    }

    /**
     * Parse tag attributes from string
     * Supports: attr="value" attr='value' attr=value
     */
    private static function parseTagAttributes(string $attrString): array
    {
        $attrs = [];
        $attrString = trim($attrString);
        
        if (empty($attrString)) {
            return $attrs;
        }

        // Match attribute patterns
        $pattern = '/([a-zA-Z_][a-zA-Z0-9_-]*)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s}]+))/';
        
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
     * Execute a tag callback
     */
    private static function executeTag(string $name, array $attrs, string $content): string
    {
        if (!isset(self::$tags[$name])) {
            return '';
        }

        try {
            $result = call_user_func(self::$tags[$name]['callback'], $attrs, $content, $name);
            return is_string($result) ? $result : '';
        } catch (\Throwable $e) {
            // Log error in development, return empty in production
            if (defined('CMS_DEBUG') && CMS_DEBUG) {
                return '<!-- Tag error: ' . htmlspecialchars($e->getMessage()) . ' -->';
            }
            return '';
        }
    }

    // =========================================================================
    // Plugin Management
    // =========================================================================

    /**
     * Get list of active plugins from database
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

// =========================================================================
// Helper Functions
// =========================================================================

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

/**
 * Register a content tag
 * 
 * Example:
 *   register_tag('button', function($attrs, $content) {
 *       $class = $attrs['class'] ?? 'btn';
 *       $href = $attrs['href'] ?? '#';
 *       return '<a href="' . esc($href) . '" class="' . esc($class) . '">' . $content . '</a>';
 *   }, ['has_content' => true]);
 * 
 * Usage in content: {button href="/contact" class="btn-primary"}Click Me{/button}
 */
function register_tag(string $name, callable $callback, array $options = []): void
{
    Plugin::registerTag($name, $callback, $options);
}

/**
 * Process content tags
 */
function process_tags(string $content): string
{
    return Plugin::processContent($content);
}
