<?php
/**
 * Taxonomy System - VoidForge CMS
 * Categories, Tags, and Custom Taxonomies
 */

defined('CMS_ROOT') or die('Direct access not allowed');

class Taxonomy
{
    /** @var array Registered taxonomies */
    private static array $taxonomies = [];
    
    /** @var bool Initialization flag */
    private static bool $initialized = false;
    
    /** @var bool|null Tables exist cache */
    private static ?bool $_tablesExist = null;
    
    /**
     * Check if taxonomy tables exist
     */
    public static function tablesExist(): bool
    {
        if (self::$_tablesExist !== null) {
            return self::$_tablesExist;
        }
        
        try {
            $table = Database::table('taxonomies');
            $pdo = Database::getInstance();
            $check = $pdo->query("SHOW TABLES LIKE '{$table}'");
            self::$_tablesExist = $check->rowCount() > 0;
        } catch (Exception $e) {
            self::$_tablesExist = false;
        }
        
        return self::$_tablesExist;
    }

    /**
     * Initialize default taxonomies and load custom ones
     */
    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }
        
        self::$initialized = true;
        
        // Register built-in taxonomies
        self::register('category', [
            'label' => 'Categories',
            'singular' => 'Category',
            'hierarchical' => true,
            'post_types' => ['post'],
            'builtin' => true,
        ]);
        
        self::register('tag', [
            'label' => 'Tags',
            'singular' => 'Tag',
            'hierarchical' => false,
            'post_types' => ['post'],
            'builtin' => true,
        ]);
        
        // Load custom taxonomies from database
        self::loadCustomTaxonomies();
    }
    
    /**
     * Load custom taxonomies from database
     */
    private static function loadCustomTaxonomies(): void
    {
        try {
            $table = Database::table('taxonomies');
            
            // Check if table exists first
            $pdo = Database::getInstance();
            $check = $pdo->query("SHOW TABLES LIKE '{$table}'");
            if ($check->rowCount() === 0) {
                return; // Table doesn't exist yet
            }
            
            $taxonomies = Database::query("SELECT * FROM {$table} ORDER BY name ASC");
            
            foreach ($taxonomies as $tax) {
                $postTypes = json_decode($tax['post_types'] ?? '[]', true) ?: [];
                
                self::register($tax['slug'], [
                    'label' => $tax['name'],
                    'singular' => $tax['singular'] ?? rtrim($tax['name'], 's'),
                    'description' => $tax['description'] ?? '',
                    'hierarchical' => (bool)$tax['hierarchical'],
                    'post_types' => $postTypes,
                    'builtin' => false,
                    'db_id' => (int)$tax['id'],
                ]);
            }
        } catch (Exception $e) {
            // Database might not be ready
        }
    }
    
    /**
     * Register a taxonomy
     */
    public static function register(string $slug, array $args): void
    {
        $defaults = [
            'label' => ucfirst($slug),
            'singular' => ucfirst($slug),
            'description' => '',
            'hierarchical' => false,
            'post_types' => [],
            'builtin' => false,
            'db_id' => null,
        ];
        
        self::$taxonomies[$slug] = array_merge($defaults, $args);
    }
    
    /**
     * Get all registered taxonomies
     */
    public static function getAll(): array
    {
        return self::$taxonomies;
    }
    
    /**
     * Get a specific taxonomy
     */
    public static function get(string $slug): ?array
    {
        return self::$taxonomies[$slug] ?? null;
    }
    
    /**
     * Get taxonomies for a specific post type
     */
    public static function getForPostType(string $postType): array
    {
        $result = [];
        foreach (self::$taxonomies as $slug => $tax) {
            if (in_array($postType, $tax['post_types'])) {
                $result[$slug] = $tax;
            }
        }
        return $result;
    }
    
    /**
     * Check if taxonomy exists
     */
    public static function exists(string $slug): bool
    {
        return isset(self::$taxonomies[$slug]);
    }
    
    // =========================================================================
    // TAXONOMY CRUD (for custom taxonomies)
    // =========================================================================
    
    /**
     * Create a custom taxonomy in database
     */
    public static function create(array $data): int
    {
        $table = Database::table('taxonomies');
        
        $slug = self::generateSlug($data['name'], $data['slug'] ?? '');
        
        Database::execute(
            "INSERT INTO {$table} (name, slug, singular, description, hierarchical, post_types, created_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())",
            [
                $data['name'],
                $slug,
                $data['singular'] ?? rtrim($data['name'], 's'),
                $data['description'] ?? '',
                $data['hierarchical'] ? 1 : 0,
                json_encode($data['post_types'] ?? []),
            ]
        );
        
        $id = (int)Database::lastInsertId();
        
        // Register immediately
        self::register($slug, [
            'label' => $data['name'],
            'singular' => $data['singular'] ?? rtrim($data['name'], 's'),
            'description' => $data['description'] ?? '',
            'hierarchical' => (bool)$data['hierarchical'],
            'post_types' => $data['post_types'] ?? [],
            'builtin' => false,
            'db_id' => $id,
        ]);
        
        return $id;
    }
    
    /**
     * Update a custom taxonomy
     */
    public static function update(int $id, array $data): bool
    {
        $table = Database::table('taxonomies');
        
        $fields = [];
        $params = [];
        
        if (isset($data['name'])) {
            $fields[] = 'name = ?';
            $params[] = $data['name'];
        }
        if (isset($data['singular'])) {
            $fields[] = 'singular = ?';
            $params[] = $data['singular'];
        }
        if (isset($data['description'])) {
            $fields[] = 'description = ?';
            $params[] = $data['description'];
        }
        if (isset($data['hierarchical'])) {
            $fields[] = 'hierarchical = ?';
            $params[] = $data['hierarchical'] ? 1 : 0;
        }
        if (isset($data['post_types'])) {
            $fields[] = 'post_types = ?';
            $params[] = json_encode($data['post_types']);
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $params[] = $id;
        
        Database::execute(
            "UPDATE {$table} SET " . implode(', ', $fields) . " WHERE id = ?",
            $params
        );
        
        return true;
    }
    
    /**
     * Delete a custom taxonomy and all its terms
     */
    public static function delete(int $id): bool
    {
        $table = Database::table('taxonomies');
        $termsTable = Database::table('terms');
        $relTable = Database::table('term_relationships');
        
        // Get taxonomy slug first
        $tax = Database::queryOne("SELECT slug FROM {$table} WHERE id = ?", [$id]);
        if (!$tax) {
            return false;
        }
        
        // Get all term IDs for this taxonomy
        $terms = Database::query("SELECT id FROM {$termsTable} WHERE taxonomy = ?", [$tax['slug']]);
        $termIds = array_column($terms, 'id');
        
        // Delete relationships
        if (!empty($termIds)) {
            $placeholders = implode(',', array_fill(0, count($termIds), '?'));
            Database::execute("DELETE FROM {$relTable} WHERE term_id IN ({$placeholders})", $termIds);
        }
        
        // Delete terms
        Database::execute("DELETE FROM {$termsTable} WHERE taxonomy = ?", [$tax['slug']]);
        
        // Delete taxonomy
        Database::execute("DELETE FROM {$table} WHERE id = ?", [$id]);
        
        // Unregister
        unset(self::$taxonomies[$tax['slug']]);
        
        return true;
    }
    
    /**
     * Find taxonomy by ID
     */
    public static function find(int $id): ?array
    {
        $table = Database::table('taxonomies');
        $row = Database::queryOne("SELECT * FROM {$table} WHERE id = ?", [$id]);
        
        if ($row) {
            $row['post_types'] = json_decode($row['post_types'] ?? '[]', true) ?: [];
        }
        
        return $row;
    }
    
    /**
     * Get all custom taxonomies from database
     */
    public static function getAllCustom(): array
    {
        if (!self::tablesExist()) {
            return [];
        }
        
        try {
            $table = Database::table('taxonomies');
            $taxonomies = Database::query("SELECT * FROM {$table} ORDER BY name ASC");
            
            foreach ($taxonomies as &$tax) {
                $tax['post_types'] = json_decode($tax['post_types'] ?? '[]', true) ?: [];
            }
            
            return $taxonomies;
        } catch (Exception $e) {
            return [];
        }
    }
    
    // =========================================================================
    // TERM CRUD
    // =========================================================================
    
    /**
     * Create a term
     */
    public static function createTerm(string $taxonomy, array $data): int
    {
        // Allow filtering of term data before creation
        $data = safe_apply_filters('pre_insert_term', $data, $taxonomy);
        
        $table = Database::table('terms');
        
        $slug = self::generateTermSlug($taxonomy, $data['name'], $data['slug'] ?? '');
        
        Database::execute(
            "INSERT INTO {$table} (taxonomy, name, slug, description, parent_id, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())",
            [
                $taxonomy,
                $data['name'],
                $slug,
                $data['description'] ?? '',
                $data['parent_id'] ?? 0,
            ]
        );
        
        $id = (int)Database::lastInsertId();
        
        // Fire term created action
        safe_do_action('term_inserted', $id, $taxonomy, $data);
        
        return $id;
    }
    
    /**
     * Update a term
     */
    public static function updateTerm(int $id, array $data): bool
    {
        $term = self::findTerm($id);
        if (!$term) {
            return false;
        }
        
        // Allow filtering of term data before update
        $data = safe_apply_filters('pre_update_term', $data, $id, $term);
        
        $table = Database::table('terms');
        
        $fields = [];
        $params = [];
        
        if (isset($data['name'])) {
            $fields[] = 'name = ?';
            $params[] = $data['name'];
        }
        if (isset($data['slug'])) {
            $fields[] = 'slug = ?';
            $params[] = $data['slug'];
        }
        if (isset($data['description'])) {
            $fields[] = 'description = ?';
            $params[] = $data['description'];
        }
        if (array_key_exists('parent_id', $data)) {
            $fields[] = 'parent_id = ?';
            $params[] = $data['parent_id'] ?? 0;
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $params[] = $id;
        
        Database::execute(
            "UPDATE {$table} SET " . implode(', ', $fields) . " WHERE id = ?",
            $params
        );
        
        // Fire term updated action
        safe_do_action('term_updated', $id, $data, $term);
        
        return true;
    }
    
    /**
     * Delete a term and its relationships
     */
    public static function deleteTerm(int $id): bool
    {
        $table = Database::table('terms');
        $relTable = Database::table('term_relationships');
        
        // Get term to find children
        $term = self::findTerm($id);
        if (!$term) {
            return false;
        }
        
        // Fire pre-delete action
        safe_do_action('pre_delete_term', $id, $term);
        
        // Update children to have no parent
        Database::execute("UPDATE {$table} SET parent_id = 0 WHERE parent_id = ?", [$id]);
        
        // Delete relationships
        Database::execute("DELETE FROM {$relTable} WHERE term_id = ?", [$id]);
        
        // Delete term
        Database::execute("DELETE FROM {$table} WHERE id = ?", [$id]);
        
        // Fire deleted action
        safe_do_action('term_deleted', $id, $term);
        
        return true;
    }
    
    /**
     * Find a term by ID
     */
    public static function findTerm(int $id): ?array
    {
        $table = Database::table('terms');
        return Database::queryOne("SELECT * FROM {$table} WHERE id = ?", [$id]);
    }
    
    /**
     * Find a term by slug
     */
    public static function findTermBySlug(string $taxonomy, string $slug): ?array
    {
        $table = Database::table('terms');
        return Database::queryOne(
            "SELECT * FROM {$table} WHERE taxonomy = ? AND slug = ?",
            [$taxonomy, $slug]
        );
    }
    
    /**
     * Get all terms for a taxonomy
     */
    public static function getTerms(string $taxonomy, array $args = []): array
    {
        try {
            $table = Database::table('terms');
            
            // Check if table exists
            $pdo = Database::getInstance();
            $check = $pdo->query("SHOW TABLES LIKE '{$table}'");
            if ($check->rowCount() === 0) {
                return []; // Table doesn't exist yet
            }
            
            $orderBy = $args['orderby'] ?? 'name';
            $order = strtoupper($args['order'] ?? 'ASC');
            $order = in_array($order, ['ASC', 'DESC']) ? $order : 'ASC';
            $hideEmpty = $args['hide_empty'] ?? false;
            $parent = $args['parent'] ?? null;
            
            $allowedOrderBy = ['name', 'slug', 'count', 'id', 'created_at'];
            $orderBy = in_array($orderBy, $allowedOrderBy) ? $orderBy : 'name';
            
            $sql = "SELECT * FROM {$table} WHERE taxonomy = ?";
            $params = [$taxonomy];
            
            if ($parent !== null) {
                $sql .= " AND parent_id = ?";
                $params[] = $parent;
            }
            
            if ($hideEmpty) {
                $sql .= " AND count > 0";
            }
            
            $sql .= " ORDER BY {$orderBy} {$order}";
            
            return Database::query($sql, $params);
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Get terms as hierarchical tree (for categories)
     */
    public static function getTermsTree(string $taxonomy, int $parentId = 0): array
    {
        $terms = self::getTerms($taxonomy, ['parent' => $parentId, 'orderby' => 'name']);
        
        foreach ($terms as &$term) {
            $term['children'] = self::getTermsTree($taxonomy, (int)$term['id']);
        }
        
        return $terms;
    }
    
    /**
     * Get term count for a taxonomy
     */
    public static function getTermCount(string $taxonomy): int
    {
        if (!self::tablesExist()) {
            return 0;
        }
        
        try {
            $table = Database::table('terms');
            $result = Database::queryOne(
                "SELECT COUNT(*) as cnt FROM {$table} WHERE taxonomy = ?",
                [$taxonomy]
            );
            return (int)($result['cnt'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }
    
    // =========================================================================
    // POST-TERM RELATIONSHIPS
    // =========================================================================
    
    /**
     * Set terms for a post (replaces existing)
     */
    public static function setPostTerms(int $postId, string $taxonomy, array $termIds): void
    {
        if (!self::tablesExist()) {
            return;
        }
        
        $relTable = Database::table('term_relationships');
        $termsTable = Database::table('terms');
        
        // Get current term IDs for this post and taxonomy
        $current = self::getPostTermIds($postId, $taxonomy);
        
        // Delete old relationships for this taxonomy
        if (!empty($current)) {
            $placeholders = implode(',', array_fill(0, count($current), '?'));
            Database::execute(
                "DELETE FROM {$relTable} WHERE post_id = ? AND term_id IN ({$placeholders})",
                array_merge([$postId], $current)
            );
        }
        
        // Add new relationships
        foreach ($termIds as $termId) {
            $termId = (int)$termId;
            if ($termId > 0) {
                Database::execute(
                    "INSERT INTO {$relTable} (post_id, term_id) VALUES (?, ?)",
                    [$postId, $termId]
                );
            }
        }
        
        // Update term counts
        self::updateTermCounts($taxonomy);
        
        // Fire action
        safe_do_action('post_terms_set', $postId, $taxonomy, $termIds, $current);
    }
    
    /**
     * Add terms to a post (without removing existing)
     */
    public static function addPostTerms(int $postId, array $termIds): void
    {
        $relTable = Database::table('term_relationships');
        
        $existing = self::getPostTermIds($postId);
        
        foreach ($termIds as $termId) {
            $termId = (int)$termId;
            if ($termId > 0 && !in_array($termId, $existing)) {
                Database::execute(
                    "INSERT INTO {$relTable} (post_id, term_id) VALUES (?, ?)",
                    [$postId, $termId]
                );
            }
        }
    }
    
    /**
     * Remove terms from a post
     */
    public static function removePostTerms(int $postId, array $termIds): void
    {
        $relTable = Database::table('term_relationships');
        
        if (empty($termIds)) {
            return;
        }
        
        $placeholders = implode(',', array_fill(0, count($termIds), '?'));
        Database::execute(
            "DELETE FROM {$relTable} WHERE post_id = ? AND term_id IN ({$placeholders})",
            array_merge([$postId], $termIds)
        );
    }
    
    /**
     * Get terms for a post
     */
    public static function getPostTerms(int $postId, string $taxonomy = null): array
    {
        try {
            $termsTable = Database::table('terms');
            $relTable = Database::table('term_relationships');
            
            // Check if tables exist
            $pdo = Database::getInstance();
            $check = $pdo->query("SHOW TABLES LIKE '{$termsTable}'");
            if ($check->rowCount() === 0) {
                return []; // Table doesn't exist yet
            }
            
            $sql = "SELECT t.* FROM {$termsTable} t
                    JOIN {$relTable} r ON t.id = r.term_id
                    WHERE r.post_id = ?";
            $params = [$postId];
            
            if ($taxonomy) {
                $sql .= " AND t.taxonomy = ?";
                $params[] = $taxonomy;
            }
            
            $sql .= " ORDER BY t.name ASC";
            
            return Database::query($sql, $params);
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Get term IDs for a post
     */
    public static function getPostTermIds(int $postId, string $taxonomy = null): array
    {
        $terms = self::getPostTerms($postId, $taxonomy);
        return array_column($terms, 'id');
    }
    
    /**
     * Check if post has a specific term
     */
    public static function postHasTerm(int $postId, int $termId): bool
    {
        $relTable = Database::table('term_relationships');
        $result = Database::queryOne(
            "SELECT 1 FROM {$relTable} WHERE post_id = ? AND term_id = ?",
            [$postId, $termId]
        );
        return $result !== null;
    }
    
    /**
     * Get posts with a specific term
     */
    public static function getTermPosts(int $termId, array $args = []): array
    {
        $postsTable = Database::table('posts');
        $relTable = Database::table('term_relationships');
        
        $status = $args['status'] ?? 'published';
        $limit = $args['limit'] ?? 20;
        $offset = $args['offset'] ?? 0;
        
        $sql = "SELECT p.* FROM {$postsTable} p
                JOIN {$relTable} r ON p.id = r.post_id
                WHERE r.term_id = ? AND p.status = ?
                ORDER BY p.created_at DESC
                LIMIT ? OFFSET ?";
        
        return Database::query($sql, [$termId, $status, $limit, $offset]);
    }
    
    /**
     * Update term counts
     */
    public static function updateTermCounts(string $taxonomy = null): void
    {
        $termsTable = Database::table('terms');
        $relTable = Database::table('term_relationships');
        $postsTable = Database::table('posts');
        
        $sql = "UPDATE {$termsTable} t SET count = (
                    SELECT COUNT(DISTINCT r.post_id) 
                    FROM {$relTable} r
                    JOIN {$postsTable} p ON r.post_id = p.id
                    WHERE r.term_id = t.id AND p.status = 'published'
                )";
        
        if ($taxonomy) {
            $sql .= " WHERE t.taxonomy = ?";
            Database::execute($sql, [$taxonomy]);
        } else {
            Database::execute($sql);
        }
    }
    
    /**
     * Delete all terms for a post
     */
    public static function deletePostTerms(int $postId): void
    {
        $relTable = Database::table('term_relationships');
        Database::execute("DELETE FROM {$relTable} WHERE post_id = ?", [$postId]);
    }
    
    // =========================================================================
    // UTILITIES
    // =========================================================================
    
    /**
     * Generate unique taxonomy slug
     */
    private static function generateSlug(string $name, string $customSlug = ''): string
    {
        $table = Database::table('taxonomies');
        
        $slug = $customSlug ?: strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $name));
        $slug = trim($slug, '_');
        
        $baseSlug = $slug;
        $counter = 1;
        
        while (true) {
            $existing = Database::queryOne(
                "SELECT id FROM {$table} WHERE slug = ?",
                [$slug]
            );
            
            if (!$existing && !self::exists($slug)) {
                break;
            }
            
            $slug = $baseSlug . '_' . $counter;
            $counter++;
        }
        
        return $slug;
    }
    
    /**
     * Generate unique term slug within a taxonomy
     */
    private static function generateTermSlug(string $taxonomy, string $name, string $customSlug = ''): string
    {
        $table = Database::table('terms');
        
        $slug = $customSlug ?: strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
        $slug = trim($slug, '-');
        
        $baseSlug = $slug;
        $counter = 1;
        
        while (true) {
            $existing = Database::queryOne(
                "SELECT id FROM {$table} WHERE taxonomy = ? AND slug = ?",
                [$taxonomy, $slug]
            );
            
            if (!$existing) {
                break;
            }
            
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
    
    /**
     * Get term URL
     */
    public static function getTermUrl(array $term): string
    {
        return siteUrl() . '/' . $term['taxonomy'] . '/' . $term['slug'];
    }
    
    /**
     * Get taxonomy archive URL
     */
    public static function getTaxonomyUrl(string $taxonomy): string
    {
        return siteUrl() . '/' . $taxonomy;
    }
    
    /**
     * Get breadcrumb for hierarchical term
     */
    public static function getTermBreadcrumb(int $termId): array
    {
        $breadcrumb = [];
        $term = self::findTerm($termId);
        
        while ($term) {
            array_unshift($breadcrumb, $term);
            $term = $term['parent_id'] ? self::findTerm((int)$term['parent_id']) : null;
        }
        
        return $breadcrumb;
    }
    
    /**
     * Get term depth in hierarchy
     */
    public static function getTermDepth(int $termId): int
    {
        return count(self::getTermBreadcrumb($termId)) - 1;
    }
    
    /**
     * Get all descendant term IDs
     */
    public static function getDescendantIds(int $termId): array
    {
        $term = self::findTerm($termId);
        if (!$term) {
            return [];
        }
        
        $table = Database::table('terms');
        $descendants = [];
        
        $children = Database::query(
            "SELECT id FROM {$table} WHERE parent_id = ?",
            [$termId]
        );
        
        foreach ($children as $child) {
            $descendants[] = (int)$child['id'];
            $descendants = array_merge($descendants, self::getDescendantIds((int)$child['id']));
        }
        
        return $descendants;
    }
}
