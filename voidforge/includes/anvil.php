<?php
/**
 * Anvil Block Editor - VoidForge CMS
 * A modular block-based content editor
 * 
 * @version 2.0.0
 */

defined('CMS_ROOT') or die('Direct access not allowed');

class Anvil
{
    /** @var array Registered block types */
    private static array $blocks = [];
    
    /** @var array Block categories */
    private static array $categories = [];
    
    /** @var bool Whether Anvil has been initialized */
    private static bool $initialized = false;
    
    /** @var array Registered block class names */
    private static array $blockClasses = [];
    
    /**
     * Initialize Anvil with default blocks and categories
     */
    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }
        
        // Load the base block class
        require_once CMS_ROOT . '/includes/anvil/AnvilBlock.php';
        
        // Register default categories
        self::registerDefaultCategories();
        
        // Load and register default blocks
        self::loadDefaultBlocks();
        
        // Allow plugins/themes to register custom blocks
        if (class_exists('Plugin')) {
            safe_do_action('anvil_register_blocks');
        }
        
        self::$initialized = true;
    }
    
    /**
     * Register default categories
     */
    private static function registerDefaultCategories(): void
    {
        self::registerCategory('text', [
            'label' => 'Text',
            'icon' => 'type',
            'order' => 10,
        ]);
        
        self::registerCategory('media', [
            'label' => 'Media',
            'icon' => 'image',
            'order' => 20,
        ]);
        
        self::registerCategory('layout', [
            'label' => 'Layout',
            'icon' => 'layout',
            'order' => 30,
        ]);
        
        self::registerCategory('embed', [
            'label' => 'Embeds',
            'icon' => 'code',
            'order' => 40,
        ]);
    }
    
    /**
     * Load default blocks from the blocks directory
     */
    private static function loadDefaultBlocks(): void
    {
        $blocksDir = CMS_ROOT . '/includes/anvil/blocks';
        
        if (!is_dir($blocksDir)) {
            return;
        }
        
        // Get all PHP files in the blocks directory
        $files = glob($blocksDir . '/*Block.php');
        
        foreach ($files as $file) {
            require_once $file;
            
            // Get class name from filename (e.g., ParagraphBlock.php -> ParagraphBlock)
            $className = basename($file, '.php');
            
            if (class_exists($className) && is_subclass_of($className, 'AnvilBlock')) {
                $className::register();
                self::$blockClasses[$className::getName()] = $className;
            }
        }
    }
    
    /**
     * Register a block from a class
     * 
     * Usage in plugins/themes:
     * Anvil::registerBlockClass(MyCustomBlock::class);
     */
    public static function registerBlockClass(string $className): bool
    {
        if (!class_exists($className)) {
            return false;
        }
        
        if (!is_subclass_of($className, 'AnvilBlock')) {
            return false;
        }
        
        $className::register();
        self::$blockClasses[$className::getName()] = $className;
        
        return true;
    }
    
    /**
     * Register a block type (array-based, for backwards compatibility)
     */
    public static function registerBlock(string $name, array $args): void
    {
        $defaults = [
            'label' => ucfirst($name),
            'description' => '',
            'category' => 'text',
            'icon' => 'square',
            'attributes' => [],
            'supports' => [],
            'render_callback' => null,
            'class' => null,
        ];
        
        self::$blocks[$name] = array_merge($defaults, $args);
    }
    
    /**
     * Unregister a block type
     */
    public static function unregisterBlock(string $name): bool
    {
        if (isset(self::$blocks[$name])) {
            unset(self::$blocks[$name]);
            unset(self::$blockClasses[$name]);
            return true;
        }
        return false;
    }
    
    /**
     * Get all registered blocks
     */
    public static function getBlocks(): array
    {
        return self::$blocks;
    }
    
    /**
     * Get a specific block type
     */
    public static function getBlock(string $name): ?array
    {
        return self::$blocks[$name] ?? null;
    }
    
    /**
     * Get block class for a type
     */
    public static function getBlockClass(string $name): ?string
    {
        return self::$blockClasses[$name] ?? null;
    }
    
    /**
     * Register a block category
     */
    public static function registerCategory(string $slug, array $args): void
    {
        $defaults = [
            'label' => ucfirst($slug),
            'icon' => 'folder',
            'order' => 100,
        ];
        
        self::$categories[$slug] = array_merge($defaults, $args);
    }
    
    /**
     * Get all categories sorted by order
     */
    public static function getCategories(): array
    {
        uasort(self::$categories, fn($a, $b) => $a['order'] <=> $b['order']);
        return self::$categories;
    }
    
    /**
     * Get blocks grouped by category
     */
    public static function getBlocksByCategory(): array
    {
        $grouped = [];
        foreach (self::$categories as $slug => $category) {
            $grouped[$slug] = [
                'category' => $category,
                'blocks' => [],
            ];
        }
        
        foreach (self::$blocks as $name => $block) {
            $cat = $block['category'];
            if (!isset($grouped[$cat])) {
                $grouped[$cat] = [
                    'category' => ['label' => ucfirst($cat), 'icon' => 'folder', 'order' => 999],
                    'blocks' => [],
                ];
            }
            $grouped[$cat]['blocks'][$name] = $block;
        }
        
        // Remove empty categories
        return array_filter($grouped, fn($g) => !empty($g['blocks']));
    }
    
    /**
     * Parse blocks from JSON content
     */
    public static function parseBlocks(string $json): array
    {
        if (empty($json)) {
            return [];
        }
        
        $data = json_decode($json, true);
        if (!is_array($data)) {
            return [];
        }
        
        return $data;
    }
    
    /**
     * Serialize blocks to JSON
     */
    public static function serializeBlocks(array $blocks): string
    {
        return json_encode($blocks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    
    /**
     * Render blocks to HTML for frontend display
     */
    public static function renderBlocks(array $blocks): string
    {
        $html = '';
        
        foreach ($blocks as $block) {
            $html .= self::renderBlock($block);
        }
        
        return $html;
    }
    
    /**
     * Render a single block to HTML
     */
    public static function renderBlock(array $block): string
    {
        $type = $block['type'] ?? '';
        $attrs = $block['attributes'] ?? [];
        
        $blockDef = self::getBlock($type);
        if (!$blockDef) {
            return '<!-- Unknown block type: ' . esc($type) . ' -->';
        }
        
        // Use render callback (from block class or custom callback)
        if (is_callable($blockDef['render_callback'])) {
            return call_user_func($blockDef['render_callback'], $attrs, $block);
        }
        
        // Fallback to default render
        return self::defaultRender($type, $attrs, $block);
    }
    
    /**
     * Default block rendering (fallback for blocks without render callback)
     */
    private static function defaultRender(string $type, array $attrs, array $block): string
    {
        $className = $attrs['className'] ?? '';
        
        $classes = ['anvil-block', 'anvil-block-' . $type];
        if ($className) {
            $classes[] = $className;
        }
        
        $classStr = implode(' ', $classes);
        
        // Basic fallback rendering
        $content = $attrs['content'] ?? '';
        
        return sprintf(
            '<div class="%s">%s</div>',
            esc($classStr),
            esc($content)
        );
    }
    
    /**
     * Convert legacy HTML content to blocks
     */
    public static function htmlToBlocks(string $html): array
    {
        if (empty(trim($html))) {
            return [];
        }
        
        $blocks = [];
        $html = trim($html);
        
        // Check if it's simple paragraphs
        if (!preg_match('/<(h[1-6]|ul|ol|blockquote|pre|table|figure|div|img|video)/i', $html)) {
            // Simple text, split by double newlines or <p> tags
            $paragraphs = preg_split('/\s*<\/p>\s*<p[^>]*>\s*|\s*<br\s*\/?>\s*<br\s*\/?>\s*|\n\n+/', $html);
            foreach ($paragraphs as $p) {
                $p = strip_tags(trim($p), '<strong><em><a><code><br>');
                $p = preg_replace('/^<p[^>]*>|<\/p>$/', '', $p);
                if (!empty($p)) {
                    $blocks[] = [
                        'id' => self::generateBlockId(),
                        'type' => 'paragraph',
                        'attributes' => ['content' => $p],
                    ];
                }
            }
        } else {
            // Complex HTML, use single HTML block
            $blocks[] = [
                'id' => self::generateBlockId(),
                'type' => 'html',
                'attributes' => ['content' => $html],
            ];
        }
        
        return $blocks;
    }
    
    /**
     * Convert blocks to plain HTML (for legacy compatibility)
     */
    public static function blocksToHtml(array $blocks): string
    {
        return self::renderBlocks($blocks);
    }
    
    /**
     * Generate a unique block ID
     */
    public static function generateBlockId(): string
    {
        return 'block-' . bin2hex(random_bytes(8));
    }
    
    /**
     * Validate block data
     */
    public static function validateBlock(array $block): bool
    {
        if (empty($block['type'])) {
            return false;
        }
        
        $blockDef = self::getBlock($block['type']);
        if (!$blockDef) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get blocks data as JSON for the editor
     */
    public static function getEditorData(): array
    {
        return [
            'blocks' => self::$blocks,
            'categories' => self::getCategories(),
            'blocksByCategory' => self::getBlocksByCategory(),
        ];
    }
    
    /**
     * Get list of all registered block names
     */
    public static function getBlockNames(): array
    {
        return array_keys(self::$blocks);
    }
    
    /**
     * Check if a block type is registered
     */
    public static function hasBlock(string $name): bool
    {
        return isset(self::$blocks[$name]);
    }
}
