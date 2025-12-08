<?php
/**
 * Media Library Management with Folder Support and Thumbnails
 */

defined('CMS_ROOT') or die('Direct access not allowed');

class Media
{
    public const ALLOWED_TYPES = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'svg' => 'image/svg+xml',
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'zip' => 'application/zip',
        'txt' => 'text/plain',
    ];

    public const MAX_FILE_SIZE = 10485760; // 10MB
    
    /**
     * Default thumbnail sizes (cannot be removed, but can be disabled)
     * Format: name => [width, height, crop, enabled]
     */
    public const DEFAULT_THUMBNAIL_SIZES = [
        'thumbnail' => [150, 150, true, true],    // Square crop
        'small'     => [300, 300, false, true],   // Fit within
        'medium'    => [600, 600, false, true],   // Fit within
        'large'     => [1200, 1200, false, true], // Fit within
    ];
    
    /** @var array|null Cached thumbnail sizes */
    private static $thumbnailSizes = null;
    
    /**
     * Get all thumbnail sizes (defaults + custom, respecting enabled status)
     */
    public static function getThumbnailSizes(bool $includeDisabled = false): array
    {
        if (self::$thumbnailSizes === null) {
            self::$thumbnailSizes = self::loadThumbnailSizes();
        }
        
        if ($includeDisabled) {
            return self::$thumbnailSizes;
        }
        
        // Filter to only enabled sizes
        return array_filter(self::$thumbnailSizes, function($size) {
            return !isset($size[3]) || $size[3] === true;
        });
    }
    
    /**
     * Load thumbnail sizes from database
     */
    private static function loadThumbnailSizes(): array
    {
        $sizes = self::DEFAULT_THUMBNAIL_SIZES;
        
        // Load custom sizes and overrides from options
        $customSizes = getOption('thumbnail_sizes');
        if ($customSizes) {
            $custom = json_decode($customSizes, true);
            if (is_array($custom)) {
                // Merge custom sizes with defaults (custom overrides defaults)
                foreach ($custom as $name => $config) {
                    $sizes[$name] = $config;
                }
            }
        }
        
        return $sizes;
    }
    
    /**
     * Save thumbnail sizes to database
     */
    public static function saveThumbnailSizes(array $sizes): bool
    {
        // Validate sizes
        foreach ($sizes as $name => $config) {
            if (!is_array($config) || count($config) < 3) {
                return false;
            }
        }
        
        setOption('thumbnail_sizes', json_encode($sizes));
        self::$thumbnailSizes = null; // Clear cache
        return true;
    }
    
    /**
     * Add or update a thumbnail size
     */
    public static function setThumbnailSize(string $name, int $width, int $height, bool $crop = false, bool $enabled = true): bool
    {
        $sizes = self::getThumbnailSizes(true);
        $sizes[$name] = [$width, $height, $crop, $enabled];
        return self::saveThumbnailSizes($sizes);
    }
    
    /**
     * Enable or disable a thumbnail size
     */
    public static function toggleThumbnailSize(string $name, bool $enabled): bool
    {
        $sizes = self::getThumbnailSizes(true);
        if (!isset($sizes[$name])) {
            return false;
        }
        $sizes[$name][3] = $enabled;
        return self::saveThumbnailSizes($sizes);
    }
    
    /**
     * Remove a custom thumbnail size (cannot remove defaults)
     */
    public static function removeThumbnailSize(string $name): bool
    {
        // Cannot remove default sizes
        if (isset(self::DEFAULT_THUMBNAIL_SIZES[$name])) {
            return false;
        }
        
        $sizes = self::getThumbnailSizes(true);
        if (!isset($sizes[$name])) {
            return false;
        }
        
        unset($sizes[$name]);
        return self::saveThumbnailSizes($sizes);
    }
    
    /**
     * Check if a size is a default (cannot be removed)
     */
    public static function isDefaultSize(string $name): bool
    {
        return isset(self::DEFAULT_THUMBNAIL_SIZES[$name]);
    }

    /**
     * Get single media item
     * @return array|null
     */
    public static function get(int $id)
    {
        $table = Database::table('media');
        $media = Database::queryOne("SELECT * FROM {$table} WHERE id = ?", [$id]);
        
        if ($media) {
            $media['url'] = self::getUrl($media);
            $media['path'] = self::getPath($media);
            $media['thumbnails'] = self::getThumbnails($media);
        }
        
        return $media;
    }

    /**
     * Alias for get()
     * @return array|null
     */
    public static function find(int $id)
    {
        return self::get($id);
    }

    /**
     * Get all media, optionally filtered by folder
     */
    public static function getAll($folderId = null): array
    {
        $table = Database::table('media');
        $where = '1=1';
        $params = [];
        
        if ($folderId !== null) {
            $where .= ' AND folder_id = ?';
            $params[] = $folderId;
        }
        
        $items = Database::query(
            "SELECT * FROM {$table} WHERE {$where} ORDER BY created_at DESC",
            $params
        );

        foreach ($items as &$item) {
            $item['url'] = self::getUrl($item);
            $item['path'] = self::getPath($item);
        }

        return $items;
    }

    /**
     * Query media with filters
     */
    public static function query(array $args = []): array
    {
        $defaults = [
            'type' => null,
            'folder_id' => null,
            'search' => null,
            'orderby' => 'created_at',
            'order' => 'DESC',
            'limit' => null,
            'offset' => 0,
        ];

        $args = array_merge($defaults, $args);
        $where = ['1=1'];
        $params = [];

        if ($args['type'] === 'image') {
            $where[] = "mime_type LIKE 'image/%'";
        }

        if ($args['folder_id'] !== null) {
            $where[] = 'folder_id = ?';
            $params[] = $args['folder_id'];
        }

        if ($args['search']) {
            $where[] = '(filename LIKE ? OR alt_text LIKE ? OR title LIKE ?)';
            $searchTerm = '%' . $args['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $whereClause = implode(' AND ', $where);
        $orderClause = $args['orderby'] . ' ' . $args['order'];
        $table = Database::table('media');

        $sql = "SELECT * FROM {$table} WHERE {$whereClause} ORDER BY {$orderClause}";

        if ($args['limit']) {
            $sql .= " LIMIT {$args['limit']} OFFSET {$args['offset']}";
        }

        $items = Database::query($sql, $params);

        foreach ($items as &$item) {
            $item['url'] = self::getUrl($item);
            $item['path'] = self::getPath($item);
        }

        return $items;
    }

    /**
     * Count media
     */
    public static function count(array $args = []): int
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($args['type']) && $args['type'] === 'image') {
            $where[] = "mime_type LIKE 'image/%'";
        }

        if (isset($args['folder_id'])) {
            $where[] = 'folder_id = ?';
            $params[] = $args['folder_id'];
        }

        $whereClause = implode(' AND ', $where);
        $table = Database::table('media');
        return (int) Database::queryValue("SELECT COUNT(*) FROM {$table} WHERE {$whereClause}", $params);
    }

    /**
     * Upload a file
     */
    public static function upload(array $file, ?int $userId = null, int $folderId = 0): array
    {
        $errors = [];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = self::getUploadError($file['error']);
            return ['success' => false, 'error' => implode(', ', $errors)];
        }

        if ($file['size'] > self::MAX_FILE_SIZE) {
            return ['success' => false, 'error' => 'File size exceeds maximum limit of ' . formatFileSize(self::MAX_FILE_SIZE)];
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!isset(self::ALLOWED_TYPES[$extension])) {
            return ['success' => false, 'error' => 'File type not allowed: ' . $extension];
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        
        // SVG files might be detected as text/plain or application/xml
        if ($extension === 'svg' && !in_array($mimeType, ['image/svg+xml', 'text/plain', 'application/xml', 'text/xml'])) {
            return ['success' => false, 'error' => 'Invalid SVG file'];
        }

        $filename = self::generateFilename($file['name']);
        $subdir = date('Y/m');
        $uploadDir = UPLOADS_PATH . '/' . $subdir;
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filepath = $subdir . '/' . $filename;
        $fullPath = UPLOADS_PATH . '/' . $filepath;

        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            return ['success' => false, 'error' => 'Failed to save uploaded file'];
        }

        $width = null;
        $height = null;
        if (strpos($mimeType, 'image/') === 0 && $extension !== 'svg') {
            $dimensions = @getimagesize($fullPath);
            if ($dimensions) {
                $width = $dimensions[0];
                $height = $dimensions[1];
            }
        }

        $id = Database::insert(Database::table('media'), [
            'filename' => $filename,
            'filepath' => $filepath,
            'mime_type' => $mimeType,
            'filesize' => $file['size'],
            'width' => $width,
            'height' => $height,
            'title' => pathinfo($file['name'], PATHINFO_FILENAME),
            'alt_text' => '',
            'folder_id' => $folderId > 0 ? $folderId : null,
            'uploaded_by' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Generate thumbnails for images
        $media = self::get($id);
        if ($media && self::isImage($media) && $extension !== 'svg') {
            self::generateThumbnails($media);
        }

        return [
            'success' => true,
            'id' => $id,
            'media' => self::get($id),
        ];
    }

    /**
     * Update media metadata
     */
    public static function update(int $id, array $data): array
    {
        $updateData = [];

        if (isset($data['alt_text'])) {
            $updateData['alt_text'] = $data['alt_text'];
        }
        if (isset($data['title'])) {
            $updateData['title'] = $data['title'];
        }
        if (isset($data['folder_id'])) {
            $updateData['folder_id'] = $data['folder_id'] > 0 ? $data['folder_id'] : null;
        }

        if (empty($updateData)) {
            return ['success' => false, 'error' => 'No data to update'];
        }

        Database::update(Database::table('media'), $updateData, 'id = ?', [$id]);
        return ['success' => true];
    }

    /**
     * Delete media
     */
    public static function delete(int $id): array
    {
        $media = self::get($id);
        if (!$media) {
            return ['success' => false, 'error' => 'Media not found'];
        }

        // Delete thumbnails first
        self::deleteThumbnails($media);

        // Delete main file
        $path = self::getPath($media);
        if (file_exists($path)) {
            @unlink($path);
        }

        $postsTable = Database::table('posts');
        Database::execute(
            "UPDATE {$postsTable} SET featured_image_id = NULL WHERE featured_image_id = ?",
            [$id]
        );

        Database::delete(Database::table('media'), 'id = ?', [$id]);
        return ['success' => true];
    }

    /**
     * Get all folders
     */
    public static function getFolders(): array
    {
        $table = Database::table('media_folders');
        $folders = Database::query("SELECT * FROM {$table} ORDER BY name ASC");
        
        foreach ($folders as &$folder) {
            $folder['count'] = self::count(['folder_id' => $folder['id']]);
        }
        
        return $folders;
    }

    /**
     * Create folder
     */
    public static function createFolder(string $name): array
    {
        $name = trim($name);
        if (empty($name)) {
            return ['success' => false, 'error' => 'Folder name is required'];
        }

        $table = Database::table('media_folders');
        $existing = Database::queryOne(
            "SELECT id FROM {$table} WHERE name = ?",
            [$name]
        );

        if ($existing) {
            return ['success' => false, 'error' => 'A folder with this name already exists'];
        }

        $id = Database::insert(Database::table('media_folders'), [
            'name' => $name,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return ['success' => true, 'id' => $id];
    }

    /**
     * Delete folder
     */
    public static function deleteFolder(int $id): array
    {
        // Move all media in folder to root
        $mediaTable = Database::table('media');
        Database::execute(
            "UPDATE {$mediaTable} SET folder_id = NULL WHERE folder_id = ?",
            [$id]
        );

        Database::delete(Database::table('media_folders'), 'id = ?', [$id]);
        return ['success' => true];
    }

    /**
     * Get full URL for media
     */
    public static function getUrl(array $media): string
    {
        if (empty($media['filepath'])) {
            return '';
        }
        return UPLOADS_URL . '/' . $media['filepath'];
    }

    /**
     * Get full path for media
     */
    public static function getPath(array $media): string
    {
        if (empty($media['filepath'])) {
            return '';
        }
        return UPLOADS_PATH . '/' . $media['filepath'];
    }

    /**
     * Get all thumbnail URLs for a media item
     */
    public static function getThumbnails(array $media): array
    {
        if (!self::isImage($media)) {
            return [];
        }
        
        if (!empty($media['mime_type']) && strpos($media['mime_type'], 'svg') !== false) {
            return [];
        }
        
        if (empty($media['filepath'])) {
            return [];
        }
        
        $thumbnails = [];
        $pathInfo = pathinfo($media['filepath']);
        
        if (empty($pathInfo['filename']) || empty($pathInfo['extension'])) {
            return [];
        }
        
        $thumbDir = ($pathInfo['dirname'] ?? '.') . '/thumbs';
        $baseName = $pathInfo['filename'];
        $ext = $pathInfo['extension'];
        
        foreach (self::getThumbnailSizes() as $size => $dims) {
            $thumbPath = $thumbDir . '/' . $baseName . '-' . $size . '.' . $ext;
            $fullPath = UPLOADS_PATH . '/' . $thumbPath;
            
            if (file_exists($fullPath)) {
                $thumbnails[$size] = UPLOADS_URL . '/' . $thumbPath;
            }
        }
        
        return $thumbnails;
    }

    /**
     * Get a specific thumbnail URL, with fallback to original
     */
    public static function getThumbnailUrl(array $media, string $size = 'medium'): string
    {
        // Safety checks for required fields
        if (empty($media['filepath'])) {
            return '';
        }
        
        if (!self::isImage($media)) {
            return self::getUrl($media);
        }
        
        // SVGs don't need thumbnails
        if (!empty($media['mime_type']) && strpos($media['mime_type'], 'svg') !== false) {
            return self::getUrl($media);
        }
        
        $pathInfo = pathinfo($media['filepath']);
        
        // Check required path components exist
        if (empty($pathInfo['filename']) || empty($pathInfo['extension'])) {
            return self::getUrl($media);
        }
        
        $thumbDir = ($pathInfo['dirname'] ?? '.') . '/thumbs';
        $baseName = $pathInfo['filename'];
        $ext = $pathInfo['extension'];
        
        $thumbPath = $thumbDir . '/' . $baseName . '-' . $size . '.' . $ext;
        $fullPath = UPLOADS_PATH . '/' . $thumbPath;
        
        if (file_exists($fullPath)) {
            return UPLOADS_URL . '/' . $thumbPath;
        }
        
        // Try to generate on the fly if missing
        $sizes = self::getThumbnailSizes();
        if (isset($sizes[$size])) {
            self::generateThumbnail($media, $size);
            if (file_exists($fullPath)) {
                return UPLOADS_URL . '/' . $thumbPath;
            }
        }
        
        // Fall back to original
        return self::getUrl($media);
    }

    /**
     * Generate all thumbnails for a media item
     */
    public static function generateThumbnails(array $media): bool
    {
        if (!self::isImage($media)) {
            return false;
        }
        
        if (!empty($media['mime_type']) && strpos($media['mime_type'], 'svg') !== false) {
            return false;
        }
        
        if (empty($media['filepath'])) {
            return false;
        }
        
        $success = true;
        foreach (self::getThumbnailSizes() as $size => $dims) {
            if (!self::generateThumbnail($media, $size)) {
                $success = false;
            }
        }
        
        return $success;
    }

    /**
     * Generate a single thumbnail
     */
    public static function generateThumbnail(array $media, string $size): bool
    {
        $sizes = self::getThumbnailSizes();
        if (!isset($sizes[$size])) {
            error_log("Forge Thumbnail: Size '$size' not found in configured sizes");
            return false;
        }
        
        if (empty($media['filepath'])) {
            error_log("Forge Thumbnail: Media has no filepath");
            return false;
        }
        
        // Check GD library
        if (!extension_loaded('gd')) {
            error_log("Forge Thumbnail: GD library not loaded");
            return false;
        }
        
        $sourcePath = self::getPath($media);
        if (empty($sourcePath)) {
            error_log("Forge Thumbnail: Could not get source path");
            return false;
        }
        
        if (!file_exists($sourcePath)) {
            error_log("Forge Thumbnail: Source file does not exist: $sourcePath");
            return false;
        }
        
        $sizeConfig = $sizes[$size];
        $maxWidth = (int)$sizeConfig[0];
        $maxHeight = (int)$sizeConfig[1];
        $crop = isset($sizeConfig[2]) ? (bool)$sizeConfig[2] : false;
        
        $pathInfo = pathinfo($media['filepath']);
        
        if (empty($pathInfo['filename']) || empty($pathInfo['extension'])) {
            error_log("Forge Thumbnail: Invalid path info for: " . $media['filepath']);
            return false;
        }
        
        $thumbDir = UPLOADS_PATH . '/' . ($pathInfo['dirname'] ?? '.');
        $thumbDir .= '/thumbs';
        $baseName = $pathInfo['filename'];
        $ext = strtolower($pathInfo['extension']);
        
        // Create thumbs directory
        if (!is_dir($thumbDir)) {
            if (!@mkdir($thumbDir, 0755, true)) {
                error_log("Forge Thumbnail: Could not create directory: $thumbDir");
                return false;
            }
        }
        
        // Check if directory is writable
        if (!is_writable($thumbDir)) {
            error_log("Forge Thumbnail: Directory not writable: $thumbDir");
            return false;
        }
        
        $thumbPath = $thumbDir . '/' . $baseName . '-' . $size . '.' . $ext;
        
        // Get source image info
        $imageInfo = @getimagesize($sourcePath);
        if (!$imageInfo) {
            error_log("Forge Thumbnail: Could not get image size for: $sourcePath");
            return false;
        }
        
        [$srcWidth, $srcHeight, $type] = $imageInfo;
        
        // Don't upscale - if source is smaller than target, just copy
        if ($srcWidth <= $maxWidth && $srcHeight <= $maxHeight && !$crop) {
            $result = @copy($sourcePath, $thumbPath);
            if (!$result) {
                error_log("Forge Thumbnail: Failed to copy small image to: $thumbPath");
            }
            return $result;
        }
        
        // Create source image resource
        $sourceImage = null;
        switch ($type) {
            case IMAGETYPE_JPEG:
                if (function_exists('imagecreatefromjpeg')) {
                    $sourceImage = @imagecreatefromjpeg($sourcePath);
                }
                break;
            case IMAGETYPE_PNG:
                if (function_exists('imagecreatefrompng')) {
                    $sourceImage = @imagecreatefrompng($sourcePath);
                }
                break;
            case IMAGETYPE_GIF:
                if (function_exists('imagecreatefromgif')) {
                    $sourceImage = @imagecreatefromgif($sourcePath);
                }
                break;
            case IMAGETYPE_WEBP:
                if (function_exists('imagecreatefromwebp')) {
                    $sourceImage = @imagecreatefromwebp($sourcePath);
                }
                break;
        }
        
        if (!$sourceImage) {
            error_log("Forge Thumbnail: Could not create image resource for type $type from: $sourcePath");
            return false;
        }
        
        // Calculate dimensions
        if ($crop) {
            // Crop to exact dimensions (center crop)
            $srcRatio = $srcWidth / $srcHeight;
            $destRatio = $maxWidth / $maxHeight;
            
            if ($srcRatio > $destRatio) {
                // Source is wider - crop sides
                $cropWidth = (int)($srcHeight * $destRatio);
                $cropHeight = $srcHeight;
                $cropX = (int)(($srcWidth - $cropWidth) / 2);
                $cropY = 0;
            } else {
                // Source is taller - crop top/bottom
                $cropWidth = $srcWidth;
                $cropHeight = (int)($srcWidth / $destRatio);
                $cropX = 0;
                $cropY = (int)(($srcHeight - $cropHeight) / 2);
            }
            
            $destWidth = $maxWidth;
            $destHeight = $maxHeight;
        } else {
            // Fit within dimensions (maintain aspect ratio)
            $ratio = min($maxWidth / $srcWidth, $maxHeight / $srcHeight);
            $destWidth = max(1, (int)($srcWidth * $ratio));
            $destHeight = max(1, (int)($srcHeight * $ratio));
            $cropX = 0;
            $cropY = 0;
            $cropWidth = $srcWidth;
            $cropHeight = $srcHeight;
        }
        
        // Create destination image
        $destImage = @imagecreatetruecolor($destWidth, $destHeight);
        if (!$destImage) {
            error_log("Forge Thumbnail: Could not create destination image {$destWidth}x{$destHeight}");
            imagedestroy($sourceImage);
            return false;
        }
        
        // Preserve transparency for PNG and GIF
        if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_GIF) {
            imagealphablending($destImage, false);
            imagesavealpha($destImage, true);
            $transparent = imagecolorallocatealpha($destImage, 0, 0, 0, 127);
            imagefill($destImage, 0, 0, $transparent);
        }
        
        // Resize/crop
        $resampleResult = @imagecopyresampled(
            $destImage, $sourceImage,
            0, 0, $cropX, $cropY,
            $destWidth, $destHeight, $cropWidth, $cropHeight
        );
        
        if (!$resampleResult) {
            error_log("Forge Thumbnail: imagecopyresampled failed");
            imagedestroy($sourceImage);
            imagedestroy($destImage);
            return false;
        }
        
        // Save thumbnail
        $result = false;
        switch ($type) {
            case IMAGETYPE_JPEG:
                $result = @imagejpeg($destImage, $thumbPath, 85);
                break;
            case IMAGETYPE_PNG:
                $result = @imagepng($destImage, $thumbPath, 8);
                break;
            case IMAGETYPE_GIF:
                $result = @imagegif($destImage, $thumbPath);
                break;
            case IMAGETYPE_WEBP:
                if (function_exists('imagewebp')) {
                    $result = @imagewebp($destImage, $thumbPath, 85);
                }
                break;
        }
        
        // Cleanup
        imagedestroy($sourceImage);
        imagedestroy($destImage);
        
        if (!$result) {
            error_log("Forge Thumbnail: Failed to save thumbnail to: $thumbPath");
        }
        
        return $result;
    }
    
    /**
     * Get diagnostic information about thumbnail system
     */
    public static function getThumbnailDiagnostics(): array
    {
        $diagnostics = [
            'gd_loaded' => extension_loaded('gd'),
            'gd_info' => function_exists('gd_info') ? gd_info() : [],
            'uploads_path' => UPLOADS_PATH,
            'uploads_writable' => is_writable(UPLOADS_PATH),
            'thumbnail_sizes' => self::getThumbnailSizes(),
            'supported_formats' => [],
        ];
        
        if ($diagnostics['gd_loaded'] && function_exists('gd_info')) {
            $gdInfo = gd_info();
            $diagnostics['supported_formats'] = [
                'jpeg' => !empty($gdInfo['JPEG Support']),
                'png' => !empty($gdInfo['PNG Support']),
                'gif' => !empty($gdInfo['GIF Read Support']) && !empty($gdInfo['GIF Create Support']),
                'webp' => !empty($gdInfo['WebP Support']),
            ];
        }
        
        return $diagnostics;
    }
    
    /**
     * Get all thumbnails for all media with status
     */
    public static function getAllThumbnailsStatus(): array
    {
        $media = self::query(['type' => 'image']);
        $sizes = self::getThumbnailSizes();
        $results = [];
        
        foreach ($media as $item) {
            // Skip SVGs
            if (!empty($item['mime_type']) && strpos($item['mime_type'], 'svg') !== false) {
                continue;
            }
            
            $pathInfo = pathinfo($item['filepath'] ?? '');
            if (empty($pathInfo['filename']) || empty($pathInfo['extension'])) {
                continue;
            }
            
            $thumbDir = ($pathInfo['dirname'] ?? '.') . '/thumbs';
            $baseName = $pathInfo['filename'];
            $ext = $pathInfo['extension'];
            
            $thumbStatus = [];
            foreach ($sizes as $sizeName => $sizeConfig) {
                $thumbPath = UPLOADS_PATH . '/' . $thumbDir . '/' . $baseName . '-' . $sizeName . '.' . $ext;
                $thumbUrl = UPLOADS_URL . '/' . $thumbDir . '/' . $baseName . '-' . $sizeName . '.' . $ext;
                
                $exists = file_exists($thumbPath);
                $thumbStatus[$sizeName] = [
                    'exists' => $exists,
                    'path' => $thumbPath,
                    'url' => $exists ? $thumbUrl : null,
                    'size' => $exists ? filesize($thumbPath) : 0,
                    'dimensions' => $sizeConfig[0] . 'x' . $sizeConfig[1],
                    'crop' => !empty($sizeConfig[2]),
                ];
            }
            
            $results[] = [
                'id' => $item['id'],
                'filename' => $item['filename'],
                'filepath' => $item['filepath'],
                'original_url' => $item['url'] ?? self::getUrl($item),
                'mime_type' => $item['mime_type'],
                'dimensions' => ($item['width'] ?? '?') . 'x' . ($item['height'] ?? '?'),
                'thumbnails' => $thumbStatus,
            ];
        }
        
        return $results;
    }

    /**
     * Delete thumbnails for a media item
     */
    public static function deleteThumbnails(array $media): void
    {
        $pathInfo = pathinfo($media['filepath']);
        $thumbDir = UPLOADS_PATH . '/' . $pathInfo['dirname'] . '/thumbs';
        $baseName = $pathInfo['filename'];
        $ext = $pathInfo['extension'];
        
        foreach (self::getThumbnailSizes(true) as $size => $dims) {
            $thumbPath = $thumbDir . '/' . $baseName . '-' . $size . '.' . $ext;
            if (file_exists($thumbPath)) {
                @unlink($thumbPath);
            }
        }
    }
    
    /**
     * Delete all thumbnails for all media
     */
    public static function deleteAllThumbnails(): array
    {
        $deleted = 0;
        $errors = 0;
        
        // Find all thumbs directories in uploads
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(UPLOADS_PATH, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isDir() && $file->getFilename() === 'thumbs') {
                $thumbDir = $file->getPathname();
                $files = glob($thumbDir . '/*');
                foreach ($files as $thumbFile) {
                    if (is_file($thumbFile)) {
                        if (@unlink($thumbFile)) {
                            $deleted++;
                        } else {
                            $errors++;
                        }
                    }
                }
            }
        }
        
        return ['deleted' => $deleted, 'errors' => $errors];
    }
    
    /**
     * Regenerate thumbnails for a single media item
     */
    public static function regenerateThumbnails(int $id): array
    {
        $media = self::get($id);
        if (!$media) {
            return ['success' => false, 'error' => 'Media not found'];
        }
        
        if (!self::isImage($media)) {
            return ['success' => false, 'error' => 'Not an image'];
        }
        
        if (!empty($media['mime_type']) && strpos($media['mime_type'], 'svg') !== false) {
            return ['success' => false, 'error' => 'SVG images do not need thumbnails'];
        }
        
        // Delete existing thumbnails first
        self::deleteThumbnails($media);
        
        // Regenerate
        $success = self::generateThumbnails($media);
        
        return [
            'success' => $success,
            'message' => $success ? 'Thumbnails regenerated successfully' : 'Some thumbnails failed to generate'
        ];
    }

    /**
     * Regenerate all thumbnails for all media
     */
    public static function regenerateAllThumbnails(): array
    {
        $media = self::query(['type' => 'image']);
        $success = 0;
        $failed = 0;
        
        foreach ($media as $item) {
            // Skip SVGs
            if (strpos($item['mime_type'], 'svg') !== false) {
                continue;
            }
            
            if (self::generateThumbnails($item)) {
                $success++;
            } else {
                $failed++;
            }
        }
        
        return ['success' => $success, 'failed' => $failed];
    }

    /**
     * Check if media is an image
     */
    public static function isImage(array $media): bool
    {
        if (empty($media['mime_type'])) {
            return false;
        }
        return strpos($media['mime_type'], 'image/') === 0;
    }

    /**
     * Generate unique filename
     */
    private static function generateFilename(string $originalName): string
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $basename = pathinfo($originalName, PATHINFO_FILENAME);
        $basename = sanitizeFilename($basename);
        
        return $basename . '-' . uniqid() . '.' . $extension;
    }

    /**
     * Get upload error message
     */
    private static function getUploadError(int $code): string
    {
        switch ($code) {
            case UPLOAD_ERR_INI_SIZE:
                return 'File exceeds upload_max_filesize directive';
            case UPLOAD_ERR_FORM_SIZE:
                return 'File exceeds MAX_FILE_SIZE directive';
            case UPLOAD_ERR_PARTIAL:
                return 'File was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'A PHP extension stopped the file upload';
            default:
                return 'Unknown upload error';
        }
    }
}
