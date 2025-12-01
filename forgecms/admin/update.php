<?php
/**
 * System Updates - Forge CMS
 * Simple synchronous update process
 */

define('CMS_ROOT', dirname(__DIR__));
require_once CMS_ROOT . '/includes/config.php';
require_once CMS_ROOT . '/includes/database.php';
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/user.php';
require_once CMS_ROOT . '/includes/post.php';
require_once CMS_ROOT . '/includes/media.php';

User::startSession();
User::requireRole('admin');

$zipAvailable = class_exists('ZipArchive');
$pageTitle = 'System Update';
$currentVersion = CMS_VERSION;
$lastUpdate = getOption('last_update', 'Never');

$updateResult = null;
$updateLog = [];

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf() && $zipAvailable) {
    // Increase limits
    set_time_limit(300);
    ini_set('memory_limit', '256M');
    
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
        
        $tempDir = CMS_ROOT . '/backups/temp_update_' . uniqid();
        $backupDir = CMS_ROOT . '/backups/backup_' . date('Y-m-d_H-i-s');
        
        // Create directories
        if (!is_dir(CMS_ROOT . '/backups')) {
            mkdir(CMS_ROOT . '/backups', 0755, true);
        }
        
        // Extract zip
        $zip = new ZipArchive();
        $zipResult = $zip->open($file['tmp_name']);
        if ($zipResult !== true) {
            throw new Exception('Failed to open ZIP file. Error code: ' . $zipResult);
        }
        
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        
        if (!$zip->extractTo($tempDir)) {
            $zip->close();
            throw new Exception('Failed to extract ZIP contents');
        }
        $zip->close();
        
        $updateLog[] = '✓ Extracted ZIP file';
        
        // Find CMS root in extracted files
        $extractedRoot = $tempDir;
        $items = scandir($tempDir);
        foreach ($items as $item) {
            if ($item !== '.' && $item !== '..' && is_dir($tempDir . '/' . $item)) {
                if (file_exists($tempDir . '/' . $item . '/includes/config.php') || 
                    file_exists($tempDir . '/' . $item . '/admin/index.php')) {
                    $extractedRoot = $tempDir . '/' . $item;
                    $updateLog[] = '✓ Found CMS files in: ' . $item;
                    break;
                }
            }
        }
        
        // Files to preserve
        $preserve = [
            'includes/config.php',
            'uploads',
            'backups',
            '.installed',
            '.htaccess',
        ];
        
        // Create backup
        mkdir($backupDir, 0755, true);
        
        foreach ($preserve as $item) {
            $source = CMS_ROOT . '/' . $item;
            $dest = $backupDir . '/' . $item;
            
            if (file_exists($source)) {
                if (is_dir($source)) {
                    recursiveCopy($source, $dest);
                } else {
                    $destDir = dirname($dest);
                    if (!is_dir($destDir)) {
                        mkdir($destDir, 0755, true);
                    }
                    copy($source, $dest);
                }
            }
        }
        
        $updateLog[] = '✓ Created backup: ' . basename($backupDir);
        
        // Copy new files
        $fileCount = 0;
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
                    mkdir($destPath, 0755, true);
                }
            } else {
                $destDir = dirname($destPath);
                if (!is_dir($destDir)) {
                    mkdir($destDir, 0755, true);
                }
                copy($item, $destPath);
                $fileCount++;
            }
        }
        
        $updateLog[] = '✓ Installed ' . $fileCount . ' files';
        
        // Restore preserved files
        foreach ($preserve as $item) {
            $source = $backupDir . '/' . $item;
            $dest = CMS_ROOT . '/' . $item;
            
            if (file_exists($source)) {
                if (is_dir($source)) {
                    recursiveCopy($source, $dest);
                } else {
                    $destDir = dirname($dest);
                    if (!is_dir($destDir)) {
                        mkdir($destDir, 0755, true);
                    }
                    copy($source, $dest);
                }
            }
        }
        
        $updateLog[] = '✓ Restored configuration';
        
        // Run migrations if they exist
        $migrationsFile = CMS_ROOT . '/includes/migrations.php';
        if (file_exists($migrationsFile)) {
            include $migrationsFile;
            $updateLog[] = '✓ Ran database migrations';
        }
        
        // Clean up temp directory
        recursiveDelete($tempDir);
        $updateLog[] = '✓ Cleaned up temporary files';
        
        // Update version in database
        setOption('cms_version', CMS_VERSION);
        setOption('last_update', date('Y-m-d H:i:s'));
        
        $updateResult = 'success';
        $updateLog[] = '';
        $updateLog[] = '🎉 Update completed successfully!';
        
    } catch (Exception $e) {
        $updateResult = 'error';
        $updateLog[] = '';
        $updateLog[] = '❌ Error: ' . $e->getMessage();
        
        // Clean up on error
        if (isset($tempDir) && is_dir($tempDir)) {
            recursiveDelete($tempDir);
        }
    }
}

/**
 * Copy directory recursively
 */
function recursiveCopy($source, $dest) {
    if (!is_dir($dest)) {
        mkdir($dest, 0755, true);
    }
    
    $dir = opendir($source);
    while (($file = readdir($dir)) !== false) {
        if ($file === '.' || $file === '..') continue;
        
        $srcPath = $source . '/' . $file;
        $dstPath = $dest . '/' . $file;
        
        if (is_dir($srcPath)) {
            recursiveCopy($srcPath, $dstPath);
        } else {
            copy($srcPath, $dstPath);
        }
    }
    closedir($dir);
}

/**
 * Delete directory recursively
 */
function recursiveDelete($dir) {
    if (!is_dir($dir)) {
        return;
    }
    
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    
    foreach ($items as $item) {
        if ($item->isDir()) {
            @rmdir($item);
        } else {
            @unlink($item);
        }
    }
    
    @rmdir($dir);
}

include ADMIN_PATH . '/includes/header.php';
?>

<div class="page-header">
    <h2>System Update</h2>
    <p style="color: var(--text-secondary); margin-top: 0.25rem;">Upload and install new versions of <?= CMS_NAME ?></p>
</div>

<?php if ($updateResult === 'success'): ?>
<!-- Success Result -->
<div class="card" style="margin-bottom: 1.5rem; border-color: var(--forge-success);">
    <div class="card-header" style="background: rgba(16, 185, 129, 0.1);">
        <h3 class="card-title" style="color: var(--forge-success);">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: -4px; margin-right: 0.5rem;">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            Update Successful
        </h3>
    </div>
    <div class="card-body">
        <div style="background: var(--bg-card-header); padding: 1rem; border-radius: var(--border-radius); font-family: monospace; font-size: 0.875rem; line-height: 1.8;">
            <?php foreach ($updateLog as $line): ?>
                <div><?= esc($line) ?></div>
            <?php endforeach; ?>
        </div>
        <div style="margin-top: 1rem;">
            <a href="<?= ADMIN_URL ?>" class="btn btn-primary">Return to Dashboard</a>
            <a href="<?= ADMIN_URL ?>/update.php" class="btn">Upload Another Update</a>
        </div>
    </div>
</div>

<?php elseif ($updateResult === 'error'): ?>
<!-- Error Result -->
<div class="card" style="margin-bottom: 1.5rem; border-color: var(--forge-danger);">
    <div class="card-header" style="background: rgba(239, 68, 68, 0.1);">
        <h3 class="card-title" style="color: var(--forge-danger);">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: -4px; margin-right: 0.5rem;">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="15" y1="9" x2="9" y2="15"></line>
                <line x1="9" y1="9" x2="15" y2="15"></line>
            </svg>
            Update Failed
        </h3>
    </div>
    <div class="card-body">
        <div style="background: var(--bg-card-header); padding: 1rem; border-radius: var(--border-radius); font-family: monospace; font-size: 0.875rem; line-height: 1.8;">
            <?php foreach ($updateLog as $line): ?>
                <div><?= esc($line) ?></div>
            <?php endforeach; ?>
        </div>
        <div style="margin-top: 1rem;">
            <a href="<?= ADMIN_URL ?>/update.php" class="btn btn-primary">Try Again</a>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Current Version Info -->
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <h3 class="card-title">Current Installation</h3>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
            <div>
                <div style="font-size: 0.8125rem; color: var(--text-secondary); margin-bottom: 0.25rem;">Version</div>
                <div style="font-size: 1.5rem; font-weight: 700; color: var(--forge-primary);"><?= esc($currentVersion) ?></div>
            </div>
            <div>
                <div style="font-size: 0.8125rem; color: var(--text-secondary); margin-bottom: 0.25rem;">Last Updated</div>
                <div style="font-size: 1.125rem; font-weight: 600;"><?= $lastUpdate !== 'Never' ? formatDate($lastUpdate, 'M j, Y \a\t H:i') : 'Never' ?></div>
            </div>
            <div>
                <div style="font-size: 0.8125rem; color: var(--text-secondary); margin-bottom: 0.25rem;">PHP Version</div>
                <div style="font-size: 1.125rem; font-weight: 600;"><?= PHP_VERSION ?></div>
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
            <li>After completion, verify your site is working correctly</li>
        </ol>
        <div style="margin-top: 1rem; padding: 1rem; background: var(--bg-card-header); border-radius: var(--border-radius);">
            <strong>⚡ What gets preserved:</strong>
            <ul style="padding-left: 1.25rem; margin-top: 0.5rem;">
                <li>Your configuration (includes/config.php)</li>
                <li>All uploaded media (uploads/)</li>
                <li>Previous backups (backups/)</li>
                <li>Your .htaccess rules</li>
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
        btnText.textContent = 'Installing... Please wait';
        installBtn.style.opacity = '0.7';
    });
}
</script>

<?php include ADMIN_PATH . '/includes/footer.php'; ?>
