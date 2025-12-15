<?php
/**
 * Columns Block
 * 
 * @package VoidForge
 * @subpackage Anvil/Blocks
 */

defined('CMS_ROOT') or die('Direct access not allowed');

class ColumnsBlock extends AnvilBlock
{
    protected static string $name = 'columns';
    protected static string $label = 'Columns';
    protected static string $description = 'Add columns layout';
    protected static string $category = 'layout';
    protected static string $icon = 'columns';
    
    protected static array $attributes = [
        'columns' => ['type' => 'array', 'default' => [[], []]],
        'columnCount' => ['type' => 'integer', 'default' => 2],
        'verticalAlign' => ['type' => 'string', 'default' => 'top'],
    ];
    
    protected static array $supports = ['className', 'innerBlocks'];
    
    public static function render(array $attrs, array $block): string
    {
        $classes = self::buildClasses($attrs, 'columns');
        $columnCount = max(2, min(6, (int)($attrs['columnCount'] ?? 2)));
        $columns = $attrs['columns'] ?? array_fill(0, $columnCount, []);
        $vAlign = $attrs['verticalAlign'] ?? 'top';
        
        $alignValue = match($vAlign) {
            'center' => 'center',
            'bottom' => 'end',
            default => 'start'
        };
        
        $style = sprintf(
            'display:grid;grid-template-columns:repeat(%d,1fr);gap:24px;align-items:%s;',
            $columnCount,
            $alignValue
        );
        
        $colsHtml = '';
        foreach ($columns as $colBlocks) {
            $colContent = is_array($colBlocks) ? Anvil::renderBlocks($colBlocks) : '';
            $colsHtml .= '<div class="anvil-column">' . $colContent . '</div>';
        }
        
        return sprintf(
            '<div class="%s" style="%s">%s</div>',
            esc(self::classString($classes)),
            $style,
            $colsHtml
        );
    }
}
