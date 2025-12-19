<?php
/**
 * Post Management (Posts, Pages, and Custom Post Types)
 */

defined('CMS_ROOT') or die('Direct access not allowed');

class Post
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_TRASH = 'trash';

    public const STATUS_LABELS = [
        'draft' => 'Draft',
        'published' => 'Published',
        'scheduled' => 'Scheduled',
        'trash' => 'Trash',
    ];
    
    /** @var int Days before trashed items are permanently deleted */
    public const TRASH_RETENTION_DAYS = 30;

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
        
        // Allow filtering of query args before execution
        $args = safe_apply_filters('pre_get_posts', $args);
        
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

        $posts = Database::query($sql, $params);
        
        // Allow filtering of query results
        $posts = safe_apply_filters('the_posts', $posts, $args);
        
        return $posts;
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
        // Allow filtering of post data before insertion
        $data = safe_apply_filters('pre_insert_post', $data);
        
        // Handle slug - use provided slug or generate from title
        if (!empty($data['slug'])) {
            $slug = slugify($data['slug']);
        } else {
            $slug = slugify($data['title'] ?? 'untitled');
        }
        $slug = uniqueSlug($slug, $data['post_type'] ?? 'post');

        $insertData = [
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
        ];
        
        $id = Database::insert(Database::table('posts'), $insertData);

        // Save meta
        if (!empty($data['meta']) && is_array($data['meta'])) {
            foreach ($data['meta'] as $key => $value) {
                self::setMeta($id, $key, $value);
            }
        }
        
        // Fire post_inserted action
        safe_do_action('post_inserted', $id, $insertData, $data);
        
        // Fire post type specific action
        safe_do_action('post_inserted_' . $insertData['post_type'], $id, $insertData);

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
        
        // Store old status for status change detection
        $oldStatus = $post['status'];
        
        // Allow filtering of update data
        $data = safe_apply_filters('pre_update_post', $data, $id, $post);

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
        
        if ($result) {
            // Fire post_updated action
            safe_do_action('post_updated', $id, $updateData, $post);
            
            // Fire post type specific action
            safe_do_action('post_updated_' . $post['post_type'], $id, $updateData, $post);
            
            // Fire status change action if status changed
            $newStatus = $updateData['status'] ?? $oldStatus;
            if ($oldStatus !== $newStatus) {
                safe_do_action('post_status_changed', $id, $newStatus, $oldStatus, $post);
                safe_do_action('post_status_' . $newStatus, $id, $oldStatus, $post);
            }
        }

        return $result;
    }

    /**
     * Delete a post (move to trash or permanent delete)
     */
    public static function delete(int $id, bool $permanent = false): bool
    {
        $post = self::find($id);
        if (!$post) {
            return false;
        }
        
        // Fire pre_delete action (can be used to prevent deletion)
        safe_do_action('pre_delete_post', $id, $post, $permanent);
        
        if ($permanent) {
            // Delete taxonomy terms
            if (class_exists('Taxonomy')) {
                try {
                    Taxonomy::deletePostTerms($id);
                } catch (Exception $e) {
                    // Taxonomy tables might not exist
                }
            }
            // Delete comments
            if (class_exists('Comment')) {
                try {
                    Comment::deleteByPost($id);
                } catch (Exception $e) {
                    // Comments table might not exist
                }
            }
            // Delete meta
            Database::delete(Database::table('postmeta'), 'post_id = ?', [$id]);
            // Delete revisions
            self::deleteRevisions($id);
            
            $result = Database::delete(Database::table('posts'), 'id = ?', [$id]) > 0;
            
            if ($result) {
                safe_do_action('post_deleted', $id, $post);
                safe_do_action('post_deleted_' . $post['post_type'], $id, $post);
            }
            
            return $result;
        }

        // Soft delete: set status to trash and record trashed_at timestamp
        $table = Database::table('posts');
        $result = false;
        
        // Check if trashed_at column exists (for backward compatibility)
        try {
            $columns = Database::query("SHOW COLUMNS FROM {$table} LIKE 'trashed_at'");
            if (!empty($columns)) {
                $result = Database::execute(
                    "UPDATE {$table} SET status = ?, trashed_at = NOW(), updated_at = NOW() WHERE id = ?",
                    [self::STATUS_TRASH, $id]
                ) > 0;
            }
        } catch (Exception $e) {
            // Fall through to simple update
        }
        
        if (!$result) {
            // Fallback for databases without trashed_at column
            $result = self::update($id, ['status' => self::STATUS_TRASH]);
        }
        
        if ($result) {
            safe_do_action('post_trashed', $id, $post);
        }
        
        return $result;
    }

    /**
     * Restore from trash
     */
    public static function restore(int $id): bool
    {
        $post = self::find($id);
        if (!$post) {
            return false;
        }
        
        $table = Database::table('posts');
        $result = false;
        
        // Check if trashed_at column exists (for backward compatibility)
        try {
            $columns = Database::query("SHOW COLUMNS FROM {$table} LIKE 'trashed_at'");
            if (!empty($columns)) {
                $result = Database::execute(
                    "UPDATE {$table} SET status = ?, trashed_at = NULL, updated_at = NOW() WHERE id = ?",
                    [self::STATUS_DRAFT, $id]
                ) > 0;
            }
        } catch (Exception $e) {
            // Fall through to simple update
        }
        
        if (!$result) {
            // Fallback for databases without trashed_at column
            $result = self::update($id, ['status' => self::STATUS_DRAFT]);
        }
        
        if ($result) {
            safe_do_action('post_restored', $id, $post);
        }
        
        return $result;
    }

    /**
     * Empty all items from trash (permanent delete)
     * @param string|null $postType Optionally limit to a specific post type
     * @return int Number of items deleted
     */
    public static function emptyTrash(?string $postType = null): int
    {
        $table = Database::table('posts');
        
        // Get IDs of all trashed posts
        $sql = "SELECT id FROM {$table} WHERE status = ?";
        $params = [self::STATUS_TRASH];
        
        if ($postType) {
            $sql .= " AND post_type = ?";
            $params[] = $postType;
        }
        
        $posts = Database::query($sql, $params);
        $count = 0;
        
        foreach ($posts as $post) {
            if (self::delete($post['id'], true)) {
                $count++;
            }
        }
        
        return $count;
    }

    /**
     * Clean up trashed items older than retention period
     * @return int Number of items permanently deleted
     */
    public static function cleanupOldTrash(): int
    {
        $table = Database::table('posts');
        $retentionDays = self::TRASH_RETENTION_DAYS;
        
        // Check if trashed_at column exists
        try {
            $columns = Database::query("SHOW COLUMNS FROM {$table} LIKE 'trashed_at'");
            if (empty($columns)) {
                return 0; // Column doesn't exist yet
            }
        } catch (Exception $e) {
            return 0;
        }
        
        // Get IDs of expired trashed posts
        $posts = Database::query(
            "SELECT id FROM {$table} WHERE status = ? AND trashed_at IS NOT NULL AND trashed_at < DATE_SUB(NOW(), INTERVAL ? DAY)",
            [self::STATUS_TRASH, $retentionDays]
        );
        
        $count = 0;
        foreach ($posts as $post) {
            if (self::delete($post['id'], true)) {
                $count++;
            }
        }
        
        return $count;
    }

    /**
     * Get days remaining before a trashed post is permanently deleted
     */
    public static function getDaysUntilDeletion(array $post): ?int
    {
        if ($post['status'] !== self::STATUS_TRASH) {
            return null;
        }
        
        // Check if trashed_at exists and has a value
        if (empty($post['trashed_at'])) {
            return null; // Column doesn't exist or no value
        }
        
        $trashedAt = strtotime($post['trashed_at']);
        $deleteAt = $trashedAt + (self::TRASH_RETENTION_DAYS * 86400);
        $remaining = ceil(($deleteAt - time()) / 86400);
        
        return max(0, (int)$remaining);
    }

    /**
     * Get count of trashed items
     */
    public static function getTrashCount(?string $postType = null): int
    {
        $table = Database::table('posts');
        
        if ($postType) {
            return (int) Database::queryValue(
                "SELECT COUNT(*) FROM {$table} WHERE status = ? AND post_type = ?",
                [self::STATUS_TRASH, $postType]
            );
        }
        
        return (int) Database::queryValue(
            "SELECT COUNT(*) FROM {$table} WHERE status = ?",
            [self::STATUS_TRASH]
        );
    }

    // =====================================================
    // SCHEDULED PUBLISHING
    // =====================================================

    /**
     * Schedule a post for future publication
     */
    public static function schedule(int $id, string $datetime): bool
    {
        $table = Database::table('posts');
        
        // Check if scheduled_at column exists
        try {
            $columns = Database::query("SHOW COLUMNS FROM {$table} LIKE 'scheduled_at'");
            if (empty($columns)) {
                // Column doesn't exist, just set as draft
                return self::update($id, ['status' => self::STATUS_DRAFT]);
            }
        } catch (Exception $e) {
            return false;
        }
        
        return Database::execute(
            "UPDATE {$table} SET status = ?, scheduled_at = ?, updated_at = NOW() WHERE id = ?",
            [self::STATUS_SCHEDULED, $datetime, $id]
        ) > 0;
    }

    /**
     * Publish all scheduled posts that are due
     * Call this on page load or via cron
     * @return int Number of posts published
     */
    public static function publishScheduledPosts(): int
    {
        $table = Database::table('posts');
        
        // Check if scheduled_at column exists
        try {
            $columns = Database::query("SHOW COLUMNS FROM {$table} LIKE 'scheduled_at'");
            if (empty($columns)) {
                return 0; // Column doesn't exist yet
            }
        } catch (Exception $e) {
            return 0;
        }
        
        // Get all scheduled posts that are due
        $posts = Database::query(
            "SELECT id FROM {$table} WHERE status = ? AND scheduled_at IS NOT NULL AND scheduled_at <= NOW()",
            [self::STATUS_SCHEDULED]
        );
        
        $count = 0;
        foreach ($posts as $post) {
            $result = Database::execute(
                "UPDATE {$table} SET status = ?, published_at = scheduled_at, scheduled_at = NULL, updated_at = NOW() WHERE id = ?",
                [self::STATUS_PUBLISHED, $post['id']]
            );
            if ($result > 0) {
                $count++;
            }
        }
        
        return $count;
    }

    /**
     * Get scheduled posts
     */
    public static function getScheduledPosts(?string $postType = null, int $limit = 50): array
    {
        $table = Database::table('posts');
        
        $sql = "SELECT * FROM {$table} WHERE status = ?";
        $params = [self::STATUS_SCHEDULED];
        
        if ($postType) {
            $sql .= " AND post_type = ?";
            $params[] = $postType;
        }
        
        $sql .= " ORDER BY scheduled_at ASC LIMIT ?";
        $params[] = $limit;
        
        return Database::query($sql, $params);
    }

    /**
     * Get count of scheduled posts
     */
    public static function getScheduledCount(?string $postType = null): int
    {
        $table = Database::table('posts');
        
        if ($postType) {
            return (int) Database::queryValue(
                "SELECT COUNT(*) FROM {$table} WHERE status = ? AND post_type = ?",
                [self::STATUS_SCHEDULED, $postType]
            );
        }
        
        return (int) Database::queryValue(
            "SELECT COUNT(*) FROM {$table} WHERE status = ?",
            [self::STATUS_SCHEDULED]
        );
    }

    /**
     * Duplicate a post with all its meta and taxonomy terms
     * @return int|false New post ID or false on failure
     */
    public static function duplicate(int $id)
    {
        $post = self::find($id);
        if (!$post) {
            return false;
        }
        
        // Prepare new post data
        $newTitle = $post['title'] . ' (Copy)';
        $newSlug = uniqueSlug(slugify($newTitle), $post['post_type']);
        
        // Create the duplicate post
        $newId = Database::insert(Database::table('posts'), [
            'post_type' => $post['post_type'],
            'title' => $newTitle,
            'slug' => $newSlug,
            'content' => $post['content'],
            'excerpt' => $post['excerpt'] ?? '',
            'status' => self::STATUS_DRAFT, // Always create as draft
            'author_id' => User::current()['id'] ?? $post['author_id'],
            'parent_id' => $post['parent_id'],
            'menu_order' => $post['menu_order'],
            'featured_image_id' => $post['featured_image_id'],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'published_at' => null, // Don't copy published date
        ]);
        
        if (!$newId) {
            return false;
        }
        
        // Copy all meta data (custom fields)
        $meta = self::getMeta($id);
        if (!empty($meta)) {
            foreach ($meta as $key => $value) {
                self::setMeta($newId, $key, $value);
            }
        }
        
        // Copy taxonomy terms if Taxonomy class is available
        if (class_exists('Taxonomy')) {
            try {
                // Get all taxonomies for this post type
                $taxonomies = Taxonomy::getForPostType($post['post_type']);
                
                foreach ($taxonomies as $taxSlug => $taxonomy) {
                    // Get terms assigned to original post
                    $termIds = Taxonomy::getPostTermIds($id, $taxSlug);
                    
                    if (!empty($termIds)) {
                        // Assign same terms to new post
                        Taxonomy::setPostTerms($newId, $taxSlug, $termIds);
                    }
                }
            } catch (Exception $e) {
                // Taxonomy tables might not exist, continue without copying terms
            }
        }
        
        return $newId;
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
        // Allow filtering of meta value before save
        $value = safe_apply_filters('pre_update_post_meta', $value, $postId, $key);
        
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
        
        // Fire action after meta updated
        safe_do_action('post_meta_updated', $postId, $key, $value);
    }

    /**
     * Delete post meta
     */
    public static function deleteMeta(int $postId, string $key): bool
    {
        safe_do_action('pre_delete_post_meta', $postId, $key);
        
        $result = Database::delete(Database::table('postmeta'), 'post_id = ? AND meta_key = ?', [$postId, $key]) > 0;
        
        if ($result) {
            safe_do_action('post_meta_deleted', $postId, $key);
        }
        
        return $result;
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
     * Get featured image URL (convenience method for themes)
     * @return string|null
     */
    public static function featuredImage(array $post): ?string
    {
        $media = self::getFeaturedImage($post);
        if (!$media) {
            return null;
        }
        return Media::getUrl($media);
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
            $url = SITE_URL . '/' . $post['slug'];
        } else {
            $url = SITE_URL . '/' . $post['post_type'] . '/' . $post['slug'];
        }
        
        // Allow filtering of permalink
        return safe_apply_filters('the_permalink', $url, $post);
    }

    /**
     * Get adjacent (previous or next) post
     * @param int $postId Current post ID
     * @param string $direction 'prev' or 'next'
     * @param string $postType Post type to filter by
     * @return array|null
     */
    public static function getAdjacent(int $postId, string $direction = 'prev', string $postType = 'post'): ?array
    {
        $table = Database::table('posts');
        $post = self::find($postId);
        
        if (!$post) {
            return null;
        }
        
        $createdAt = $post['created_at'];
        
        if ($direction === 'prev') {
            // Get previous post (older)
            $sql = "SELECT * FROM {$table} 
                    WHERE post_type = ? 
                    AND status = 'published' 
                    AND created_at < ? 
                    ORDER BY created_at DESC 
                    LIMIT 1";
        } else {
            // Get next post (newer)
            $sql = "SELECT * FROM {$table} 
                    WHERE post_type = ? 
                    AND status = 'published' 
                    AND created_at > ? 
                    ORDER BY created_at ASC 
                    LIMIT 1";
        }
        
        $results = Database::query($sql, [$postType, $createdAt]);
        return $results[0] ?? null;
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
