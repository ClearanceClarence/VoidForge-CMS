<?php
/**
 * Post Management (Posts, Pages, and Custom Post Types)
 */

defined('CMS_ROOT') or die('Direct access not allowed');

class Post
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_TRASH = 'trash';

    public const STATUS_LABELS = [
        'draft' => 'Draft',
        'published' => 'Published',
        'trash' => 'Trash',
    ];

    // Registered post types
    /** @var array */
    private static $postTypes = [];

    /**
     * Initialize default post types and load custom ones from database
     */
    public static function init(): void
    {
        // Register default post types
        self::registerType('post', [
            'label' => 'Posts',
            'singular' => 'Post',
            'icon' => 'file-text',
            'supports' => ['title', 'editor', 'excerpt', 'author', 'featured_image'],
            'has_archive' => true,
            'hierarchical' => false,
        ]);

        self::registerType('page', [
            'label' => 'Pages',
            'singular' => 'Page',
            'icon' => 'file',
            'supports' => ['title', 'editor', 'excerpt', 'featured_image', 'page_attributes'],
            'has_archive' => false,
            'hierarchical' => true,
        ]);
        
        // Load custom post types from database
        self::loadCustomPostTypes();
    }
    
    /**
     * Load custom post types from database options
     */
    private static function loadCustomPostTypes(): void
    {
        try {
            $customTypes = getOption('custom_post_types', []);
            
            if (!is_array($customTypes)) {
                return;
            }
            
            foreach ($customTypes as $slug => $config) {
                if (empty($slug) || isset(self::$postTypes[$slug])) {
                    continue; // Skip if already registered (built-in types)
                }
                
                self::registerType($slug, [
                    'label' => $config['label_plural'] ?? ucfirst($slug) . 's',
                    'singular' => $config['label_singular'] ?? ucfirst($slug),
                    'icon' => $config['icon'] ?? 'file',
                    'supports' => $config['supports'] ?? ['title', 'editor'],
                    'has_archive' => $config['has_archive'] ?? true,
                    'hierarchical' => $config['hierarchical'] ?? false,
                    'public' => $config['public'] ?? true,
                    'fields' => $config['fields'] ?? [], // Custom fields
                ]);
            }
        } catch (Exception $e) {
            // Silently fail if database not ready
        }
    }
    
    /**
     * Get custom fields for a post type
     */
    public static function getCustomFields(string $type): array
    {
        $postType = self::getType($type);
        return $postType['fields'] ?? [];
    }

    /**
     * Register a custom post type
     */
    public static function registerType(string $type, array $args): void
    {
        $defaults = [
            'label' => ucfirst($type) . 's',
            'singular' => ucfirst($type),
            'icon' => 'file',
            'supports' => ['title', 'editor'],
            'has_archive' => true,
            'hierarchical' => false,
            'public' => true,
        ];

        self::$postTypes[$type] = array_merge($defaults, $args);
    }

    /**
     * Get all registered post types
     */
    public static function getTypes(): array
    {
        return self::$postTypes;
    }

    /**
     * Get a specific post type
     */
    public static function getType(string $type): ?array
    {
        return self::$postTypes[$type] ?? null;
    }

    /**
     * Check if post type supports a feature
     */
    public static function typeSupports(string $type, string $feature): bool
    {
        $postType = self::getType($type);
        return $postType && in_array($feature, $postType['supports']);
    }

    /**
     * Find a post by ID
     * @return array|null
     */
    public static function find(int $id)
    {
        $table = Database::table('posts');
        $post = Database::queryOne("SELECT * FROM {$table} WHERE id = ?", [$id]);
        
        if ($post) {
            $post['meta'] = self::getMeta($id);
        }
        
        return $post;
    }

    /**
     * Find a post by slug and type
     * @return array|null
     */
    public static function findBySlug(string $slug, string $type = 'post')
    {
        $table = Database::table('posts');
        $post = Database::queryOne(
            "SELECT * FROM {$table} WHERE slug = ? AND post_type = ?",
            [$slug, $type]
        );
        
        if ($post) {
            $post['meta'] = self::getMeta($post['id']);
        }
        
        return $post;
    }

    /**
     * Query posts with filters
     */
    public static function query(array $args = []): array
    {
        $defaults = [
            'post_type' => 'post',
            'status' => null,
            'author' => null,
            'parent' => null,
            'search' => null,
            'orderby' => 'created_at',
            'order' => 'DESC',
            'limit' => null,
            'offset' => 0,
        ];

        $args = array_merge($defaults, $args);
        $where = ['1=1'];
        $params = [];

        // Post type
        if ($args['post_type']) {
            $where[] = 'post_type = ?';
            $params[] = $args['post_type'];
        }

        // Status
        if ($args['status']) {
            if (is_array($args['status'])) {
                $placeholders = implode(',', array_fill(0, count($args['status']), '?'));
                $where[] = "status IN ({$placeholders})";
                $params = array_merge($params, $args['status']);
            } else {
                $where[] = 'status = ?';
                $params[] = $args['status'];
            }
        } else {
            // Exclude trash by default
            $where[] = "status != 'trash'";
        }

        // Author
        if ($args['author']) {
            $where[] = 'author_id = ?';
            $params[] = $args['author'];
        }

        // Parent
        if ($args['parent'] !== null) {
            $where[] = 'parent_id = ?';
            $params[] = $args['parent'];
        }

        // Search
        if ($args['search']) {
            $where[] = '(title LIKE ? OR content LIKE ?)';
            $searchTerm = '%' . $args['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $whereClause = implode(' AND ', $where);
        $orderClause = $args['orderby'] . ' ' . $args['order'];
        $table = Database::table('posts');

        $sql = "SELECT * FROM {$table} WHERE {$whereClause} ORDER BY {$orderClause}";

        if ($args['limit']) {
            $sql .= " LIMIT {$args['limit']} OFFSET {$args['offset']}";
        }

        return Database::query($sql, $params);
    }

    /**
     * Count posts with filters
     */
    public static function count(array $args = []): int
    {
        $defaults = [
            'post_type' => 'post',
            'status' => null,
            'author' => null,
        ];

        $args = array_merge($defaults, $args);
        $where = ['1=1'];
        $params = [];

        if ($args['post_type']) {
            $where[] = 'post_type = ?';
            $params[] = $args['post_type'];
        }

        if ($args['status']) {
            if (is_array($args['status'])) {
                $placeholders = implode(',', array_fill(0, count($args['status']), '?'));
                $where[] = "status IN ({$placeholders})";
                $params = array_merge($params, $args['status']);
            } else {
                $where[] = 'status = ?';
                $params[] = $args['status'];
            }
        }

        if ($args['author']) {
            $where[] = 'author_id = ?';
            $params[] = $args['author'];
        }

        $whereClause = implode(' AND ', $where);
        $table = Database::table('posts');
        return (int) Database::queryValue(
            "SELECT COUNT(*) FROM {$table} WHERE {$whereClause}",
            $params
        );
    }

    /**
     * Create a new post
     */
    public static function create(array $data): int
    {
        // Handle slug - use provided slug or generate from title
        if (!empty($data['slug'])) {
            $slug = slugify($data['slug']);
        } else {
            $slug = slugify($data['title'] ?? 'untitled');
        }
        $slug = uniqueSlug($slug, $data['post_type'] ?? 'post');

        $id = Database::insert(Database::table('posts'), [
            'post_type' => $data['post_type'] ?? 'post',
            'title' => $data['title'] ?? '',
            'slug' => $slug,
            'content' => $data['content'] ?? '',
            'excerpt' => $data['excerpt'] ?? '',
            'status' => $data['status'] ?? self::STATUS_DRAFT,
            'author_id' => $data['author_id'] ?? User::current()['id'],
            'parent_id' => $data['parent_id'] ?? null,
            'menu_order' => $data['menu_order'] ?? 0,
            'featured_image_id' => $data['featured_image_id'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'published_at' => $data['status'] === self::STATUS_PUBLISHED ? date('Y-m-d H:i:s') : null,
        ]);

        // Save meta
        if (!empty($data['meta']) && is_array($data['meta'])) {
            foreach ($data['meta'] as $key => $value) {
                self::setMeta($id, $key, $value);
            }
        }

        return $id;
    }

    /**
     * Update a post
     */
    public static function update(int $id, array $data): bool
    {
        $post = self::find($id);
        if (!$post) {
            return false;
        }

        $updateData = [
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if (isset($data['title'])) {
            $updateData['title'] = $data['title'];
        }
        
        // Handle slug - can be set manually or auto-generated from title
        if (isset($data['slug']) && !empty(trim($data['slug']))) {
            $newSlug = slugify($data['slug']);
            // Only uniquify if the slug actually changed
            if ($newSlug !== $post['slug']) {
                $updateData['slug'] = uniqueSlug($newSlug, $post['post_type'], $id);
            }
        } elseif (isset($data['title']) && !$post['slug']) {
            $updateData['slug'] = uniqueSlug(slugify($data['title']), $post['post_type'], $id);
        }
        if (isset($data['content'])) {
            $updateData['content'] = $data['content'];
        }
        if (isset($data['excerpt'])) {
            $updateData['excerpt'] = $data['excerpt'];
        }
        if (isset($data['status'])) {
            $updateData['status'] = $data['status'];
            if ($data['status'] === self::STATUS_PUBLISHED && !$post['published_at']) {
                $updateData['published_at'] = date('Y-m-d H:i:s');
            }
        }
        if (isset($data['parent_id'])) {
            $updateData['parent_id'] = $data['parent_id'];
        }
        if (isset($data['menu_order'])) {
            $updateData['menu_order'] = $data['menu_order'];
        }
        if (isset($data['featured_image_id'])) {
            $updateData['featured_image_id'] = $data['featured_image_id'];
        }

        $result = Database::update(Database::table('posts'), $updateData, 'id = ?', [$id]) >= 0;

        // Update meta
        if (!empty($data['meta']) && is_array($data['meta'])) {
            foreach ($data['meta'] as $key => $value) {
                self::setMeta($id, $key, $value);
            }
        }

        return $result;
    }

    /**
     * Delete a post (move to trash or permanent delete)
     */
    public static function delete(int $id, bool $permanent = false): bool
    {
        if ($permanent) {
            // Delete meta first
            Database::delete(Database::table('postmeta'), 'post_id = ?', [$id]);
            // Delete revisions
            self::deleteRevisions($id);
            return Database::delete(Database::table('posts'), 'id = ?', [$id]) > 0;
        }

        return self::update($id, ['status' => self::STATUS_TRASH]);
    }

    /**
     * Restore from trash
     */
    public static function restore(int $id): bool
    {
        return self::update($id, ['status' => self::STATUS_DRAFT]);
    }

    /**
     * Get post meta
     * @return mixed
     */
    public static function getMeta(int $postId, $key = null)
    {
        $table = Database::table('postmeta');
        if ($key) {
            $meta = Database::queryOne(
                "SELECT meta_value FROM {$table} WHERE post_id = ? AND meta_key = ?",
                [$postId, $key]
            );
            
            if ($meta) {
                $decoded = json_decode($meta['meta_value'], true);
                return $decoded !== null ? $decoded : $meta['meta_value'];
            }
            
            return null;
        }

        $metas = Database::query(
            "SELECT meta_key, meta_value FROM {$table} WHERE post_id = ?",
            [$postId]
        );

        $result = [];
        foreach ($metas as $meta) {
            $decoded = json_decode($meta['meta_value'], true);
            $result[$meta['meta_key']] = $decoded !== null ? $decoded : $meta['meta_value'];
        }

        return $result;
    }

    /**
     * Set post meta
     */
    public static function setMeta(int $postId, string $key, $value): void
    {
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
        }

        $table = Database::table('postmeta');
        $existing = Database::queryOne(
            "SELECT id FROM {$table} WHERE post_id = ? AND meta_key = ?",
            [$postId, $key]
        );

        if ($existing) {
            Database::update(Database::table('postmeta'), ['meta_value' => $value], 'id = ?', [$existing['id']]);
        } else {
            Database::insert(Database::table('postmeta'), [
                'post_id' => $postId,
                'meta_key' => $key,
                'meta_value' => $value,
            ]);
        }
    }

    /**
     * Delete post meta
     */
    public static function deleteMeta(int $postId, string $key): bool
    {
        return Database::delete(Database::table('postmeta'), 'post_id = ? AND meta_key = ?', [$postId, $key]) > 0;
    }

    /**
     * Get author of a post
     * @return array|null
     */
    public static function getAuthor(array $post)
    {
        return User::find($post['author_id']);
    }

    /**
     * Get featured image
     * @return array|null
     */
    public static function getFeaturedImage(array $post)
    {
        if (empty($post['featured_image_id'])) {
            return null;
        }
        return Media::find($post['featured_image_id']);
    }

    /**
     * Get parent pages for dropdown
     */
    public static function getParentOptions(string $postType, $excludeId = null): array
    {
        $table = Database::table('posts');
        $where = 'post_type = ?';
        $params = [$postType];

        if ($excludeId) {
            $where .= ' AND id != ?';
            $params[] = $excludeId;
        }

        return Database::query(
            "SELECT id, title, parent_id FROM {$table} WHERE {$where} ORDER BY menu_order, title",
            $params
        );
    }

    /**
     * Get permalink for a post
     */
    public static function permalink(array $post): string
    {
        if ($post['post_type'] === 'page') {
            return SITE_URL . '/' . $post['slug'];
        }
        return SITE_URL . '/' . $post['post_type'] . '/' . $post['slug'];
    }

    // =====================================================
    // REVISION SYSTEM
    // =====================================================

    /**
     * Get max revisions for a post type
     */
    public static function getMaxRevisions(string $postType): int
    {
        // Check custom post type settings
        $customTypes = getOption('custom_post_types', []);
        if (isset($customTypes[$postType]['max_revisions'])) {
            return (int) $customTypes[$postType]['max_revisions'];
        }
        
        // Check built-in post type settings
        $builtInSettings = getOption('revision_settings', []);
        if (isset($builtInSettings[$postType])) {
            return (int) $builtInSettings[$postType];
        }
        
        // Default: 10 revisions
        return 10;
    }

    /**
     * Create a revision of a post (call BEFORE updating)
     */
    public static function createRevision(int $postId): ?int
    {
        $post = self::find($postId);
        if (!$post) {
            return null;
        }
        
        $maxRevisions = self::getMaxRevisions($post['post_type']);
        
        // If max_revisions is 0, revisions are disabled
        if ($maxRevisions === 0) {
            return null;
        }
        
        // Get the next revision number
        $table = Database::table('post_revisions');
        $lastRevision = Database::queryValue(
            "SELECT MAX(revision_number) FROM {$table} WHERE post_id = ?",
            [$postId]
        );
        $revisionNumber = ($lastRevision ?? 0) + 1;
        
        // Get current meta data
        $meta = self::getMeta($postId);
        
        // Create the revision
        $revisionId = Database::insert($table, [
            'post_id' => $postId,
            'post_type' => $post['post_type'],
            'title' => $post['title'],
            'slug' => $post['slug'],
            'content' => $post['content'],
            'excerpt' => $post['excerpt'] ?? '',
            'meta_data' => json_encode($meta),
            'author_id' => User::current()['id'] ?? $post['author_id'],
            'revision_number' => $revisionNumber,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        
        // Cleanup old revisions if over limit
        self::cleanupRevisions($postId, $maxRevisions);
        
        return $revisionId;
    }

    /**
     * Get all revisions for a post
     */
    public static function getRevisions(int $postId, int $limit = 50): array
    {
        $table = Database::table('post_revisions');
        $usersTable = Database::table('users');
        
        return Database::query(
            "SELECT r.*, u.display_name as author_name 
             FROM {$table} r 
             LEFT JOIN {$usersTable} u ON r.author_id = u.id 
             WHERE r.post_id = ? 
             ORDER BY r.revision_number DESC 
             LIMIT ?",
            [$postId, $limit]
        );
    }

    /**
     * Get a specific revision
     */
    public static function getRevision(int $revisionId): ?array
    {
        $table = Database::table('post_revisions');
        return Database::queryOne("SELECT * FROM {$table} WHERE id = ?", [$revisionId]);
    }

    /**
     * Get revision count for a post
     */
    public static function getRevisionCount(int $postId): int
    {
        $table = Database::table('post_revisions');
        return (int) Database::queryValue(
            "SELECT COUNT(*) FROM {$table} WHERE post_id = ?",
            [$postId]
        );
    }

    /**
     * Restore a revision to the current post
     */
    public static function restoreRevision(int $revisionId): bool
    {
        $revision = self::getRevision($revisionId);
        if (!$revision) {
            return false;
        }
        
        $postId = $revision['post_id'];
        $post = self::find($postId);
        if (!$post) {
            return false;
        }
        
        // Create a revision of current state before restoring
        self::createRevision($postId);
        
        // Restore the post content
        $updateData = [
            'title' => $revision['title'],
            'content' => $revision['content'],
            'excerpt' => $revision['excerpt'],
        ];
        
        Database::update(Database::table('posts'), $updateData, 'id = ?', [$postId]);
        
        // Restore meta data
        $metaData = json_decode($revision['meta_data'], true);
        if (is_array($metaData)) {
            foreach ($metaData as $key => $value) {
                self::setMeta($postId, $key, $value);
            }
        }
        
        return true;
    }

    /**
     * Delete old revisions beyond the max limit
     */
    public static function cleanupRevisions(int $postId, int $maxRevisions): void
    {
        if ($maxRevisions <= 0) {
            return;
        }
        
        $table = Database::table('post_revisions');
        
        // Get count of revisions
        $count = Database::queryValue(
            "SELECT COUNT(*) FROM {$table} WHERE post_id = ?",
            [$postId]
        );
        
        if ($count > $maxRevisions) {
            // Delete oldest revisions beyond the limit
            $toDelete = $count - $maxRevisions;
            Database::execute(
                "DELETE FROM {$table} WHERE post_id = ? ORDER BY revision_number ASC LIMIT ?",
                [$postId, $toDelete]
            );
        }
    }

    /**
     * Delete all revisions for a post
     */
    public static function deleteRevisions(int $postId): int
    {
        $table = Database::table('post_revisions');
        return Database::delete($table, 'post_id = ?', [$postId]);
    }

    /**
     * Compare two revisions or revision with current post
     */
    public static function compareRevisions(int $revisionId, ?int $compareToId = null): array
    {
        $revision = self::getRevision($revisionId);
        if (!$revision) {
            return [];
        }
        
        if ($compareToId) {
            $compareTo = self::getRevision($compareToId);
        } else {
            // Compare to current post
            $compareTo = self::find($revision['post_id']);
        }
        
        if (!$compareTo) {
            return [];
        }
        
        return [
            'title' => [
                'from' => $revision['title'],
                'to' => $compareTo['title'],
                'changed' => $revision['title'] !== $compareTo['title'],
            ],
            'content' => [
                'from' => $revision['content'],
                'to' => $compareTo['content'],
                'changed' => $revision['content'] !== $compareTo['content'],
            ],
            'excerpt' => [
                'from' => $revision['excerpt'],
                'to' => $compareTo['excerpt'] ?? '',
                'changed' => $revision['excerpt'] !== ($compareTo['excerpt'] ?? ''),
            ],
        ];
    }
}
