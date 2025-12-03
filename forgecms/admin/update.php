<?php
/**
 * System Updates - Forge CMS
 * Improved update process with better error handling
 */

// Set timeouts IMMEDIATELY before anything else
@set_time_limit(0);
@ini_set('max_execution_time', '0');
@ini_set('memory_limit', '512M');
ignore_user_abort(true);

define('CMS_ROOT', dirname(__DIR__));
require_once CMS_ROOT . '/includes/config.php';
require_once CMS_ROOT . '/includes/database.php';
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/user.php';
require_once CMS_ROOT . '/includes/post.php';
require_once CMS_ROOT . '/includes/media.php';

Post::init();

User::startSession();
User::requireRole('admin');

/**
 * Safely run database migrations
 */
function runMigrations(): array {
    $log = [];
    
    try {
        // Ensure database connection
        $pdo = Database::getInstance();
        
        // Get table prefix
        $mediaFolders = Database::table('media_folders');
        $media = Database::table('media');
        $options = Database::table('options');
        $customPostTypes = Database::table('custom_post_types');
        
        // Create media_folders table if it doesn't exist
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS {$mediaFolders} (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                created_at DATETIME NOT NULL,
                INDEX idx_name (name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $log[] = '  → Checked media_folders table';
        
        // Add folder_id column to media table if it doesn't exist
        $columns = $pdo->query("SHOW COLUMNS FROM {$media} LIKE 'folder_id'")->fetchAll();
        if (empty($columns)) {
            $pdo->exec("ALTER TABLE {$media} ADD COLUMN folder_id INT UNSIGNED DEFAULT NULL");
            $pdo->exec("ALTER TABLE {$media} ADD INDEX idx_folder_id (folder_id)");
            $log[] = '  → Added folder_id column to media';
        }
        
        // Add title column to media table if it doesn't exist
        $columns = $pdo->query("SHOW COLUMNS FROM {$media} LIKE 'title'")->fetchAll();
        if (empty($columns)) {
            $pdo->exec("ALTER TABLE {$media} ADD COLUMN title VARCHAR(255) DEFAULT NULL AFTER alt_text");
            $log[] = '  → Added title column to media';
        }
        
        // Add caption column to media table if it doesn't exist
        $columns = $pdo->query("SHOW COLUMNS FROM {$media} LIKE 'caption'")->fetchAll();
        if (empty($columns)) {
            $pdo->exec("ALTER TABLE {$media} ADD COLUMN caption TEXT DEFAULT NULL AFTER title");
            $log[] = '  → Added caption column to media';
        }
        
        // Ensure options table exists
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS {$options} (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                option_name VARCHAR(255) NOT NULL UNIQUE,
                option_value LONGTEXT,
                INDEX idx_name (option_name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $log[] = '  → Checked options table';
        
        // Ensure custom_post_types table exists
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS {$customPostTypes} (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                slug VARCHAR(50) NOT NULL UNIQUE,
                config JSON NOT NULL,
                created_at DATETIME NOT NULL,
                INDEX idx_slug (slug)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $log[] = '  → Checked custom_post_types table';
        
    } catch (Exception $e) {
        $log[] = '  ⚠ Migration warning: ' . $e->getMessage();
    }
    
    return $log;
}

/**
 * Recursive copy that handles errors gracefully
 */
function safeCopy($source, $dest): bool {
    if (is_dir($source)) {
        if (!is_dir($dest)) {
            @mkdir($dest, 0755, true);
        }
        $items = @scandir($source);
        if ($items === false) return false;
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            safeCopy($source . '/' . $item, $dest . '/' . $item);
        }
        return true;
    } else {
        $destDir = dirname($dest);
        if (!is_dir($destDir)) {
            @mkdir($destDir, 0755, true);
        }
        return @copy($source, $dest);
    }
}

/**
 * Recursive delete that handles errors gracefully
 */
function safeDelete($path): bool {
    if (!file_exists($path)) return true;
    
    if (is_dir($path)) {
        $items = @scandir($path);
        if ($items === false) return false;
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            safeDelete($path . '/' . $item);
        }
        return @rmdir($path);
    } else {
        return @unlink($path);
    }
}

// Collect server diagnostics
$diagnostics = [
    'max_execution_time' => ini_get('max_execution_time'),
    'max_input_time' => ini_get('max_input_time'),
    'memory_limit' => ini_get('memory_limit'),
    'post_max_size' => ini_get('post_max_size'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'php_sapi' => php_sapi_name(),
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
];

// Handle AJAX diagnostic test
if (isset($_GET['test_timeout'])) {
    header('Content-Type: application/json');
    $start = microtime(true);
    $duration = (int)($_GET['duration'] ?? 5);
    
    // Try to extend limits
    @set_time_limit(0);
    @ini_set('max_execution_time', '0');
    
    for ($i = 0; $i < $duration; $i++) {
        sleep(1);
        // Check if connection is still alive
        if (connection_aborted()) {
            break;
        }
    }
    
    $elapsed = round(microtime(true) - $start, 2);
    echo json_encode([
        'success' => true,
        'elapsed' => $elapsed,
        'requested' => $duration,
        'completed' => $elapsed >= $duration
    ]);
    exit;
}

// Handle manual migrations (after FTP upload)
if (isset($_GET['run_migrations'])) {
    $migrationLog = runMigrations();
    setOption('last_update', date('Y-m-d H:i:s'));
    
    // Show results
    echo '<!DOCTYPE html><html><head><title>Migrations Complete</title>';
    echo '<style>body{font-family:system-ui,sans-serif;max-width:600px;margin:50px auto;padding:20px;background:#1a1a2e;color:#e0e0e0;}';
    echo '.log{background:#252542;padding:1rem;border-radius:8px;margin:1rem 0;font-family:monospace;font-size:14px;}';
    echo '.success{color:#10b981;font-weight:600;font-size:1.25rem;}';
    echo 'a{color:#818cf8;}</style></head><body>';
    echo '<h1>Database Migrations</h1>';
    echo '<p class="success">✓ Migrations completed successfully!</p>';
    echo '<div class="log">';
    foreach ($migrationLog as $line) {
        echo htmlspecialchars($line) . '<br>';
    }
    echo '</div>';
    echo '<p><a href="' . ADMIN_URL . '/update.php">← Back to Update Page</a></p>';
    echo '</body></html>';
    exit;
}

// Handle AJAX file upload and extraction
if (isset($_POST['ajax_update']) && isset($_FILES['update_file'])) {
    header('Content-Type: application/json');
    
    @set_time_limit(0);
    @ini_set('max_execution_time', '0');
    
    $response = ['success' => false, 'log' => [], 'error' => null];
    
    try {
        // Verify CSRF
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid security token. Please refresh and try again.');
        }
        
        // Check file upload
        if ($_FILES['update_file']['error'] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize (' . ini_get('upload_max_filesize') . ')',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            ];
            throw new Exception($errorMessages[$_FILES['update_file']['error']] ?? 'Upload error: ' . $_FILES['update_file']['error']);
        }
        
        $file = $_FILES['update_file'];
        $response['log'][] = '✓ File received: ' . $file['name'] . ' (' . formatFileSize($file['size']) . ')';
        
        // Save to backups folder for processing
        $backupsDir = CMS_ROOT . '/backups';
        if (!is_dir($backupsDir)) {
            if (!@mkdir($backupsDir, 0755, true)) {
                throw new Exception('Cannot create backups directory');
            }
        }
        
        $uploadedZip = $backupsDir . '/update_' . time() . '.zip';
        if (!move_uploaded_file($file['tmp_name'], $uploadedZip)) {
            throw new Exception('Failed to save uploaded file');
        }
        
        $response['log'][] = '✓ File saved to server';
        $response['success'] = true;
        $response['zip_path'] = $uploadedZip;
        $response['next_step'] = 'extract';
        
    } catch (Exception $e) {
        $response['error'] = $e->getMessage();
    }
    
    echo json_encode($response);
    exit;
}

// Handle AJAX extraction step
if (isset($_POST['ajax_extract'])) {
    header('Content-Type: application/json');
    
    @set_time_limit(0);
    @ini_set('max_execution_time', '0');
    
    $response = ['success' => false, 'log' => [], 'error' => null];
    
    try {
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid security token');
        }
        
        $zipPath = $_POST['zip_path'] ?? '';
        if (!file_exists($zipPath)) {
            throw new Exception('Upload file not found. Please try again.');
        }
        
        $backupsDir = CMS_ROOT . '/backups';
        $tempDir = $backupsDir . '/temp_update_' . uniqid();
        $backupDir = $backupsDir . '/backup_' . date('Y-m-d_H-i-s');
        
        // Extract ZIP
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new Exception('Failed to open ZIP file');
        }
        
        if (!@mkdir($tempDir, 0755, true)) {
            throw new Exception('Cannot create temp directory');
        }
        
        if (!$zip->extractTo($tempDir)) {
            $zip->close();
            throw new Exception('Failed to extract ZIP');
        }
        $zip->close();
        
        $response['log'][] = '✓ ZIP extracted';
        
        // Find CMS root
        $extractedRoot = $tempDir;
        $items = @scandir($tempDir);
        if ($items) {
            foreach ($items as $item) {
                if ($item !== '.' && $item !== '..' && is_dir($tempDir . '/' . $item)) {
                    if (file_exists($tempDir . '/' . $item . '/includes/config.php') || 
                        file_exists($tempDir . '/' . $item . '/admin/index.php')) {
                        $extractedRoot = $tempDir . '/' . $item;
                        $response['log'][] = '✓ Found CMS in: ' . $item;
                        break;
                    }
                }
            }
        }
        
        $response['success'] = true;
        $response['temp_dir'] = $tempDir;
        $response['extracted_root'] = $extractedRoot;
        $response['backup_dir'] = $backupDir;
        $response['next_step'] = 'install';
        
    } catch (Exception $e) {
        $response['error'] = $e->getMessage();
    }
    
    echo json_encode($response);
    exit;
}

// Handle AJAX install step
if (isset($_POST['ajax_install'])) {
    header('Content-Type: application/json');
    
    @set_time_limit(0);
    @ini_set('max_execution_time', '0');
    
    $response = ['success' => false, 'log' => [], 'error' => null];
    
    try {
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid security token');
        }
        
        $extractedRoot = $_POST['extracted_root'] ?? '';
        $backupDir = $_POST['backup_dir'] ?? '';
        $tempDir = $_POST['temp_dir'] ?? '';
        
        if (!is_dir($extractedRoot)) {
            throw new Exception('Extracted files not found');
        }
        
        // Preserved paths
        $preservedPaths = ['includes/config.php', 'uploads', 'backups', '.htaccess'];
        
        // Backup preserved files
        @mkdir($backupDir, 0755, true);
        foreach ($preservedPaths as $path) {
            $source = CMS_ROOT . '/' . $path;
            if (file_exists($source)) {
                $dest = $backupDir . '/' . $path;
                @mkdir(dirname($dest), 0755, true);
                if (is_dir($source)) {
                    safeCopy($source, $dest);
                } else {
                    @copy($source, $dest);
                }
            }
        }
        $response['log'][] = '✓ Created backup';
        
        // Copy new files
        $fileCount = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($extractedRoot, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $item) {
            $relativePath = substr($item->getPathname(), strlen($extractedRoot) + 1);
            $destPath = CMS_ROOT . '/' . $relativePath;
            
            // Skip preserved paths
            $skip = false;
            foreach ($preservedPaths as $preserved) {
                if (strpos($relativePath, $preserved) === 0) {
                    $skip = true;
                    break;
                }
            }
            if ($skip) continue;
            
            if ($item->isDir()) {
                @mkdir($destPath, 0755, true);
            } else {
                @mkdir(dirname($destPath), 0755, true);
                @copy($item->getPathname(), $destPath);
                $fileCount++;
            }
        }
        
        $response['log'][] = '✓ Installed ' . $fileCount . ' files';
        
        // Restore preserved files
        foreach ($preservedPaths as $path) {
            $source = $backupDir . '/' . $path;
            $dest = CMS_ROOT . '/' . $path;
            if (file_exists($source)) {
                if (is_dir($source)) {
                    safeCopy($source, $dest);
                } else {
                    @copy($source, $dest);
                }
            }
        }
        $response['log'][] = '✓ Restored config files';
        
        // Run migrations
        $migrationLog = runMigrations();
        $response['log'] = array_merge($response['log'], $migrationLog);
        
        // Clean up
        safeDelete($tempDir);
        $response['log'][] = '✓ Cleaned up temp files';
        
        // Update version
        setOption('last_update', date('Y-m-d H:i:s'));
        
        $response['success'] = true;
        $response['log'][] = '';
        $response['log'][] = '🎉 Update completed successfully!';
        
    } catch (Exception $e) {
        $response['error'] = $e->getMessage();
    }
    
    echo json_encode($response);
    exit;
}

$zipAvailable = class_exists('ZipArchive');
$pageTitle = 'System Update';
$currentVersion = CMS_VERSION;
$lastUpdate = getOption('last_update', 'Never');

$updateResult = null;
$updateLog = [];

// Handle update (legacy form handler - keeping for compatibility)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf() && $zipAvailable) {
    // Reinforce limits
    @set_time_limit(0);
    @ini_set('max_execution_time', '0');
    @ini_set('memory_limit', '512M');
    ignore_user_abort(true);
    
    // Disable output buffering for real-time feedback
    while (ob_get_level()) ob_end_clean();
    
    // Send headers to prevent timeout
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-cache');
    header('X-Accel-Buffering: no'); // Disable nginx buffering
    
    // Keep connection alive function
    $keepAliveCounter = 0;
    $keepAlive = function() use (&$keepAliveCounter) {
        $keepAliveCounter++;
        if ($keepAliveCounter % 5 === 0) {
            echo ' ';
            if (function_exists('ob_flush')) @ob_flush();
            @flush();
        }
    };
    
    // Flush initial content to start the response
    echo '<!-- Update in progress -->';
    if (function_exists('ob_flush')) @ob_flush();
    @flush();
    
    try {
        // Check file upload
        if (!isset($_FILES['update_file']) || $_FILES['update_file']['error'] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize in php.ini',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            ];
            $error = $_FILES['update_file']['error'] ?? UPLOAD_ERR_NO_FILE;
            throw new Exception($errorMessages[$error] ?? 'Upload failed with error code: ' . $error);
        }
        
        $file = $_FILES['update_file'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if ($extension !== 'zip') {
            throw new Exception('Please upload a ZIP file');
        }
        
        $updateLog[] = '✓ File uploaded: ' . $file['name'] . ' (' . formatFileSize($file['size']) . ')';
        $keepAlive();
        
        // Setup directories
        $backupsDir = CMS_ROOT . '/backups';
        $tempDir = $backupsDir . '/temp_update_' . uniqid();
        $backupDir = $backupsDir . '/backup_' . date('Y-m-d_H-i-s');
        
        // Create backups directory
        if (!is_dir($backupsDir)) {
            if (!@mkdir($backupsDir, 0755, true)) {
                throw new Exception('Cannot create backups directory. Check permissions.');
            }
        }
        
        // Extract zip
        $zip = new ZipArchive();
        $zipResult = $zip->open($file['tmp_name']);
        if ($zipResult !== true) {
            throw new Exception('Failed to open ZIP file. Error code: ' . $zipResult);
        }
        
        if (!@mkdir($tempDir, 0755, true)) {
            $zip->close();
            throw new Exception('Cannot create temporary directory');
        }
        
        if (!$zip->extractTo($tempDir)) {
            $zip->close();
            safeDelete($tempDir);
            throw new Exception('Failed to extract ZIP contents');
        }
        $zip->close();
        
        $updateLog[] = '✓ Extracted ZIP file';
        $keepAlive();
        
        // Find CMS root in extracted files (handle nested directories)
        $extractedRoot = $tempDir;
        $items = @scandir($tempDir);
        if ($items) {
            foreach ($items as $item) {
                if ($item !== '.' && $item !== '..' && is_dir($tempDir . '/' . $item)) {
                    // Check for CMS structure
                    if (file_exists($tempDir . '/' . $item . '/includes/config.php') || 
                        file_exists($tempDir . '/' . $item . '/admin/index.php') ||
                        file_exists($tempDir . '/' . $item . '/index.php')) {
                        $extractedRoot = $tempDir . '/' . $item;
                        $updateLog[] = '✓ Found CMS root in: ' . $item;
                        break;
                    }
                }
            }
        }
        
        // Verify it's a valid CMS package
        if (!file_exists($extractedRoot . '/index.php') && !file_exists($extractedRoot . '/admin/index.php')) {
            safeDelete($tempDir);
            throw new Exception('Invalid CMS package - could not find core files');
        }
        
        // Files/directories to preserve (never overwrite)
        $preserve = [
            'includes/config.php',
            'uploads',
            'backups',
            '.installed',
            '.htaccess',
        ];
        
        // Create backup of preserved files
        if (!@mkdir($backupDir, 0755, true)) {
            safeDelete($tempDir);
            throw new Exception('Cannot create backup directory');
        }
        
        foreach ($preserve as $item) {
            $source = CMS_ROOT . '/' . $item;
            $dest = $backupDir . '/' . $item;
            
            if (file_exists($source)) {
                safeCopy($source, $dest);
            }
        }
        
        $updateLog[] = '✓ Created backup: ' . basename($backupDir);
        $keepAlive();
        
        // Copy new files (skip preserved items)
        $fileCount = 0;
        $dirCount = 0;
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($extractedRoot, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $item) {
            $relativePath = $iterator->getSubPathName();
            $destPath = CMS_ROOT . '/' . $relativePath;
            
            // Check if this path should be preserved
            $shouldPreserve = false;
            foreach ($preserve as $preservePath) {
                if ($relativePath === $preservePath || strpos($relativePath, $preservePath . '/') === 0) {
                    $shouldPreserve = true;
                    break;
                }
            }
            
            if ($shouldPreserve) {
                continue;
            }
            
            if ($item->isDir()) {
                if (!is_dir($destPath)) {
                    @mkdir($destPath, 0755, true);
                    $dirCount++;
                }
            } else {
                $destDir = dirname($destPath);
                if (!is_dir($destDir)) {
                    @mkdir($destDir, 0755, true);
                }
                if (@copy($item->getPathname(), $destPath)) {
                    $fileCount++;
                }
            }
        }
        
        $updateLog[] = '✓ Installed ' . $fileCount . ' files, ' . $dirCount . ' directories';
        $keepAlive();
        
        // Restore preserved files from backup
        foreach ($preserve as $item) {
            $source = $backupDir . '/' . $item;
            $dest = CMS_ROOT . '/' . $item;
            
            if (file_exists($source)) {
                safeCopy($source, $dest);
            }
        }
        
        $updateLog[] = '✓ Restored configuration files';
        
        // Run database migrations
        $updateLog[] = '✓ Running database migrations...';
        $migrationLog = runMigrations();
        $updateLog = array_merge($updateLog, $migrationLog);
        
        // Clean up temp directory
        safeDelete($tempDir);
        $updateLog[] = '✓ Cleaned up temporary files';
        
        // Update version info in database
        try {
            setOption('last_update', date('Y-m-d H:i:s'));
            
            // Try to read new version from config
            $configContent = @file_get_contents(CMS_ROOT . '/includes/config.php');
            if ($configContent && preg_match("/define\s*\(\s*'CMS_VERSION'\s*,\s*'([^']+)'/", $configContent, $matches)) {
                setOption('cms_version', $matches[1]);
                $updateLog[] = '✓ Updated to version: ' . $matches[1];
            }
        } catch (Exception $e) {
            $updateLog[] = '⚠ Could not update version info: ' . $e->getMessage();
        }
        
        $updateResult = 'success';
        $updateLog[] = '';
        $updateLog[] = '🎉 Update completed successfully!';
        $updateLog[] = '';
        $updateLog[] = '⚠️ Please refresh this page to load the new version.';
        
    } catch (Exception $e) {
        $updateResult = 'error';
        $updateLog[] = '';
        $updateLog[] = '❌ Error: ' . $e->getMessage();
        
        // Clean up on error
        if (isset($tempDir) && is_dir($tempDir)) {
            safeDelete($tempDir);
        }
        
        // Try to restore from backup
        if (isset($backupDir) && is_dir($backupDir)) {
            $updateLog[] = '';
            $updateLog[] = '⚠️ Attempting to restore from backup...';
            
            foreach ($preserve as $item) {
                $source = $backupDir . '/' . $item;
                $dest = CMS_ROOT . '/' . $item;
                
                if (file_exists($source)) {
                    safeCopy($source, $dest);
                }
            }
            
            $updateLog[] = '✓ Restored preserved files from backup';
        }
    }
}

include ADMIN_PATH . '/includes/header.php';
?>

<!-- Update Result -->
<?php if ($updateResult): ?>
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header" style="background: <?= $updateResult === 'success' ? 'rgba(16, 185, 129, 0.1)' : 'rgba(239, 68, 68, 0.1)' ?>;">
        <h3 class="card-title" style="color: <?= $updateResult === 'success' ? '#10b981' : '#ef4444' ?>;">
            <?= $updateResult === 'success' ? '✓ Update Successful' : '✕ Update Failed' ?>
        </h3>
    </div>
    <div class="card-body">
        <pre style="background: #0f172a; color: #e2e8f0; padding: 1.25rem; border-radius: 8px; font-family: 'Monaco', 'Consolas', monospace; font-size: 0.8125rem; line-height: 1.8; overflow-x: auto; white-space: pre-wrap; word-wrap: break-word; max-height: 400px; overflow-y: auto;"><?= implode("\n", array_map('htmlspecialchars', $updateLog)) ?></pre>
        
        <?php if ($updateResult === 'success'): ?>
        <div style="margin-top: 1rem;">
            <a href="<?= ADMIN_URL ?>/update.php" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="23 4 23 10 17 10"></polyline>
                    <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                </svg>
                Refresh Page
            </a>
            <a href="<?= ADMIN_URL ?>/" class="btn btn-secondary">Go to Dashboard</a>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Current Status -->
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <h3 class="card-title">System Status</h3>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem;">
            <div style="padding: 1rem; background: var(--bg-card-header); border-radius: var(--border-radius);">
                <div style="font-size: 0.8125rem; color: var(--text-secondary); margin-bottom: 0.25rem;">Current Version</div>
                <div style="font-size: 1.25rem; font-weight: 700; color: var(--forge-primary);"><?= CMS_VERSION ?></div>
            </div>
            <div style="padding: 1rem; background: var(--bg-card-header); border-radius: var(--border-radius);">
                <div style="font-size: 0.8125rem; color: var(--text-secondary); margin-bottom: 0.25rem;">Last Update</div>
                <div style="font-size: 1.125rem; font-weight: 600;"><?= $lastUpdate !== 'Never' ? formatDate($lastUpdate, 'M j, Y g:i A') : 'Never' ?></div>
            </div>
            <div style="padding: 1rem; background: var(--bg-card-header); border-radius: var(--border-radius);">
                <div style="font-size: 0.8125rem; color: var(--text-secondary); margin-bottom: 0.25rem;">PHP Version</div>
                <div style="font-size: 1.125rem; font-weight: 600;"><?= PHP_VERSION ?></div>
            </div>
            <div style="padding: 1rem; background: var(--bg-card-header); border-radius: var(--border-radius);">
                <div style="font-size: 0.8125rem; color: var(--text-secondary); margin-bottom: 0.25rem;">ZipArchive</div>
                <div style="font-size: 1.125rem; font-weight: 600; color: <?= $zipAvailable ? '#10b981' : '#ef4444' ?>;">
                    <?= $zipAvailable ? '✓ Available' : '✕ Missing' ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Update Upload -->
<?php if ($updateResult !== 'success'): ?>
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <h3 class="card-title">Upload Update</h3>
    </div>
    <div class="card-body">
        <?php if (!$zipAvailable): ?>
            <div class="alert alert-warning" style="margin-bottom: 1rem;">
                <strong>⚠️ PHP ZipArchive Extension Required</strong><br>
                The ZipArchive extension is not enabled. To enable it on XAMPP:
                <ol style="margin: 0.75rem 0 0 1.25rem; line-height: 1.8;">
                    <li>Open <code style="background: rgba(0,0,0,0.1); padding: 0.125rem 0.375rem; border-radius: 4px;">C:\xampp\php\php.ini</code></li>
                    <li>Find the line <code style="background: rgba(0,0,0,0.1); padding: 0.125rem 0.375rem; border-radius: 4px;">;extension=zip</code></li>
                    <li>Remove the semicolon to make it <code style="background: rgba(0,0,0,0.1); padding: 0.125rem 0.375rem; border-radius: 4px;">extension=zip</code></li>
                    <li>Restart Apache in XAMPP Control Panel</li>
                </ol>
            </div>
        <?php else: ?>

        <!-- Server Diagnostics -->
        <div class="card" style="margin-bottom: 1rem; background: var(--bg-card-header);">
            <div class="card-body" style="padding: 1rem;">
                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem;">
                    <div>
                        <strong style="font-size: 0.8125rem;">Server Diagnostics</strong>
                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">
                            PHP: <?= $diagnostics['php_sapi'] ?> | 
                            Timeout: <?= $diagnostics['max_execution_time'] ?>s | 
                            Memory: <?= $diagnostics['memory_limit'] ?> |
                            Upload: <?= $diagnostics['upload_max_filesize'] ?>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="runTimeoutTest()">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        Test Timeout
                    </button>
                </div>
                <div id="timeoutResult" style="display: none; margin-top: 0.75rem; padding: 0.75rem; background: var(--bg-card); border-radius: var(--border-radius); font-size: 0.8125rem;"></div>
            </div>
        </div>

        <!-- Update Progress -->
        <div id="updateProgress" style="display: none; margin-bottom: 1rem;">
            <div style="background: var(--bg-card-header); border-radius: var(--border-radius-lg); padding: 1.5rem;">
                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                    <div id="progressSpinner" style="width: 40px; height: 40px; border: 3px solid var(--border-color); border-top-color: var(--forge-primary); border-radius: 50%; animation: spin 1s linear infinite;"></div>
                    <div>
                        <div id="progressTitle" style="font-weight: 600; font-size: 1rem;">Uploading...</div>
                        <div id="progressSubtitle" style="font-size: 0.8125rem; color: var(--text-secondary);">Please wait</div>
                    </div>
                </div>
                <div id="progressLog" style="background: var(--bg-card); border-radius: var(--border-radius); padding: 1rem; font-family: monospace; font-size: 0.8125rem; max-height: 200px; overflow-y: auto;"></div>
            </div>
        </div>

        <!-- Upload Form -->
        <div id="uploadForm">
            <div class="upload-zone" id="updateDropZone" onclick="document.getElementById('updateFile').click()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="17 8 12 3 7 8"></polyline>
                    <line x1="12" y1="3" x2="12" y2="15"></line>
                </svg>
                <p>Drag and drop your <strong>Forge CMS ZIP file</strong> here</p>
                <p>or <span>click to browse</span></p>
                <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.5rem;">
                    Your uploads, configuration, and database will be preserved
                </p>
            </div>
            
            <input type="file" name="update_file" id="updateFile" accept=".zip" style="display: none;" onchange="handleFileSelect(this)">
            
            <div id="selectedFile" style="display: none; padding: 1rem; background: var(--bg-card-header); border: 1px solid var(--border-color); border-radius: var(--border-radius); margin-top: 1rem;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--forge-primary); flex-shrink: 0;">
                        <path d="M21 8v13H3V8"></path>
                        <path d="M1 3h22v5H1z"></path>
                        <path d="M10 12h4"></path>
                    </svg>
                    <div style="flex: 1; min-width: 0;">
                        <div id="fileName" style="font-weight: 600; overflow: hidden; text-overflow: ellipsis;"></div>
                        <div id="fileSize" style="font-size: 0.8125rem; color: var(--text-secondary);"></div>
                    </div>
                    <button type="button" onclick="clearFile()" class="btn btn-sm" style="flex-shrink: 0;">
                        Change
                    </button>
                </div>
            </div>

            <div style="display: flex; gap: 0.75rem; margin-top: 1rem;">
                <button type="button" class="btn btn-primary" id="installBtn" disabled onclick="startUpdate()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="16 16 12 12 8 16"></polyline>
                        <line x1="12" y1="12" x2="12" y2="21"></line>
                        <path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"></path>
                    </svg>
                    <span id="btnText">Install Update</span>
                </button>
            </div>
        </div>

        <!-- Success Message -->
        <div id="updateSuccess" style="display: none; text-align: center; padding: 2rem;">
            <div style="width: 64px; height: 64px; background: rgba(16, 185, 129, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
            </div>
            <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem;">Update Complete!</h3>
            <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">Forge CMS has been updated successfully.</p>
            <button onclick="location.reload()" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="23 4 23 10 17 10"></polyline>
                    <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                </svg>
                Refresh Page
            </button>
        </div>

        <!-- Error Message -->
        <div id="updateError" style="display: none;">
            <div class="alert alert-error" style="margin-bottom: 1rem;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="15" y1="9" x2="9" y2="15"></line>
                    <line x1="9" y1="9" x2="15" y2="15"></line>
                </svg>
                <div>
                    <strong>Update Failed</strong>
                    <p id="errorMessage" style="margin: 0.25rem 0 0 0;"></p>
                </div>
            </div>
            <button onclick="resetUpdate()" class="btn btn-secondary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="1 4 1 10 7 10"></polyline>
                    <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                </svg>
                Try Again
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Instructions -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Update Instructions</h3>
    </div>
    <div class="card-body" style="color: var(--text-secondary); line-height: 1.7;">
        <ol style="padding-left: 1.25rem; display: flex; flex-direction: column; gap: 0.5rem;">
            <li>Download the latest Forge CMS ZIP file</li>
            <li>Drag and drop the ZIP file to the upload area above, or click to browse</li>
            <li>Click "Install Update" to begin the process</li>
            <li>Wait for the update to complete — <strong>do not close this page</strong></li>
            <li>After completion, click "Refresh Page" to load the new version</li>
        </ol>
        <div style="margin-top: 1rem; padding: 1rem; background: var(--bg-card-header); border-radius: var(--border-radius);">
            <strong>⚡ What gets preserved:</strong>
            <ul style="padding-left: 1.25rem; margin-top: 0.5rem;">
                <li>Your database and all content</li>
                <li>Your configuration (includes/config.php)</li>
                <li>All uploaded media (uploads/)</li>
                <li>Previous backups (backups/)</li>
                <li>Your .htaccess rules</li>
            </ul>
        </div>
    </div>
</div>

<!-- Manual Update Instructions -->
<div class="card" style="margin-top: 1.5rem;" id="manualUpdateCard">
    <div class="card-header" style="cursor: pointer;" onclick="document.getElementById('manualInstructions').style.display = document.getElementById('manualInstructions').style.display === 'none' ? 'block' : 'none'">
        <h3 class="card-title" style="display: flex; align-items: center; gap: 0.5rem;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
            </svg>
            Manual Update (if automatic fails)
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-left: auto;" id="manualChevron">
                <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
        </h3>
    </div>
    <div id="manualInstructions" class="card-body" style="display: none; color: var(--text-secondary); line-height: 1.7;">
        <div class="alert alert-info" style="margin-bottom: 1.5rem;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="16" x2="12" y2="12"></line>
                <line x1="12" y1="8" x2="12.01" y2="8"></line>
            </svg>
            <div>
                <strong>Why does this happen?</strong>
                <p style="margin: 0.25rem 0 0 0;">Your hosting provider enforces a timeout (usually 3-30 seconds) at the server level. This cannot be overridden by PHP or .htaccess. Manual update via FTP or File Manager is the solution.</p>
            </div>
        </div>
        
        <h4 style="font-size: 1rem; font-weight: 600; margin-bottom: 1rem; color: var(--text-primary);">📋 Quick Steps:</h4>
        
        <div style="background: var(--bg-card-header); border-radius: var(--border-radius-lg); padding: 1.25rem; margin-bottom: 1rem;">
            <div style="display: flex; align-items: flex-start; gap: 1rem; margin-bottom: 1rem;">
                <div style="width: 28px; height: 28px; background: var(--forge-primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; flex-shrink: 0;">1</div>
                <div>
                    <strong style="color: var(--text-primary);">Extract ZIP on your computer</strong>
                    <p style="margin: 0.25rem 0 0 0; font-size: 0.875rem;">Unzip the forge-cms-v*.zip file locally</p>
                </div>
            </div>
            
            <div style="display: flex; align-items: flex-start; gap: 1rem; margin-bottom: 1rem;">
                <div style="width: 28px; height: 28px; background: var(--forge-primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; flex-shrink: 0;">2</div>
                <div>
                    <strong style="color: var(--text-primary);">Upload via File Manager or FTP</strong>
                    <p style="margin: 0.25rem 0 0 0; font-size: 0.875rem;">Upload all files to your CMS folder. <strong>Skip these files:</strong></p>
                    <ul style="margin: 0.5rem 0 0 1rem; font-size: 0.8125rem; color: var(--text-muted);">
                        <li><code>includes/config.php</code> (keep yours)</li>
                        <li><code>uploads/</code> folder (keep yours)</li>
                    </ul>
                </div>
            </div>
            
            <div style="display: flex; align-items: flex-start; gap: 1rem;">
                <div style="width: 28px; height: 28px; background: var(--forge-primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; flex-shrink: 0;">3</div>
                <div>
                    <strong style="color: var(--text-primary);">Run database migrations</strong>
                    <p style="margin: 0.25rem 0 0 0; font-size: 0.875rem;">Click the button below or visit the URL:</p>
                    <div style="margin-top: 0.75rem; display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        <a href="?run_migrations=1" class="btn btn-primary btn-sm" target="_blank">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                                <polyline points="15 3 21 3 21 9"></polyline>
                                <line x1="10" y1="14" x2="21" y2="3"></line>
                            </svg>
                            Run Migrations
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <details style="margin-top: 1rem;">
            <summary style="cursor: pointer; font-weight: 600; color: var(--text-primary);">📁 Need help with FTP?</summary>
            <div style="margin-top: 0.75rem; padding-left: 1rem; border-left: 2px solid var(--border-color);">
                <p style="margin-bottom: 0.5rem;"><strong>Free FTP clients:</strong></p>
                <ul style="padding-left: 1rem; font-size: 0.875rem;">
                    <li><a href="https://filezilla-project.org/" target="_blank" style="color: var(--forge-primary);">FileZilla</a> — Works on Windows, Mac, Linux</li>
                    <li><a href="https://cyberduck.io/" target="_blank" style="color: var(--forge-primary);">Cyberduck</a> — Great for Mac and Windows</li>
                </ul>
                <p style="margin-top: 0.75rem; font-size: 0.875rem;"><strong>Or use your hosting's File Manager</strong> — Most hosting control panels (cPanel, Plesk, etc.) have a built-in File Manager that lets you upload and extract ZIP files directly.</p>
            </div>
        </details>
    </div>
</div>

<style>
.upload-zone {
    border: 2px dashed var(--border-color);
    border-radius: var(--border-radius-lg);
    padding: 3rem 2rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s ease;
    background: var(--bg-card-header);
}

.upload-zone:hover,
.upload-zone.dragover {
    border-color: var(--forge-primary);
    background: rgba(99, 102, 241, 0.05);
}

.upload-zone svg {
    width: 48px;
    height: 48px;
    color: var(--text-muted);
    margin-bottom: 1rem;
}

.upload-zone p {
    margin: 0.25rem 0;
    color: var(--text-secondary);
}

.upload-zone span {
    color: var(--forge-primary);
    font-weight: 600;
    text-decoration: underline;
}
</style>

<script>
const csrfToken = '<?= csrfToken() ?>';
const dropZone = document.getElementById('updateDropZone');
const fileInput = document.getElementById('updateFile');
const selectedFile = document.getElementById('selectedFile');
const fileName = document.getElementById('fileName');
const fileSize = document.getElementById('fileSize');
const installBtn = document.getElementById('installBtn');
const btnText = document.getElementById('btnText');

let selectedFileObj = null;

if (dropZone) {
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('dragover');
    });

    dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('dragover');
    });

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
        if (e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;
            handleFileSelect(fileInput);
        }
    });
}

function handleFileSelect(input) {
    if (input.files.length) {
        const file = input.files[0];
        if (!file.name.toLowerCase().endsWith('.zip')) {
            alert('Please select a ZIP file');
            input.value = '';
            return;
        }
        
        selectedFileObj = file;
        fileName.textContent = file.name;
        fileSize.textContent = formatBytes(file.size);
        selectedFile.style.display = 'block';
        dropZone.style.display = 'none';
        installBtn.disabled = false;
    }
}

function clearFile() {
    fileInput.value = '';
    selectedFileObj = null;
    selectedFile.style.display = 'none';
    dropZone.style.display = 'block';
    installBtn.disabled = true;
}

function formatBytes(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Timeout test
async function runTimeoutTest() {
    const resultDiv = document.getElementById('timeoutResult');
    resultDiv.style.display = 'block';
    resultDiv.innerHTML = '<span style="color: var(--text-muted);">Testing server timeout (5 seconds)...</span>';
    
    const startTime = Date.now();
    
    try {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 10000); // 10s client timeout
        
        const response = await fetch('?test_timeout=1&duration=5', {
            signal: controller.signal
        });
        
        clearTimeout(timeoutId);
        const elapsed = ((Date.now() - startTime) / 1000).toFixed(2);
        
        if (response.ok) {
            const data = await response.json();
            if (data.completed) {
                resultDiv.innerHTML = `<span style="color: #10b981;">✓ Server completed ${data.elapsed}s test successfully. No timeout issues detected.</span>`;
            } else {
                resultDiv.innerHTML = `<span style="color: #f59e0b;">⚠ Server only ran for ${data.elapsed}s of ${data.requested}s requested.</span>`;
            }
        } else {
            resultDiv.innerHTML = `<span style="color: #ef4444;">✗ Server returned error after ${elapsed}s. Status: ${response.status}</span>`;
        }
    } catch (error) {
        const elapsed = ((Date.now() - startTime) / 1000).toFixed(2);
        if (error.name === 'AbortError') {
            resultDiv.innerHTML = `<span style="color: #ef4444;">✗ Request timed out after ${elapsed}s (client-side limit)</span>`;
        } else {
            resultDiv.innerHTML = `<span style="color: #ef4444;">✗ Connection failed after ${elapsed}s: ${error.message}<br>This indicates a server-level timeout (Apache/nginx/proxy) that cannot be changed via PHP.</span>`;
        }
    }
}

// Progress logging
function addLog(message, isError = false) {
    const log = document.getElementById('progressLog');
    const color = isError ? '#ef4444' : 'inherit';
    log.innerHTML += `<div style="color: ${color}; margin-bottom: 0.25rem;">${message}</div>`;
    log.scrollTop = log.scrollHeight;
}

function setProgress(title, subtitle) {
    document.getElementById('progressTitle').textContent = title;
    document.getElementById('progressSubtitle').textContent = subtitle;
}

// Main update function using chunked AJAX
async function startUpdate() {
    if (!selectedFileObj) return;
    
    // Show progress UI
    document.getElementById('uploadForm').style.display = 'none';
    document.getElementById('updateProgress').style.display = 'block';
    document.getElementById('progressLog').innerHTML = '';
    
    const startTime = Date.now();
    
    try {
        // Step 1: Upload file
        setProgress('Uploading...', 'Sending file to server');
        addLog('Starting upload: ' + selectedFileObj.name + ' (' + formatBytes(selectedFileObj.size) + ')');
        
        const formData = new FormData();
        formData.append('ajax_update', '1');
        formData.append('csrf_token', csrfToken);
        formData.append('update_file', selectedFileObj);
        
        let uploadResponse;
        try {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 30000); // 30s timeout
            
            uploadResponse = await fetch('', {
                method: 'POST',
                body: formData,
                signal: controller.signal
            });
            
            clearTimeout(timeoutId);
        } catch (error) {
            const elapsed = ((Date.now() - startTime) / 1000).toFixed(1);
            if (error.name === 'AbortError') {
                throw new Error(`Upload timed out after ${elapsed}s. The file may be too large or your server has strict timeout limits.`);
            }
            throw new Error(`Connection lost after ${elapsed}s. Your server likely has a timeout limit that cannot be changed via PHP. Try the manual update method below.`);
        }
        
        if (!uploadResponse.ok) {
            throw new Error(`Server error: ${uploadResponse.status} ${uploadResponse.statusText}`);
        }
        
        let uploadData;
        const responseText = await uploadResponse.text();
        try {
            uploadData = JSON.parse(responseText);
        } catch (e) {
            console.error('Response:', responseText.substring(0, 500));
            throw new Error('Invalid server response. The server may have timed out or crashed. Check error logs.');
        }
        
        if (!uploadData.success) {
            throw new Error(uploadData.error || 'Upload failed');
        }
        
        uploadData.log.forEach(msg => addLog(msg));
        
        // Step 2: Extract ZIP
        setProgress('Extracting...', 'Unpacking update files');
        addLog('Extracting ZIP archive...');
        
        const extractData = new FormData();
        extractData.append('ajax_extract', '1');
        extractData.append('csrf_token', csrfToken);
        extractData.append('zip_path', uploadData.zip_path);
        
        const extractResponse = await fetch('', {
            method: 'POST',
            body: extractData
        });
        
        const extractResult = await extractResponse.json();
        if (!extractResult.success) {
            throw new Error(extractResult.error || 'Extraction failed');
        }
        
        extractResult.log.forEach(msg => addLog(msg));
        
        // Step 3: Install files
        setProgress('Installing...', 'Copying files and running migrations');
        addLog('Installing update...');
        
        const installData = new FormData();
        installData.append('ajax_install', '1');
        installData.append('csrf_token', csrfToken);
        installData.append('extracted_root', extractResult.extracted_root);
        installData.append('backup_dir', extractResult.backup_dir);
        installData.append('temp_dir', extractResult.temp_dir);
        
        const installResponse = await fetch('', {
            method: 'POST',
            body: installData
        });
        
        const installResult = await installResponse.json();
        if (!installResult.success) {
            throw new Error(installResult.error || 'Installation failed');
        }
        
        installResult.log.forEach(msg => addLog(msg));
        
        // Success!
        const totalTime = ((Date.now() - startTime) / 1000).toFixed(1);
        addLog(`\nCompleted in ${totalTime}s`);
        
        document.getElementById('updateProgress').style.display = 'none';
        document.getElementById('updateSuccess').style.display = 'block';
        
    } catch (error) {
        console.error('Update error:', error);
        
        // Detect NetworkError (server killed connection)
        const isNetworkError = error.message.includes('NetworkError') || 
                               error.message.includes('network') ||
                               error.message.includes('Failed to fetch') ||
                               error.name === 'TypeError';
        
        let errorMsg = error.message;
        if (isNetworkError) {
            const elapsed = ((Date.now() - startTime) / 1000).toFixed(1);
            errorMsg = `Server connection lost after ${elapsed}s. Your hosting provider has a strict timeout limit that cannot be bypassed. Please use the Manual Update method below.`;
        }
        
        addLog('✗ ' + errorMsg, true);
        
        document.getElementById('progressSpinner').style.display = 'none';
        setProgress('Update Failed', 'Server timeout detected');
        
        // Show error UI after a moment
        setTimeout(() => {
            document.getElementById('updateProgress').style.display = 'none';
            document.getElementById('updateError').style.display = 'block';
            document.getElementById('errorMessage').innerHTML = errorMsg + 
                (isNetworkError ? '<br><br><strong>→ Scroll down and expand "Manual Update" for step-by-step instructions.</strong>' : '');
            
            // Auto-expand manual instructions if network error
            if (isNetworkError) {
                const manualSection = document.getElementById('manualInstructions');
                if (manualSection) manualSection.style.display = 'block';
            }
        }, 1500);
    }
}

function resetUpdate() {
    document.getElementById('updateError').style.display = 'none';
    document.getElementById('uploadForm').style.display = 'block';
    document.getElementById('progressSpinner').style.display = 'block';
    clearFile();
}
</script>

<style>
@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>

<?php include ADMIN_PATH . '/includes/footer.php'; ?>
