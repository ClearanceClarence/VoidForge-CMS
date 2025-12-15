<?php
/**
 * AnvilBlock - Abstract base class for all Anvil blocks
 * 
 * Each block extends this class and defines its own settings,
 * attributes, and rendering logic.
 * 
 * @package VoidForge
 * @subpackage Anvil
 * @version 1.0.0
 */

defined('CMS_ROOT') or die('Direct access not allowed');

abstract class AnvilBlock
{
    /** @var string Block unique identifier */
    protected static string $name = '';
    
    /** @var string Display label */
    protected static string $label = '';
    
    /** @var string Block description */
    protected static string $description = '';
    
    /** @var string Category slug */
    protected static string $category = 'text';
    
    /** @var string Icon name (Lucide icons) */
    protected static string $icon = 'square';
    
    /** @var array Attribute definitions */
    protected static array $attributes = [];
    
    /** @var array Supported features */
    protected static array $supports = [];
    
    /**
     * Get block name
     */
    public static function getName(): string
    {
        return static::$name;
    }
    
    /**
     * Get block label
     */
    public static function getLabel(): string
    {
        return static::$label;
    }
    
    /**
     * Get block description
     */
    public static function getDescription(): string
    {
        return static::$description;
    }
    
    /**
     * Get block category
     */
    public static function getCategory(): string
    {
        return static::$category;
    }
    
    /**
     * Get block icon
     */
    public static function getIcon(): string
    {
        return static::$icon;
    }
    
    /**
     * Get attribute definitions
     */
    public static function getAttributes(): array
    {
        return static::$attributes;
    }
    
    /**
     * Get supported features
     */
    public static function getSupports(): array
    {
        return static::$supports;
    }
    
    /**
     * Get complete block definition for registration
     */
    public static function getDefinition(): array
    {
        return [
            'label' => static::$label,
            'description' => static::$description,
            'category' => static::$category,
            'icon' => static::$icon,
            'attributes' => static::$attributes,
            'supports' => static::$supports,
            'render_callback' => [static::class, 'render'],
            'class' => static::class,
        ];
    }
    
    /**
     * Register this block with Anvil
     */
    public static function register(): void
    {
        if (empty(static::$name)) {
            return;
        }
        
        Anvil::registerBlock(static::$name, static::getDefinition());
    }
    
    /**
     * Build CSS classes for the block
     */
    protected static function buildClasses(array $attrs, string $type): array
    {
        $classes = ['anvil-block', 'anvil-block-' . $type];
        
        if (!empty($attrs['className'])) {
            $classes[] = $attrs['className'];
        }
        
        if (!empty($attrs['align']) && $attrs['align'] !== 'none') {
            $classes[] = 'align' . $attrs['align'];
        }
        
        return $classes;
    }
    
    /**
     * Build class string from array
     */
    protected static function classString(array $classes): string
    {
        return implode(' ', array_filter($classes));
    }
    
    /**
     * Process inline content (basic rich text)
     */
    protected static function processInlineContent(string $content): string
    {
        return $content;
    }
    
    /**
     * Render the block to HTML
     * Each block must implement this method
     */
    abstract public static function render(array $attrs, array $block): string;
}
