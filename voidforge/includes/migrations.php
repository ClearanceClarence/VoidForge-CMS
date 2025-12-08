<?php
/**
 * Database Migrations - VoidForge CMS v1.0.8
 * Run automatically during update process
 */

defined('CMS_ROOT') or die('Direct access not allowed');

$postsTable = Database::table('posts');
$foldersTable = Database::table('media_folders');
$mediaTable = Database::table('media');

// Fix posts table - add missing columns
try {
    $columns = Database::query("SHOW COLUMNS FROM {$postsTable} LIKE 'parent_id'");
    if (empty($columns)) {
        Database::execute("ALTER TABLE {$postsTable} ADD COLUMN parent_id INT DEFAULT NULL AFTER author_id");
        Database::execute("ALTER TABLE {$postsTable} ADD INDEX idx_parent_id (parent_id)");
    }
} catch (Exception $e) {
    // Column might already exist
}

try {
    $columns = Database::query("SHOW COLUMNS FROM {$postsTable} LIKE 'menu_order'");
    if (empty($columns)) {
        Database::execute("ALTER TABLE {$postsTable} ADD COLUMN menu_order INT DEFAULT 0 AFTER parent_id");
    }
} catch (Exception $e) {
    // Column might already exist
}

try {
    $columns = Database::query("SHOW COLUMNS FROM {$postsTable} LIKE 'featured_image_id'");
    if (empty($columns)) {
        // Check if old column exists
        $oldCol = Database::query("SHOW COLUMNS FROM {$postsTable} LIKE 'featured_image'");
        if (!empty($oldCol)) {
            Database::execute("ALTER TABLE {$postsTable} CHANGE COLUMN featured_image featured_image_id INT DEFAULT NULL");
        } else {
            Database::execute("ALTER TABLE {$postsTable} ADD COLUMN featured_image_id INT DEFAULT NULL AFTER menu_order");
        }
    }
} catch (Exception $e) {
    // Column might already exist
}

// Create media_folders table if it doesn't exist
try {
    Database::execute("
        CREATE TABLE IF NOT EXISTS {$foldersTable} (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
} catch (Exception $e) {
    // Table might already exist
}

// Add folder_id column to media table if it doesn't exist
try {
    $columns = Database::query("SHOW COLUMNS FROM {$mediaTable} LIKE 'folder_id'");
    if (empty($columns)) {
        Database::execute("ALTER TABLE {$mediaTable} ADD COLUMN folder_id INT UNSIGNED DEFAULT NULL AFTER caption");
        Database::execute("ALTER TABLE {$mediaTable} ADD INDEX idx_folder_id (folder_id)");
    }
} catch (Exception $e) {
    // Column might already exist
}

// Add title column to media table if it doesn't exist
try {
    $columns = Database::query("SHOW COLUMNS FROM {$mediaTable} LIKE 'title'");
    if (empty($columns)) {
        Database::execute("ALTER TABLE {$mediaTable} ADD COLUMN title VARCHAR(255) DEFAULT NULL AFTER alt_text");
    }
} catch (Exception $e) {
    // Column might already exist
}

// Update version in options
setOption('cms_version', '1.0.8');
setOption('last_update', date('Y-m-d H:i:s'));
