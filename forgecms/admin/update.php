<?php
/**
 * System Updates - Forge CMS
 * Improved update process with better error handling
 */

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

$zipAvailable = class_exists('ZipArchive');
$pageTitle = 'System Update';
$currentVersion = CMS_VERSION;
$lastUpdate = getOption('last_update', 'Never');

$updateResult = null;
$updateLog = [];

/**
 * Safely run database migrations
 */
function runMigrations(): array {
    $log = [];
    
    try {
        // Ensure database connection
        $pdo = Database::getInstance();
        
        // Create media_folders table if it doesn't exist
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS media_folders (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                created_at DATETIME NOT NULL,
                INDEX idx_name (name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $log[] = '  → Checked media_folders table';
        
        // Add folder_id column to media table if it doesn't exist
        $columns = $pdo->query("SHOW COLUMNS FROM media LIKE 'folder_id'")->fetchAll();
        if (empty($columns)) {
            $pdo->exec("ALTER TABLE media ADD COLUMN folder_id INT UNSIGNED DEFAULT NULL");
            $pdo->exec("ALTER TABLE media ADD INDEX idx_folder_id (folder_id)");
            $log[] = '  → Added folder_id column to media';
        }
        
        // Add title column to media table if it doesn't exist
        $columns = $pdo->query("SHOW COLUMNS FROM media LIKE 'title'")->fetchAll();
        if (empty($columns)) {
            $pdo->exec("ALTER TABLE media ADD COLUMN title VARCHAR(255) DEFAULT NULL AFTER alt_text");
            $log[] = '  → Added title column to media';
        }
        
        // Add caption column to media table if it doesn't exist
        $columns = $pdo->query("SHOW COLUMNS FROM media LIKE 'caption'")->fetchAll();
        if (empty($columns)) {
            $pdo->exec("ALTER TABLE media ADD COLUMN caption TEXT DEFAULT NULL AFTER title");
            $log[] = '  → Added caption column to media';
        }
        
        // Ensure options table exists
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS options (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                option_name VARCHAR(255) NOT NULL UNIQUE,
                option_value LONGTEXT,
                INDEX idx_name (option_name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $log[] = '  → Checked options table';
        
        // Ensure custom_post_types table exists
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS custom_post_types (
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

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf() && $zipAvailable) {
    // Increase limits significantly
    set_time_limit(600);
    ini_set('memory_limit', '512M');
    ini_set('max_execution_time', '600');
    
    // Disable output buffering for real-time feedback
    while (ob_get_level()) ob_end_clean();
    
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

        <form method="POST" enctype="multipart/form-data" id="updateForm">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            
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
                <button type="submit" class="btn btn-primary" id="installBtn" disabled>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="16 16 12 12 8 16"></polyline>
                        <line x1="12" y1="12" x2="12" y2="21"></line>
                        <path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"></path>
                    </svg>
                    <span id="btnText">Install Update</span>
                </button>
            </div>
        </form>
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
        <div style="margin-top: 1rem; padding: 1rem; background: rgba(239, 68, 68, 0.1); border-radius: var(--border-radius); border-left: 4px solid #ef4444;">
            <strong>⚠️ Important:</strong>
            <ul style="padding-left: 1.25rem; margin-top: 0.5rem;">
                <li>Always backup your database before updating</li>
                <li>If update fails, your preserved files will be restored automatically</li>
                <li>Check the backups/ directory for timestamped backups</li>
            </ul>
        </div>
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
const dropZone = document.getElementById('updateDropZone');
const fileInput = document.getElementById('updateFile');
const selectedFile = document.getElementById('selectedFile');
const fileName = document.getElementById('fileName');
const fileSize = document.getElementById('fileSize');
const installBtn = document.getElementById('installBtn');
const btnText = document.getElementById('btnText');
const updateForm = document.getElementById('updateForm');

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
        
        fileName.textContent = file.name;
        fileSize.textContent = formatBytes(file.size);
        selectedFile.style.display = 'block';
        dropZone.style.display = 'none';
        installBtn.disabled = false;
    }
}

function clearFile() {
    fileInput.value = '';
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

// Show loading state on submit
if (updateForm) {
    updateForm.addEventListener('submit', function() {
        installBtn.disabled = true;
        btnText.textContent = 'Installing... Please wait (this may take a minute)';
        installBtn.style.opacity = '0.7';
    });
}
</script>

<?php include ADMIN_PATH . '/includes/footer.php'; ?>
