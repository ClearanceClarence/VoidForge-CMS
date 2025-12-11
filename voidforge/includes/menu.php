<?php
/**
 * Menu Management Class - VoidForge CMS
 * 
 * Handles navigation menu creation, management, and display
 */

class Menu
{
    private static array $locations = [];
    private static bool $initialized = false;

    /**
     * Initialize the Menu system
     */
    public static function init(): void
    {
        if (self::$initialized) return;
        self::$initialized = true;
        
        // Register default locations
        self::registerLocation('primary', 'Primary Navigation');
        self::registerLocation('footer', 'Footer Menu');
    }

    /**
     * Register a menu location (called by themes)
     */
    public static function registerLocation(string $slug, string $name): void
    {
        self::$locations[$slug] = $name;
    }

    /**
     * Get all registered locations
     */
    public static function getLocations(): array
    {
        return self::$locations;
    }

    /**
     * Get menu assigned to a location
     */
    public static function getMenuByLocation(string $location): ?array
    {
        $table = Database::table('menus');
        $menu = Database::queryOne("SELECT * FROM {$table} WHERE location = ?", [$location]);
        return $menu ?: null;
    }

    /**
     * Create a new menu
     */
    public static function create(array $data): int
    {
        $table = Database::table('menus');
        
        return Database::insert($table, [
            'name' => $data['name'],
            'slug' => slugify($data['name']),
            'location' => $data['location'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Update a menu
     */
    public static function update(int $id, array $data): bool
    {
        $table = Database::table('menus');
        
        $updateData = ['name' => $data['name']];
        
        if (isset($data['location'])) {
            // Clear location from other menus first
            if ($data['location']) {
                Database::query("UPDATE {$table} SET location = NULL WHERE location = ? AND id != ?", 
                    [$data['location'], $id]);
            }
            $updateData['location'] = $data['location'] ?: null;
        }
        
        return Database::update($table, $updateData, "id = ?", [$id]) > 0;
    }

    /**
     * Delete a menu and its items
     */
    public static function delete(int $id): bool
    {
        $menusTable = Database::table('menus');
        $itemsTable = Database::table('menu_items');
        
        // Delete all menu items first
        Database::query("DELETE FROM {$itemsTable} WHERE menu_id = ?", [$id]);
        
        // Delete the menu
        return Database::delete($menusTable, "id = ?", [$id]) > 0;
    }

    /**
     * Get a menu by ID
     */
    public static function find(int $id): ?array
    {
        $table = Database::table('menus');
        return Database::queryOne("SELECT * FROM {$table} WHERE id = ?", [$id]);
    }

    /**
     * Get all menus
     */
    public static function getAll(): array
    {
        $table = Database::table('menus');
        return Database::query("SELECT * FROM {$table} ORDER BY name ASC");
    }

    /**
     * Add a menu item
     */
    public static function addItem(int $menuId, array $data): int
    {
        $table = Database::table('menu_items');
        
        // Get max position
        $maxPos = Database::queryOne(
            "SELECT MAX(position) as max_pos FROM {$table} WHERE menu_id = ? AND parent_id = ?",
            [$menuId, $data['parent_id'] ?? 0]
        );
        $position = ($maxPos['max_pos'] ?? -1) + 1;
        
        return Database::insert($table, [
            'menu_id' => $menuId,
            'parent_id' => $data['parent_id'] ?? 0,
            'title' => $data['title'],
            'type' => $data['type'], // page, post, custom, category, post_type
            'object_id' => $data['object_id'] ?? null,
            'url' => $data['url'] ?? null,
            'target' => $data['target'] ?? '_self',
            'css_class' => $data['css_class'] ?? null,
            'position' => $position,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Update a menu item
     */
    public static function updateItem(int $id, array $data): bool
    {
        $table = Database::table('menu_items');
        
        $updateData = [];
        if (isset($data['title'])) $updateData['title'] = $data['title'];
        if (isset($data['url'])) $updateData['url'] = $data['url'];
        if (isset($data['target'])) $updateData['target'] = $data['target'];
        if (isset($data['css_class'])) $updateData['css_class'] = $data['css_class'];
        if (isset($data['parent_id'])) $updateData['parent_id'] = $data['parent_id'];
        if (isset($data['position'])) $updateData['position'] = $data['position'];
        
        return Database::update($table, $updateData, "id = ?", [$id]) > 0;
    }

    /**
     * Delete a menu item and its children
     */
    public static function deleteItem(int $id): bool
    {
        $table = Database::table('menu_items');
        
        // Get item to find menu_id
        $item = Database::queryOne("SELECT * FROM {$table} WHERE id = ?", [$id]);
        if (!$item) return false;
        
        // Delete children first (recursive)
        $children = Database::query("SELECT id FROM {$table} WHERE parent_id = ?", [$id]);
        foreach ($children as $child) {
            self::deleteItem($child['id']);
        }
        
        // Delete the item
        return Database::delete($table, "id = ?", [$id]) > 0;
    }

    /**
     * Get menu items as hierarchical tree
     */
    public static function getItems(int $menuId): array
    {
        $table = Database::table('menu_items');
        $items = Database::query(
            "SELECT * FROM {$table} WHERE menu_id = ? ORDER BY position ASC",
            [$menuId]
        );
        
        return self::buildTree($items);
    }

    /**
     * Get flat list of menu items
     */
    public static function getItemsFlat(int $menuId): array
    {
        $table = Database::table('menu_items');
        return Database::query(
            "SELECT * FROM {$table} WHERE menu_id = ? ORDER BY position ASC",
            [$menuId]
        );
    }

    /**
     * Build hierarchical tree from flat items
     */
    private static function buildTree(array $items, int $parentId = 0): array
    {
        $branch = [];
        
        foreach ($items as $item) {
            if ((int)$item['parent_id'] === $parentId) {
                $item['children'] = self::buildTree($items, (int)$item['id']);
                $branch[] = $item;
            }
        }
        
        return $branch;
    }

    /**
     * Save menu item order (from drag-and-drop)
     */
    public static function saveOrder(int $menuId, array $items, int $parentId = 0): void
    {
        $table = Database::table('menu_items');
        
        foreach ($items as $position => $item) {
            Database::update($table, [
                'parent_id' => $parentId,
                'position' => $position,
            ], "id = ? AND menu_id = ?", [$item['id'], $menuId]);
            
            if (!empty($item['children'])) {
                self::saveOrder($menuId, $item['children'], $item['id']);
            }
        }
    }

    /**
     * Get the URL for a menu item
     */
    public static function getItemUrl(array $item): string
    {
        switch ($item['type']) {
            case 'custom':
                return $item['url'] ?: '#';
                
            case 'page':
            case 'post':
                if ($item['object_id']) {
                    $post = Post::find($item['object_id']);
                    if ($post) {
                        return Post::permalink($post);
                    }
                }
                return '#';
                
            case 'category':
                if ($item['object_id']) {
                    return SITE_URL . '/category/' . $item['object_id'];
                }
                return '#';
                
            case 'post_type':
                if ($item['url']) {
                    return SITE_URL . '/' . $item['url'];
                }
                return '#';
                
            default:
                // For custom post types, try to get the post permalink
                if ($item['object_id']) {
                    $post = Post::find($item['object_id']);
                    if ($post) {
                        return Post::permalink($post);
                    }
                }
                return $item['url'] ?: '#';
        }
    }

    /**
     * Display a menu by location
     */
    public static function display(string $location, array $options = []): string
    {
        $menu = self::getMenuByLocation($location);
        if (!$menu) {
            return '';
        }
        
        return self::render($menu['id'], $options);
    }

    /**
     * Render a menu by ID
     */
    public static function render(int $menuId, array $options = []): string
    {
        $items = self::getItems($menuId);
        if (empty($items)) {
            return '';
        }
        
        $defaults = [
            'container' => 'nav',
            'container_class' => 'navigation',
            'container_id' => '',
            'menu_class' => 'menu',
            'menu_id' => '',
            'item_class' => 'menu-item',
            'link_class' => 'menu-link',
            'submenu_class' => 'sub-menu',
            'depth' => 0, // 0 = unlimited
            'before' => '',
            'after' => '',
            'link_before' => '',
            'link_after' => '',
        ];
        
        $options = array_merge($defaults, $options);
        
        $html = self::renderItems($items, $options, 0);
        
        // Wrap in UL
        $menuAttr = $options['menu_class'] ? ' class="' . esc($options['menu_class']) . '"' : '';
        $menuAttr .= $options['menu_id'] ? ' id="' . esc($options['menu_id']) . '"' : '';
        $html = "<ul{$menuAttr}>{$html}</ul>";
        
        // Wrap in container
        if ($options['container']) {
            $containerAttr = $options['container_class'] ? ' class="' . esc($options['container_class']) . '"' : '';
            $containerAttr .= $options['container_id'] ? ' id="' . esc($options['container_id']) . '"' : '';
            $html = "<{$options['container']}{$containerAttr}>{$html}</{$options['container']}>";
        }
        
        return $html;
    }

    /**
     * Render menu items recursively
     */
    private static function renderItems(array $items, array $options, int $depth): string
    {
        if ($options['depth'] > 0 && $depth >= $options['depth']) {
            return '';
        }
        
        $html = '';
        
        foreach ($items as $item) {
            $url = self::getItemUrl($item);
            $hasChildren = !empty($item['children']);
            
            $classes = [$options['item_class']];
            if ($hasChildren) {
                $classes[] = 'has-children';
            }
            if ($item['css_class']) {
                $classes[] = $item['css_class'];
            }
            
            $itemAttr = ' class="' . esc(implode(' ', $classes)) . '"';
            
            $linkAttr = $options['link_class'] ? ' class="' . esc($options['link_class']) . '"' : '';
            $linkAttr .= ' href="' . esc($url) . '"';
            if ($item['target'] && $item['target'] !== '_self') {
                $linkAttr .= ' target="' . esc($item['target']) . '"';
                if ($item['target'] === '_blank') {
                    $linkAttr .= ' rel="noopener noreferrer"';
                }
            }
            
            $html .= "<li{$itemAttr}>";
            $html .= $options['before'];
            $html .= "<a{$linkAttr}>";
            $html .= $options['link_before'];
            $html .= esc($item['title']);
            $html .= $options['link_after'];
            $html .= '</a>';
            $html .= $options['after'];
            
            if ($hasChildren) {
                $submenuAttr = $options['submenu_class'] ? ' class="' . esc($options['submenu_class']) . '"' : '';
                $html .= "<ul{$submenuAttr}>";
                $html .= self::renderItems($item['children'], $options, $depth + 1);
                $html .= '</ul>';
            }
            
            $html .= '</li>';
        }
        
        return $html;
    }

    /**
     * Get available pages for menu
     */
    public static function getAvailablePages(): array
    {
        $postsTable = Database::table('posts');
        return Database::query(
            "SELECT id, title, slug FROM {$postsTable} WHERE post_type = 'page' AND status = 'published' ORDER BY title ASC"
        );
    }

    /**
     * Get available posts for menu
     */
    public static function getAvailablePosts(): array
    {
        $postsTable = Database::table('posts');
        return Database::query(
            "SELECT id, title, slug FROM {$postsTable} WHERE post_type = 'post' AND status = 'published' ORDER BY title ASC LIMIT 50"
        );
    }

    /**
     * Get available categories for menu
     */
    public static function getAvailableCategories(): array
    {
        // For now, return empty - categories system would need to be implemented
        return [];
    }

    /**
     * Get available custom post types for menu (individual posts)
     */
    public static function getAvailablePostTypes(): array
    {
        $types = [];
        $allTypes = Post::getTypes();
        $postsTable = Database::table('posts');
        
        foreach ($allTypes as $slug => $config) {
            // Skip built-in types
            if (in_array($slug, ['post', 'page'])) continue;
            
            if ($config['public'] ?? true) {
                // Get published posts of this type
                $posts = Database::query(
                    "SELECT id, title, slug FROM {$postsTable} WHERE post_type = ? AND status = 'published' ORDER BY title ASC LIMIT 50",
                    [$slug]
                );
                
                if (!empty($posts)) {
                    $types[] = [
                        'slug' => $slug,
                        'name' => $config['label'] ?? ucfirst($slug),
                        'singular' => $config['singular'] ?? ucfirst($slug),
                        'posts' => $posts,
                    ];
                }
            }
        }
        
        return $types;
    }

    /**
     * Check if item already exists in menu
     */
    public static function itemExists(int $menuId, string $type, $objectId = null, ?string $url = null): bool
    {
        $table = Database::table('menu_items');
        
        if ($type === 'custom') {
            // Custom links can be duplicated, so always allow
            return false;
        }
        
        if ($objectId) {
            $exists = Database::queryOne(
                "SELECT id FROM {$table} WHERE menu_id = ? AND type = ? AND object_id = ?",
                [$menuId, $type, $objectId]
            );
            return $exists !== null;
        }
        
        return false;
    }

    /**
     * Duplicate a menu
     */
    public static function duplicate(int $id): ?int
    {
        $menu = self::find($id);
        if (!$menu) return null;
        
        // Create new menu
        $newId = self::create([
            'name' => $menu['name'] . ' (Copy)',
            'location' => null,
        ]);
        
        // Copy items
        $items = self::getItemsFlat($id);
        $idMap = [];
        
        foreach ($items as $item) {
            $oldId = $item['id'];
            $newItemId = self::addItem($newId, [
                'parent_id' => 0, // Will be fixed in second pass
                'title' => $item['title'],
                'type' => $item['type'],
                'object_id' => $item['object_id'],
                'url' => $item['url'],
                'target' => $item['target'],
                'css_class' => $item['css_class'],
            ]);
            $idMap[$oldId] = $newItemId;
        }
        
        // Fix parent IDs
        foreach ($items as $item) {
            if ($item['parent_id'] > 0 && isset($idMap[$item['parent_id']])) {
                self::updateItem($idMap[$item['id']], [
                    'parent_id' => $idMap[$item['parent_id']],
                ]);
            }
        }
        
        return $newId;
    }
}
