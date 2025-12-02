<?php
/**
 * Database Migrations - Forge CMS v1.0.3
 * Run automatically during update process
 */

defined('CMS_ROOT') or die('Direct access not allowed');

// Create media_folders table if it doesn't exist
try {
    Database::execute("
        CREATE TABLE IF NOT EXISTS media_folders (
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
    $columns = Database::query("SHOW COLUMNS FROM media LIKE 'folder_id'");
    if (empty($columns)) {
        Database::execute("ALTER TABLE media ADD COLUMN folder_id INT UNSIGNED DEFAULT NULL AFTER caption");
        Database::execute("ALTER TABLE media ADD INDEX idx_folder_id (folder_id)");
    }
} catch (Exception $e) {
    // Column might already exist
}

// Add title column to media table if it doesn't exist
try {
    $columns = Database::query("SHOW COLUMNS FROM media LIKE 'title'");
    if (empty($columns)) {
        Database::execute("ALTER TABLE media ADD COLUMN title VARCHAR(255) DEFAULT NULL AFTER alt_text");
    }
} catch (Exception $e) {
    // Column might already exist
}

// Update version in options
setOption('cms_version', '1.0.3');
setOption('last_update', date('Y-m-d H:i:s'));
