<?php
/**
 * Database Migrations - VoidForge CMS
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

// Create post_revisions table if it doesn't exist
$revisionsTable = Database::table('post_revisions');
try {
    Database::execute("
        CREATE TABLE IF NOT EXISTS {$revisionsTable} (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            post_id INT UNSIGNED NOT NULL,
            post_type VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL,
            content LONGTEXT,
            excerpt TEXT,
            meta_data LONGTEXT,
            author_id INT UNSIGNED NOT NULL,
            revision_number INT UNSIGNED NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            INDEX idx_post_id (post_id),
            INDEX idx_post_type (post_type),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
} catch (Exception $e) {
    // Table might already exist
}

// v0.1.4: Add menus table
try {
    $menusTable = Database::table('menus');
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS {$menusTable} (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL,
            location VARCHAR(50) DEFAULT NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_slug (slug),
            INDEX idx_location (location)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
} catch (Exception $e) {
    // Table might already exist
}

// v0.1.4: Add menu_items table
try {
    $menuItemsTable = Database::table('menu_items');
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS {$menuItemsTable} (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            menu_id INT UNSIGNED NOT NULL,
            parent_id INT UNSIGNED NOT NULL DEFAULT 0,
            title VARCHAR(255) NOT NULL,
            type VARCHAR(50) NOT NULL DEFAULT 'custom',
            object_id INT UNSIGNED DEFAULT NULL,
            url VARCHAR(500) DEFAULT NULL,
            target VARCHAR(20) DEFAULT '_self',
            css_class VARCHAR(255) DEFAULT NULL,
            position INT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            INDEX idx_menu_id (menu_id),
            INDEX idx_parent_id (parent_id),
            INDEX idx_position (position)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
} catch (Exception $e) {
    // Table might already exist
}

// v0.1.5: Add taxonomies table
try {
    $taxonomiesTable = Database::table('taxonomies');
    $pdo = Database::getInstance();
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS {$taxonomiesTable} (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(100) NOT NULL,
            singular VARCHAR(255) DEFAULT NULL,
            description TEXT,
            hierarchical TINYINT(1) NOT NULL DEFAULT 0,
            post_types TEXT,
            created_at DATETIME NOT NULL,
            UNIQUE INDEX idx_slug (slug)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
} catch (Exception $e) {
    // Table might already exist
}

// v0.1.5: Add terms table
try {
    $termsTable = Database::table('terms');
    $pdo = Database::getInstance();
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS {$termsTable} (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            taxonomy VARCHAR(100) NOT NULL,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL,
            description TEXT,
            parent_id INT UNSIGNED NOT NULL DEFAULT 0,
            count INT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            INDEX idx_taxonomy (taxonomy),
            INDEX idx_slug (slug),
            INDEX idx_parent (parent_id),
            UNIQUE INDEX idx_taxonomy_slug (taxonomy, slug)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
} catch (Exception $e) {
    // Table might already exist
}

// v0.1.5: Add term_relationships table
try {
    $relTable = Database::table('term_relationships');
    $pdo = Database::getInstance();
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS {$relTable} (
            post_id INT UNSIGNED NOT NULL,
            term_id INT UNSIGNED NOT NULL,
            PRIMARY KEY (post_id, term_id),
            INDEX idx_term_id (term_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
} catch (Exception $e) {
    // Table might already exist
}

// Update version in options
setOption('cms_version', '0.1.5');
setOption('last_update', date('Y-m-d H:i:s'));
