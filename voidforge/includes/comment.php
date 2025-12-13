<?php
/**
 * Comment System - VoidForge CMS
 * Handles comments, replies, and moderation
 */

defined('CMS_ROOT') or die('Direct access not allowed');

class Comment
{
    // Comment statuses
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_SPAM = 'spam';
    public const STATUS_TRASH = 'trash';

    public const STATUS_LABELS = [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'spam' => 'Spam',
        'trash' => 'Trash',
    ];

    private static ?bool $tableExists = null;

    /**
     * Check if comments table exists
     */
    public static function tableExists(): bool
    {
        if (self::$tableExists === null) {
            try {
                $table = Database::table('comments');
                $pdo = Database::getInstance();
                $result = $pdo->query("SHOW TABLES LIKE '{$table}'");
                self::$tableExists = $result->rowCount() > 0;
            } catch (Exception $e) {
                self::$tableExists = false;
            }
        }
        return self::$tableExists;
    }

    /**
     * Ensure comments table exists, create if not
     */
    public static function ensureTable(): bool
    {
        if (self::tableExists()) {
            return true;
        }

        try {
            $table = Database::table('comments');
            $pdo = Database::getInstance();
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS {$table} (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    post_id INT UNSIGNED NOT NULL,
                    parent_id INT UNSIGNED NOT NULL DEFAULT 0,
                    user_id INT UNSIGNED DEFAULT NULL,
                    author_name VARCHAR(255) NOT NULL DEFAULT '',
                    author_email VARCHAR(255) NOT NULL DEFAULT '',
                    author_url VARCHAR(500) DEFAULT '',
                    author_ip VARCHAR(45) DEFAULT '',
                    content TEXT NOT NULL,
                    status ENUM('pending','approved','spam','trash') DEFAULT 'pending',
                    created_at DATETIME NOT NULL,
                    INDEX idx_post_id (post_id),
                    INDEX idx_parent_id (parent_id),
                    INDEX idx_user_id (user_id),
                    INDEX idx_status (status),
                    INDEX idx_created_at (created_at),
                    INDEX idx_post_status (post_id, status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            self::$tableExists = true;
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Find a comment by ID
     */
    public static function find(int $id): ?array
    {
        if (!self::ensureTable()) {
            return null;
        }
        $table = Database::table('comments');
        return Database::queryOne("SELECT * FROM {$table} WHERE id = ?", [$id]);
    }

    /**
     * Get comments for a post
     */
    public static function getForPost(int $postId, array $args = []): array
    {
        if (!self::ensureTable()) {
            return [];
        }
        
        $defaults = [
            'status' => self::STATUS_APPROVED,
            'parent_id' => null,
            'orderby' => 'created_at',
            'order' => 'ASC',
            'include_replies' => true,
        ];

        $args = array_merge($defaults, $args);
        $table = Database::table('comments');
        $where = ['post_id = ?'];
        $params = [$postId];

        // Status filter
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

        // Parent filter (for getting top-level or replies)
        if ($args['parent_id'] !== null) {
            $where[] = 'parent_id = ?';
            $params[] = $args['parent_id'];
        }

        $whereClause = implode(' AND ', $where);
        $orderClause = $args['orderby'] . ' ' . $args['order'];

        $comments = Database::query(
            "SELECT * FROM {$table} WHERE {$whereClause} ORDER BY {$orderClause}",
            $params
        );

        // Build threaded structure if getting top-level comments
        if ($args['include_replies'] && $args['parent_id'] === 0) {
            foreach ($comments as &$comment) {
                $comment['replies'] = self::getReplies($comment['id'], $args['status']);
            }
            unset($comment);
        }

        return $comments;
    }

    /**
     * Get replies to a comment (recursive)
     */
    public static function getReplies(int $parentId, $status = self::STATUS_APPROVED, int $depth = 0): array
    {
        $maxDepth = (int) getOption('comment_max_depth', 3);
        if ($depth >= $maxDepth) {
            return [];
        }

        $table = Database::table('comments');
        $where = ['parent_id = ?'];
        $params = [$parentId];

        if ($status) {
            if (is_array($status)) {
                $placeholders = implode(',', array_fill(0, count($status), '?'));
                $where[] = "status IN ({$placeholders})";
                $params = array_merge($params, $status);
            } else {
                $where[] = 'status = ?';
                $params[] = $status;
            }
        }

        $whereClause = implode(' AND ', $where);
        $replies = Database::query(
            "SELECT * FROM {$table} WHERE {$whereClause} ORDER BY created_at ASC",
            $params
        );

        // Recursively get nested replies
        foreach ($replies as &$reply) {
            $reply['depth'] = $depth + 1;
            $reply['replies'] = self::getReplies($reply['id'], $status, $depth + 1);
        }
        unset($reply);

        return $replies;
    }

    /**
     * Query comments with filters (for admin)
     */
    public static function query(array $args = []): array
    {
        if (!self::ensureTable()) {
            return [];
        }

        $defaults = [
            'status' => null,
            'post_id' => null,
            'user_id' => null,
            'search' => null,
            'orderby' => 'created_at',
            'order' => 'DESC',
            'limit' => null,
            'offset' => 0,
        ];

        $args = array_merge($defaults, $args);
        $table = Database::table('comments');
        $where = ['1=1'];
        $params = [];

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

        // Post filter
        if ($args['post_id']) {
            $where[] = 'post_id = ?';
            $params[] = $args['post_id'];
        }

        // User filter
        if ($args['user_id']) {
            $where[] = 'user_id = ?';
            $params[] = $args['user_id'];
        }

        // Search
        if ($args['search']) {
            $where[] = '(content LIKE ? OR author_name LIKE ? OR author_email LIKE ?)';
            $searchTerm = '%' . $args['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $whereClause = implode(' AND ', $where);
        $orderClause = $args['orderby'] . ' ' . $args['order'];

        $sql = "SELECT * FROM {$table} WHERE {$whereClause} ORDER BY {$orderClause}";

        if ($args['limit']) {
            $sql .= " LIMIT {$args['limit']} OFFSET {$args['offset']}";
        }

        return Database::query($sql, $params);
    }

    /**
     * Count comments with filters
     */
    public static function count(array $args = []): int
    {
        if (!self::ensureTable()) {
            return 0;
        }

        $defaults = [
            'status' => null,
            'post_id' => null,
        ];

        $args = array_merge($defaults, $args);
        $table = Database::table('comments');
        $where = ['1=1'];
        $params = [];

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

        if ($args['post_id']) {
            $where[] = 'post_id = ?';
            $params[] = $args['post_id'];
        }

        $whereClause = implode(' AND ', $where);

        return (int) Database::queryValue(
            "SELECT COUNT(*) FROM {$table} WHERE {$whereClause}",
            $params
        );
    }

    /**
     * Count pending comments (for admin badge)
     */
    public static function countPending(): int
    {
        return self::count(['status' => self::STATUS_PENDING]);
    }

    /**
     * Create a new comment
     */
    public static function create(array $data): ?int
    {
        if (!self::ensureTable()) {
            return null;
        }

        // Allow filtering of comment data before insertion (for spam checking, etc.)
        $data = Plugin::applyFilters('pre_insert_comment', $data);
        
        // If filter returns false/null, comment was rejected
        if (!$data) {
            return null;
        }
        
        $table = Database::table('comments');

        // Determine initial status based on settings
        $moderation = getOption('comment_moderation', 'manual');
        $status = self::STATUS_PENDING;
        
        if ($moderation === 'none') {
            $status = self::STATUS_APPROVED;
        } elseif ($moderation === 'registered' && !empty($data['user_id'])) {
            $status = self::STATUS_APPROVED;
        }

        $insertData = [
            'post_id' => (int) ($data['post_id'] ?? 0),
            'parent_id' => (int) ($data['parent_id'] ?? 0),
            'user_id' => !empty($data['user_id']) ? (int) $data['user_id'] : null,
            'author_name' => trim($data['author_name'] ?? ''),
            'author_email' => trim($data['author_email'] ?? ''),
            'author_url' => trim($data['author_url'] ?? ''),
            'author_ip' => $data['author_ip'] ?? ($_SERVER['REMOTE_ADDR'] ?? ''),
            'content' => trim($data['content'] ?? ''),
            'status' => $data['status'] ?? $status,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $commentId = Database::insert($table, $insertData);

        if ($commentId) {
            // Update post comment count
            self::updatePostCommentCount($insertData['post_id']);
            
            // Fire action
            Plugin::doAction('comment_created', $commentId, $insertData);
            
            // Fire reply action if this is a reply
            if ($insertData['parent_id'] > 0) {
                $parentComment = self::find($insertData['parent_id']);
                Plugin::doAction('comment_reply', $commentId, $insertData, $parentComment);
            }
        }

        return $commentId;
    }

    /**
     * Update a comment
     */
    public static function update(int $id, array $data): bool
    {
        $table = Database::table('comments');
        $comment = self::find($id);
        
        if (!$comment) {
            return false;
        }
        
        $oldStatus = $comment['status'];

        $updateData = [];

        if (isset($data['content'])) {
            $updateData['content'] = trim($data['content']);
        }
        if (isset($data['status'])) {
            $updateData['status'] = $data['status'];
        }
        if (isset($data['author_name'])) {
            $updateData['author_name'] = trim($data['author_name']);
        }
        if (isset($data['author_email'])) {
            $updateData['author_email'] = trim($data['author_email']);
        }
        if (isset($data['author_url'])) {
            $updateData['author_url'] = trim($data['author_url']);
        }

        if (empty($updateData)) {
            return true;
        }

        $result = Database::update($table, $updateData, 'id = ?', [$id]);

        if ($result !== false) {
            // Update post comment count if status changed
            if (isset($data['status'])) {
                self::updatePostCommentCount($comment['post_id']);
                
                // Fire status changed action if status actually changed
                $newStatus = $data['status'];
                if ($oldStatus !== $newStatus) {
                    Plugin::doAction('comment_status_changed', $id, $newStatus, $oldStatus, $comment);
                }
            }
            
            Plugin::doAction('comment_updated', $id, $updateData);
        }

        return $result !== false;
    }

    /**
     * Delete a comment permanently
     */
    public static function delete(int $id): bool
    {
        $table = Database::table('comments');
        $comment = self::find($id);
        
        if (!$comment) {
            return false;
        }

        // Delete all replies first
        $replies = Database::query(
            "SELECT id FROM {$table} WHERE parent_id = ?",
            [$id]
        );
        
        foreach ($replies as $reply) {
            self::delete($reply['id']);
        }

        $result = Database::delete($table, 'id = ?', [$id]);

        if ($result) {
            self::updatePostCommentCount($comment['post_id']);
            Plugin::doAction('comment_deleted', $id, $comment);
        }

        return $result > 0;
    }

    /**
     * Approve a comment
     */
    public static function approve(int $id): bool
    {
        return self::update($id, ['status' => self::STATUS_APPROVED]);
    }

    /**
     * Mark comment as spam
     */
    public static function markSpam(int $id): bool
    {
        return self::update($id, ['status' => self::STATUS_SPAM]);
    }

    /**
     * Move comment to trash
     */
    public static function trash(int $id): bool
    {
        return self::update($id, ['status' => self::STATUS_TRASH]);
    }

    /**
     * Restore comment from trash
     */
    public static function restore(int $id): bool
    {
        return self::update($id, ['status' => self::STATUS_PENDING]);
    }

    /**
     * Update the comment count on a post
     */
    public static function updatePostCommentCount(int $postId): void
    {
        $table = Database::table('comments');
        $postsTable = Database::table('posts');

        $count = (int) Database::queryValue(
            "SELECT COUNT(*) FROM {$table} WHERE post_id = ? AND status = ?",
            [$postId, self::STATUS_APPROVED]
        );

        Database::update($postsTable, ['comment_count' => $count], 'id = ?', [$postId]);
    }

    /**
     * Get the post associated with a comment
     */
    public static function getPost(array $comment): ?array
    {
        return Post::find($comment['post_id']);
    }

    /**
     * Get the author (user) if logged in
     */
    public static function getAuthor(array $comment): ?array
    {
        if (empty($comment['user_id'])) {
            return null;
        }
        return User::find($comment['user_id']);
    }

    /**
     * Get author display name
     */
    public static function getAuthorName(array $comment): string
    {
        if (!empty($comment['user_id'])) {
            $user = User::find($comment['user_id']);
            if ($user) {
                return $user['display_name'] ?? $user['username'];
            }
        }
        return $comment['author_name'] ?: 'Anonymous';
    }

    /**
     * Get gravatar URL for comment author
     */
    public static function getGravatar(array $comment, int $size = 48): string
    {
        $email = '';
        
        if (!empty($comment['user_id'])) {
            $user = User::find($comment['user_id']);
            if ($user) {
                $email = $user['email'];
            }
        }
        
        if (empty($email)) {
            $email = $comment['author_email'] ?? '';
        }

        $hash = md5(strtolower(trim($email)));
        return "https://www.gravatar.com/avatar/{$hash}?s={$size}&d=mp";
    }

    /**
     * Check if comments are open for a post
     */
    public static function areOpen(array $post): bool
    {
        // Check global setting
        if (!getOption('comments_enabled', true)) {
            return false;
        }

        // Check post type setting
        $postType = $post['post_type'] ?? 'post';
        $enabledTypes = getOption('comment_post_types', ['post']);
        if (!in_array($postType, $enabledTypes)) {
            return false;
        }

        // Check post meta for individual override
        $postComments = Post::getMeta($post['id'], '_comments_enabled');
        if ($postComments === '0') {
            return false;
        }

        // Check age limit
        $closeAfter = (int) getOption('comment_close_after', 0);
        if ($closeAfter > 0) {
            $publishedAt = strtotime($post['published_at'] ?? $post['created_at']);
            $closeDate = $publishedAt + ($closeAfter * 86400);
            if (time() > $closeDate) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if user can moderate comments
     */
    public static function canModerate(): bool
    {
        $user = User::current();
        if (!$user) {
            return false;
        }
        return in_array($user['role'], ['admin', 'editor']);
    }

    /**
     * Validate comment data
     */
    public static function validate(array $data): array
    {
        $errors = [];

        // Content required
        if (empty(trim($data['content'] ?? ''))) {
            $errors[] = 'Comment content is required.';
        }

        // Content length
        $minLength = (int) getOption('comment_min_length', 3);
        $maxLength = (int) getOption('comment_max_length', 5000);
        $contentLength = strlen(trim($data['content'] ?? ''));
        
        if ($contentLength < $minLength) {
            $errors[] = "Comment must be at least {$minLength} characters.";
        }
        if ($contentLength > $maxLength) {
            $errors[] = "Comment must be less than {$maxLength} characters.";
        }

        // Guest comments need name and email
        if (empty($data['user_id'])) {
            if (empty(trim($data['author_name'] ?? ''))) {
                $errors[] = 'Name is required.';
            }
            if (empty(trim($data['author_email'] ?? ''))) {
                $errors[] = 'Email is required.';
            } elseif (!filter_var($data['author_email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Please enter a valid email address.';
            }
        }

        // Check if registration required
        $requireRegistration = getOption('comment_require_registration', false);
        if ($requireRegistration && empty($data['user_id'])) {
            $errors[] = 'You must be logged in to comment.';
        }

        return $errors;
    }

    /**
     * Sanitize comment content
     */
    public static function sanitizeContent(string $content): string
    {
        // Strip HTML tags (allow basic formatting if enabled)
        $allowedTags = getOption('comment_allowed_tags', '');
        if ($allowedTags) {
            $content = strip_tags($content, $allowedTags);
        } else {
            $content = strip_tags($content);
        }

        // Convert URLs to links (optional)
        if (getOption('comment_auto_links', true)) {
            $content = preg_replace(
                '/(https?:\/\/[^\s<]+)/i',
                '<a href="$1" rel="nofollow noopener" target="_blank">$1</a>',
                $content
            );
        }

        // Convert newlines to paragraphs
        $content = nl2br(trim($content));

        return $content;
    }

    /**
     * Empty trash (delete old trashed comments)
     */
    public static function emptyTrash(int $daysOld = 30): int
    {
        $table = Database::table('comments');
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$daysOld} days"));

        // Get post IDs first for count update
        $comments = Database::query(
            "SELECT DISTINCT post_id FROM {$table} WHERE status = ? AND created_at < ?",
            [self::STATUS_TRASH, $cutoff]
        );

        $deleted = Database::execute(
            "DELETE FROM {$table} WHERE status = ? AND created_at < ?",
            [self::STATUS_TRASH, $cutoff]
        );

        // Update comment counts
        foreach ($comments as $comment) {
            self::updatePostCommentCount($comment['post_id']);
        }

        return $deleted;
    }

    /**
     * Get recent comments
     */
    public static function getRecent(int $limit = 5, string $status = self::STATUS_APPROVED): array
    {
        $table = Database::table('comments');
        return Database::query(
            "SELECT * FROM {$table} WHERE status = ? ORDER BY created_at DESC LIMIT ?",
            [$status, $limit]
        );
    }

    /**
     * Bulk action on comments
     */
    public static function bulkAction(array $ids, string $action): int
    {
        $affected = 0;

        foreach ($ids as $id) {
            $id = (int) $id;
            switch ($action) {
                case 'approve':
                    if (self::approve($id)) $affected++;
                    break;
                case 'spam':
                    if (self::markSpam($id)) $affected++;
                    break;
                case 'trash':
                    if (self::trash($id)) $affected++;
                    break;
                case 'restore':
                    if (self::restore($id)) $affected++;
                    break;
                case 'delete':
                    if (self::delete($id)) $affected++;
                    break;
            }
        }

        return $affected;
    }
}
