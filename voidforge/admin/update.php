<?php
/**
 * System Updates - VoidForge CMS
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
    
    // Show results with proper admin styling
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Database Migrations - VoidForge CMS</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 2rem;
            }
            .migration-card {
                background: #fff;
                border-radius: 20px;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
                max-width: 520px;
                width: 100%;
                overflow: hidden;
            }
            .migration-header {
                background: linear-gradient(135deg, #6366f1, #8b5cf6);
                color: #fff;
                padding: 2rem;
                text-align: center;
            }
            .migration-header .icon {
                width: 64px;
                height: 64px;
                background: rgba(255,255,255,0.2);
                border-radius: 16px;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 1rem;
            }
            .migration-header .icon svg { width: 32px; height: 32px; }
            .migration-header h1 { font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem; }
            .migration-header p { opacity: 0.9; font-size: 0.9375rem; }
            .migration-body { padding: 2rem; }
            .success-badge {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                background: rgba(16, 185, 129, 0.1);
                color: #059669;
                padding: 0.625rem 1rem;
                border-radius: 10px;
                font-weight: 600;
                font-size: 0.9375rem;
                margin-bottom: 1.5rem;
            }
            .success-badge svg { width: 20px; height: 20px; }
            .log-section {
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                padding: 1.25rem;
                margin-bottom: 1.5rem;
            }
            .log-section h3 {
                font-size: 0.75rem;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.05em;
                color: #64748b;
                margin-bottom: 0.75rem;
            }
            .log-item {
                display: flex;
                align-items: center;
                gap: 0.625rem;
                padding: 0.5rem 0;
                font-size: 0.875rem;
                color: #334155;
                border-bottom: 1px solid #e2e8f0;
            }
            .log-item:last-child { border-bottom: none; }
            .log-item svg { width: 16px; height: 16px; color: #10b981; flex-shrink: 0; }
            .btn-back {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                padding: 0.875rem 1.5rem;
                background: linear-gradient(135deg, #6366f1, #8b5cf6);
                color: #fff;
                text-decoration: none;
                border-radius: 10px;
                font-weight: 600;
                font-size: 0.9375rem;
                transition: all 0.2s;
                width: 100%;
                justify-content: center;
            }
            .btn-back:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(99, 102, 241, 0.35);
            }
            .btn-back svg { width: 18px; height: 18px; }
        </style>
    </head>
    <body>
        <div class="migration-card">
            <div class="migration-header">
                <div class="icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                </div>
                <h1>Database Migrations</h1>
                <p>Schema updates have been applied</p>
            </div>
            <div class="migration-body">
                <div class="success-badge">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                    All migrations completed successfully
                </div>
                
                <div class="log-section">
                    <h3>Migration Log</h3>
                    <?php foreach ($migrationLog as $line): ?>
                    <div class="log-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <span><?= htmlspecialchars(str_replace(['→', '✓', '⚠'], '', trim($line))) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <a href="<?= ADMIN_URL ?>/update.php" class="btn-back">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    Back to Updates
                </a>
            </div>
        </div>
    </body>
    </html>
    <?php
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

<style>
/* Update Page Styles */
.update-page {
    max-width: 900px;
    margin: 0 auto;
}

.update-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 2rem;
}

.update-header-left h1 {
    font-size: 1.75rem;
    font-weight: 700;
    margin: 0 0 0.375rem 0;
}

.update-header-left p {
    color: #64748b;
    margin: 0;
}

.update-version-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1rem;
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.1));
    border: 1px solid rgba(99, 102, 241, 0.2);
    border-radius: 12px;
    font-weight: 600;
    color: var(--forge-primary, #6366f1);
}

.update-version-badge svg {
    width: 18px;
    height: 18px;
}

/* Status Grid */
.status-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.status-card {
    padding: 1.25rem;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
}

.status-card-label {
    font-size: 0.8125rem;
    color: #64748b;
    margin-bottom: 0.375rem;
}

.status-card-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1e293b;
}

.status-card-value.success { color: #10b981; }
.status-card-value.error { color: #ef4444; }
.status-card-value.primary { color: var(--forge-primary, #6366f1); }

/* Upload Section */
.upload-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    overflow: hidden;
    margin-bottom: 2rem;
}

.upload-card-header {
    padding: 1.25rem 1.5rem;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.upload-card-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 1rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
}

.upload-card-title svg {
    color: var(--forge-primary, #6366f1);
}

.upload-card-body {
    padding: 1.5rem;
}

/* Drop Zone */
.drop-zone {
    border: 2px dashed #cbd5e1;
    border-radius: 14px;
    padding: 3rem 2rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s ease;
    background: #fafbfc;
}

.drop-zone:hover {
    border-color: var(--forge-primary, #6366f1);
    background: rgba(99, 102, 241, 0.04);
}

.drop-zone.dragover {
    border-color: var(--forge-primary, #6366f1);
    background: rgba(99, 102, 241, 0.08);
    transform: scale(1.01);
}

.drop-zone-icon {
    width: 64px;
    height: 64px;
    border-radius: 16px;
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.15), rgba(139, 92, 246, 0.15));
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.25rem;
    color: var(--forge-primary, #6366f1);
}

.drop-zone-icon svg {
    width: 28px;
    height: 28px;
}

.drop-zone h3 {
    font-size: 1rem;
    font-weight: 600;
    margin: 0 0 0.5rem 0;
    color: #1e293b;
}

.drop-zone p {
    color: #64748b;
    margin: 0;
    font-size: 0.9375rem;
}

.drop-zone .browse-link {
    color: var(--forge-primary, #6366f1);
    font-weight: 500;
}

.drop-zone-hint {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e2e8f0;
    font-size: 0.8125rem;
    color: #94a3b8;
}

/* Selected File */
.selected-file {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.25rem;
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(139, 92, 246, 0.05));
    border: 1px solid rgba(99, 102, 241, 0.15);
    border-radius: 12px;
    margin-top: 1rem;
}

.selected-file-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, var(--forge-primary, #6366f1) 0%, var(--forge-secondary, #8b5cf6) 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    flex-shrink: 0;
}

.selected-file-info {
    flex: 1;
    min-width: 0;
}

.selected-file-name {
    font-weight: 600;
    color: #1e293b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.selected-file-size {
    font-size: 0.8125rem;
    color: #64748b;
}

.selected-file-actions {
    display: flex;
    gap: 0.5rem;
}

/* Progress */
.progress-section {
    padding: 2rem;
    text-align: center;
}

.progress-spinner {
    width: 56px;
    height: 56px;
    border: 3px solid #e2e8f0;
    border-top-color: var(--forge-primary, #6366f1);
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 1.5rem;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.progress-title {
    font-size: 1.125rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.progress-subtitle {
    color: #64748b;
    font-size: 0.9375rem;
}

.progress-log {
    margin-top: 1.5rem;
    padding: 1rem;
    background: #0f172a;
    border-radius: 10px;
    font-family: 'Monaco', 'Consolas', monospace;
    font-size: 0.8125rem;
    color: #e2e8f0;
    text-align: left;
    max-height: 200px;
    overflow-y: auto;
}

/* Success/Error States */
.result-section {
    text-align: center;
    padding: 2rem;
}

.result-icon {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.25rem;
}

.result-icon.success {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(5, 150, 105, 0.15));
    color: #10b981;
}

.result-icon.error {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.15), rgba(220, 38, 38, 0.15));
    color: #ef4444;
}

.result-icon svg {
    width: 32px;
    height: 32px;
}

.result-title {
    font-size: 1.25rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.result-message {
    color: #64748b;
    margin-bottom: 1.5rem;
}

/* Server Info */
.server-info {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.75rem;
    padding: 1rem 1.25rem;
    background: #f8fafc;
    border-radius: 10px;
    margin-bottom: 1rem;
}

.server-info-text {
    font-size: 0.8125rem;
    color: #64748b;
}

.server-info-text strong {
    color: #475569;
}

/* Instructions */
.instructions-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    overflow: hidden;
}

.instructions-header {
    padding: 1rem 1.5rem;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    font-weight: 600;
    color: #1e293b;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.instructions-header:hover {
    background: #f1f5f9;
}

.instructions-body {
    padding: 1.5rem;
}

.instructions-list {
    list-style: none;
    padding: 0;
    margin: 0;
    counter-reset: step;
}

.instructions-list li {
    display: flex;
    gap: 1rem;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f1f5f9;
}

.instructions-list li:last-child {
    border-bottom: none;
}

.instructions-list li::before {
    counter-increment: step;
    content: counter(step);
    width: 28px;
    height: 28px;
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.1));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--forge-primary, #6366f1);
    flex-shrink: 0;
}

.preserved-list {
    margin-top: 1.25rem;
    padding: 1rem;
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.05), rgba(5, 150, 105, 0.05));
    border: 1px solid rgba(16, 185, 129, 0.15);
    border-radius: 10px;
}

.preserved-list h4 {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    margin: 0 0 0.75rem 0;
    color: #059669;
}

.preserved-list ul {
    margin: 0;
    padding-left: 1.25rem;
    color: #475569;
    font-size: 0.875rem;
    line-height: 1.8;
}

/* Alert */
.alert-warning {
    display: flex;
    gap: 0.75rem;
    padding: 1rem;
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(217, 119, 6, 0.1));
    border: 1px solid rgba(245, 158, 11, 0.2);
    border-radius: 10px;
    color: #92400e;
}

.alert-warning svg {
    flex-shrink: 0;
    color: #d97706;
}

/* Buttons */
.btn-install {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.875rem 1.75rem;
    background: linear-gradient(135deg, var(--forge-primary, #6366f1) 0%, var(--forge-secondary, #8b5cf6) 100%);
    color: #fff;
    border: none;
    border-radius: 12px;
    font-family: inherit;
    font-size: 0.9375rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 4px 15px rgba(99, 102, 241, 0.35);
}

.btn-install:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(99, 102, 241, 0.45);
}

.btn-install:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}

.btn-secondary {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1rem;
    background: #fff;
    color: #475569;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    font-family: inherit;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-secondary:hover {
    border-color: var(--forge-primary, #6366f1);
    color: var(--forge-primary, #6366f1);
}

@media (max-width: 640px) {
    .update-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .status-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .server-info {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>

<div class="update-page">
    <div class="update-header">
        <div class="update-header-left">
            <h1>System Update</h1>
            <p>Keep your VoidForge CMS installation up to date</p>
        </div>
        <div class="update-version-badge">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 2v4m0 12v4m-7.07-3.93l2.83-2.83m8.48-8.48l2.83-2.83M2 12h4m12 0h4m-3.93 7.07l-2.83-2.83M6.34 6.34L3.51 3.51"/>
            </svg>
            v<?= CMS_VERSION ?>
        </div>
    </div>
    
    <!-- Status Grid -->
    <div class="status-grid">
        <div class="status-card">
            <div class="status-card-label">Current Version</div>
            <div class="status-card-value primary"><?= CMS_VERSION ?></div>
        </div>
        <div class="status-card">
            <div class="status-card-label">Last Updated</div>
            <div class="status-card-value"><?= $lastUpdate !== 'Never' ? formatDate($lastUpdate, 'M j, Y') : 'Never' ?></div>
        </div>
        <div class="status-card">
            <div class="status-card-label">PHP Version</div>
            <div class="status-card-value"><?= PHP_VERSION ?></div>
        </div>
        <div class="status-card">
            <div class="status-card-label">ZipArchive</div>
            <div class="status-card-value <?= $zipAvailable ? 'success' : 'error' ?>">
                <?= $zipAvailable ? '✓ Ready' : '✕ Missing' ?>
            </div>
        </div>
    </div>
    
    <?php if ($updateResult): ?>
    <!-- Update Result -->
    <div class="upload-card">
        <div class="upload-card-header">
            <h3 class="upload-card-title">
                <?php if ($updateResult === 'success'): ?>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                Update Successful
                <?php else: ?>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="15" y1="9" x2="9" y2="15"></line>
                    <line x1="9" y1="9" x2="15" y2="15"></line>
                </svg>
                Update Failed
                <?php endif; ?>
            </h3>
        </div>
        <div class="upload-card-body">
            <div class="progress-log"><?= implode("\n", array_map('htmlspecialchars', $updateLog)) ?></div>
            <?php if ($updateResult === 'success'): ?>
            <div style="margin-top: 1.5rem; text-align: center;">
                <a href="<?= ADMIN_URL ?>/update.php" class="btn-install">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="23 4 23 10 17 10"></polyline>
                        <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                    </svg>
                    Refresh Page
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($updateResult !== 'success'): ?>
    <!-- Upload Section -->
    <div class="upload-card">
        <div class="upload-card-header">
            <h3 class="upload-card-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="17 8 12 3 7 8"></polyline>
                    <line x1="12" y1="3" x2="12" y2="15"></line>
                </svg>
                Upload Update Package
            </h3>
        </div>
        <div class="upload-card-body">
            <?php if (!$zipAvailable): ?>
            <div class="alert-warning">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                    <line x1="12" y1="9" x2="12" y2="13"></line>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
                <div>
                    <strong>PHP ZipArchive Extension Required</strong>
                    <p style="margin: 0.5rem 0 0 0; font-size: 0.875rem;">
                        Enable the zip extension in your php.ini file and restart your web server.
                    </p>
                </div>
            </div>
            <?php else: ?>
            
            <!-- Server Info -->
            <div class="server-info">
                <div class="server-info-text">
                    <strong>Server:</strong> PHP <?= $diagnostics['php_sapi'] ?> &bull; 
                    <strong>Timeout:</strong> <?= $diagnostics['max_execution_time'] ?>s &bull; 
                    <strong>Memory:</strong> <?= $diagnostics['memory_limit'] ?> &bull; 
                    <strong>Upload:</strong> <?= $diagnostics['upload_max_filesize'] ?>
                </div>
                <button type="button" class="btn-secondary" onclick="runTimeoutTest()">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    Test Server
                </button>
            </div>
            <div id="timeoutResult" style="display: none; padding: 0.75rem 1rem; background: #f8fafc; border-radius: 8px; margin-bottom: 1rem; font-size: 0.875rem;"></div>
            
            <!-- Progress Section (hidden by default) -->
            <div id="updateProgress" style="display: none;">
                <div class="progress-section">
                    <div class="progress-spinner" id="progressSpinner"></div>
                    <div class="progress-title" id="progressTitle">Uploading...</div>
                    <div class="progress-subtitle" id="progressSubtitle">Please wait</div>
                    <div class="progress-log" id="progressLog"></div>
                </div>
            </div>
            
            <!-- Upload Form -->
            <div id="uploadForm">
                <div class="drop-zone" id="updateDropZone" onclick="document.getElementById('updateFile').click()">
                    <div class="drop-zone-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                    </div>
                    <h3>Drop your update ZIP file here</h3>
                    <p>or <span class="browse-link">click to browse</span></p>
                    <div class="drop-zone-hint">
                        Your uploads, configuration, and database will be preserved
                    </div>
                </div>
                
                <input type="file" name="update_file" id="updateFile" accept=".zip" style="display: none;" onchange="handleFileSelect(this)">
                
                <div id="selectedFile" class="selected-file" style="display: none;">
                    <div class="selected-file-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 8v13H3V8"></path>
                            <path d="M1 3h22v5H1z"></path>
                            <path d="M10 12h4"></path>
                        </svg>
                    </div>
                    <div class="selected-file-info">
                        <div class="selected-file-name" id="fileName"></div>
                        <div class="selected-file-size" id="fileSize"></div>
                    </div>
                    <div class="selected-file-actions">
                        <button type="button" class="btn-secondary" onclick="clearFile()">Change</button>
                    </div>
                </div>
                
                <div style="margin-top: 1.5rem; display: flex; gap: 0.75rem;">
                    <button type="button" class="btn-install" id="installBtn" disabled onclick="startUpdate()">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="16 16 12 12 8 16"></polyline>
                            <line x1="12" y1="12" x2="12" y2="21"></line>
                            <path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"></path>
                        </svg>
                        <span id="btnText">Install Update</span>
                    </button>
                </div>
            </div>
            
            <!-- Success -->
            <div id="updateSuccess" class="result-section" style="display: none;">
                <div class="result-icon success">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                </div>
                <div class="result-title">Update Complete!</div>
                <div class="result-message">VoidForge CMS has been updated successfully.</div>
                <button onclick="location.reload()" class="btn-install">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="23 4 23 10 17 10"></polyline>
                        <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                    </svg>
                    Refresh Page
                </button>
            </div>
            
            <!-- Error -->
            <div id="updateError" class="result-section" style="display: none;">
                <div class="result-icon error">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                </div>
                <div class="result-title">Update Failed</div>
                <div class="result-message" id="errorMessage"></div>
                <button onclick="resetUpdate()" class="btn-secondary">
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
    <div class="instructions-card">
        <div class="instructions-header" onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display === 'none' ? 'block' : 'none'">
            <span>Update Instructions</span>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
        </div>
        <div class="instructions-body">
            <ol class="instructions-list">
                <li>Download the latest VoidForge CMS ZIP file</li>
                <li>Drag and drop the ZIP to the upload area above</li>
                <li>Click "Install Update" to begin</li>
                <li>Wait for the process to complete</li>
                <li>Click "Refresh Page" to load the new version</li>
            </ol>
            <div class="preserved-list">
                <h4>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    </svg>
                    Files Preserved During Update
                </h4>
                <ul>
                    <li>Database and all content</li>
                    <li>Configuration (includes/config.php)</li>
                    <li>Uploaded media (uploads/)</li>
                    <li>Backups (backups/)</li>
                    <li>.htaccess rules</li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Manual Update -->
    <div class="instructions-card" style="margin-top: 1rem;">
        <div class="instructions-header" onclick="toggleManual(this)">
            <span>Manual Update (FTP)</span>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
        </div>
        <div class="instructions-body" id="manualInstructions" style="display: none;">
            <p style="margin: 0 0 1rem 0; color: #64748b;">If automatic updates fail, follow these steps:</p>
            <ol class="instructions-list">
                <li>Download and extract the VoidForge CMS ZIP</li>
                <li>Back up your config.php and uploads folder</li>
                <li>Upload all files via FTP (except config.php and uploads/)</li>
                <li>
                    Run migrations: 
                    <a href="?run_migrations=1" style="color: #6366f1; font-weight: 500;">Click here to run database migrations</a>
                </li>
            </ol>
        </div>
    </div>
</div>

<script>
const csrfToken = '<?= csrfToken() ?>';
let selectedFileObj = null;

function toggleManual(el) {
    const body = el.nextElementSibling;
    body.style.display = body.style.display === 'none' ? 'block' : 'none';
}

// Drag and drop
const dropZone = document.getElementById('updateDropZone');
if (dropZone) {
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(evt => {
        dropZone.addEventListener(evt, e => {
            e.preventDefault();
            e.stopPropagation();
        });
    });
    
    ['dragenter', 'dragover'].forEach(evt => {
        dropZone.addEventListener(evt, () => dropZone.classList.add('dragover'));
    });
    
    ['dragleave', 'drop'].forEach(evt => {
        dropZone.addEventListener(evt, () => dropZone.classList.remove('dragover'));
    });
    
    dropZone.addEventListener('drop', e => {
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            document.getElementById('updateFile').files = files;
            handleFileSelect(document.getElementById('updateFile'));
        }
    });
}

function handleFileSelect(input) {
    const file = input.files[0];
    if (!file) return;
    
    if (!file.name.toLowerCase().endsWith('.zip')) {
        alert('Please select a ZIP file');
        return;
    }
    
    selectedFileObj = file;
    document.getElementById('fileName').textContent = file.name;
    document.getElementById('fileSize').textContent = formatBytes(file.size);
    document.getElementById('selectedFile').style.display = 'flex';
    document.getElementById('updateDropZone').style.display = 'none';
    document.getElementById('installBtn').disabled = false;
}

function clearFile() {
    selectedFileObj = null;
    document.getElementById('updateFile').value = '';
    document.getElementById('selectedFile').style.display = 'none';
    document.getElementById('updateDropZone').style.display = 'block';
    document.getElementById('installBtn').disabled = true;
}

function formatBytes(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

async function runTimeoutTest() {
    const resultDiv = document.getElementById('timeoutResult');
    resultDiv.style.display = 'block';
    resultDiv.innerHTML = '<span style="color: #64748b;">Testing server timeout (5 seconds)...</span>';
    
    const startTime = Date.now();
    
    try {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 10000);
        
        const response = await fetch('?test_timeout=1&duration=5', {
            signal: controller.signal
        });
        
        clearTimeout(timeoutId);
        const elapsed = ((Date.now() - startTime) / 1000).toFixed(2);
        
        if (response.ok) {
            const data = await response.json();
            if (data.completed) {
                resultDiv.innerHTML = `<span style="color: #10b981;">✓ Server completed ${data.elapsed}s test. Ready for updates.</span>`;
            } else {
                resultDiv.innerHTML = `<span style="color: #f59e0b;">⚠ Server only ran for ${data.elapsed}s of ${data.requested}s.</span>`;
            }
        } else {
            resultDiv.innerHTML = `<span style="color: #ef4444;">✗ Server error after ${elapsed}s.</span>`;
        }
    } catch (error) {
        const elapsed = ((Date.now() - startTime) / 1000).toFixed(2);
        if (error.name === 'AbortError') {
            resultDiv.innerHTML = `<span style="color: #ef4444;">✗ Request timed out after ${elapsed}s.</span>`;
        } else {
            resultDiv.innerHTML = `<span style="color: #ef4444;">✗ Connection failed after ${elapsed}s.</span>`;
        }
    }
}

function addLog(message, isError = false) {
    const log = document.getElementById('progressLog');
    const color = isError ? '#ef4444' : '#e2e8f0';
    log.innerHTML += `<div style="color: ${color}; margin-bottom: 0.25rem;">${message}</div>`;
    log.scrollTop = log.scrollHeight;
}

function setProgress(title, subtitle) {
    document.getElementById('progressTitle').textContent = title;
    document.getElementById('progressSubtitle').textContent = subtitle;
}

async function startUpdate() {
    if (!selectedFileObj) return;
    
    document.getElementById('uploadForm').style.display = 'none';
    document.getElementById('updateProgress').style.display = 'block';
    document.getElementById('progressLog').innerHTML = '';
    
    const startTime = Date.now();
    
    try {
        setProgress('Uploading...', 'Sending file to server');
        addLog('Starting upload: ' + selectedFileObj.name + ' (' + formatBytes(selectedFileObj.size) + ')');
        
        const formData = new FormData();
        formData.append('ajax_update', '1');
        formData.append('csrf_token', csrfToken);
        formData.append('update_file', selectedFileObj);
        
        let uploadResponse;
        try {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 30000);
            
            uploadResponse = await fetch('', {
                method: 'POST',
                body: formData,
                signal: controller.signal
            });
            
            clearTimeout(timeoutId);
        } catch (error) {
            const elapsed = ((Date.now() - startTime) / 1000).toFixed(1);
            if (error.name === 'AbortError') {
                throw new Error(`Upload timed out after ${elapsed}s.`);
            }
            throw new Error(`Connection lost after ${elapsed}s. Try manual update.`);
        }
        
        if (!uploadResponse.ok) {
            throw new Error(`Server error: ${uploadResponse.status}`);
        }
        
        let uploadData;
        const responseText = await uploadResponse.text();
        try {
            uploadData = JSON.parse(responseText);
        } catch (e) {
            throw new Error('Invalid server response.');
        }
        
        if (!uploadData.success) {
            throw new Error(uploadData.error || 'Upload failed');
        }
        
        uploadData.log.forEach(msg => addLog(msg));
        
        setProgress('Extracting...', 'Unpacking update files');
        addLog('Extracting ZIP archive...');
        
        const extractData = new FormData();
        extractData.append('ajax_extract', '1');
        extractData.append('csrf_token', csrfToken);
        extractData.append('zip_path', uploadData.zip_path);
        
        const extractResponse = await fetch('', { method: 'POST', body: extractData });
        const extractResult = await extractResponse.json();
        
        if (!extractResult.success) {
            throw new Error(extractResult.error || 'Extraction failed');
        }
        
        extractResult.log.forEach(msg => addLog(msg));
        
        setProgress('Installing...', 'Copying files and running migrations');
        addLog('Installing update...');
        
        const installData = new FormData();
        installData.append('ajax_install', '1');
        installData.append('csrf_token', csrfToken);
        installData.append('extracted_root', extractResult.extracted_root);
        installData.append('backup_dir', extractResult.backup_dir);
        installData.append('temp_dir', extractResult.temp_dir);
        
        const installResponse = await fetch('', { method: 'POST', body: installData });
        const installResult = await installResponse.json();
        
        if (!installResult.success) {
            throw new Error(installResult.error || 'Installation failed');
        }
        
        installResult.log.forEach(msg => addLog(msg));
        
        const totalTime = ((Date.now() - startTime) / 1000).toFixed(1);
        addLog(`\nCompleted in ${totalTime}s`);
        
        document.getElementById('updateProgress').style.display = 'none';
        document.getElementById('updateSuccess').style.display = 'block';
        
    } catch (error) {
        console.error('Update error:', error);
        
        addLog('✗ ' + error.message, true);
        
        document.getElementById('progressSpinner').style.display = 'none';
        setProgress('Update Failed', '');
        
        setTimeout(() => {
            document.getElementById('updateProgress').style.display = 'none';
            document.getElementById('updateError').style.display = 'block';
            document.getElementById('errorMessage').innerHTML = error.message;
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

<?php include ADMIN_PATH . '/includes/footer.php'; ?>
