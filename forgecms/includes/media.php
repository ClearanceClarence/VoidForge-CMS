<?php
/**
 * Media Library Management with Folder Support
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
     * Get single media item
     */
    public static function get(int $id): ?array
    {
        $media = Database::queryOne("SELECT * FROM media WHERE id = ?", [$id]);
        
        if ($media) {
            $media['url'] = self::getUrl($media);
            $media['path'] = self::getPath($media);
        }
        
        return $media;
    }

    /**
     * Alias for get()
     */
    public static function find(int $id): ?array
    {
        return self::get($id);
    }

    /**
     * Get all media, optionally filtered by folder
     */
    public static function getAll(?int $folderId = null): array
    {
        $where = '1=1';
        $params = [];
        
        if ($folderId !== null) {
            $where .= ' AND folder_id = ?';
            $params[] = $folderId;
        }
        
        $items = Database::query(
            "SELECT * FROM media WHERE {$where} ORDER BY created_at DESC",
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

        $sql = "SELECT * FROM media WHERE {$whereClause} ORDER BY {$orderClause}";

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
        return (int) Database::queryValue("SELECT COUNT(*) FROM media WHERE {$whereClause}", $params);
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

        $id = Database::insert('media', [
            'filename' => $filename,
            'filepath' => $filepath,
            'mime_type' => $mimeType,
            'file_size' => $file['size'],
            'width' => $width,
            'height' => $height,
            'title' => pathinfo($file['name'], PATHINFO_FILENAME),
            'alt_text' => '',
            'folder_id' => $folderId > 0 ? $folderId : null,
            'uploaded_by' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

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

        Database::update('media', $updateData, 'id = ?', [$id]);
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

        $path = self::getPath($media);
        if (file_exists($path)) {
            @unlink($path);
        }

        Database::execute(
            "UPDATE posts SET featured_image_id = NULL WHERE featured_image_id = ?",
            [$id]
        );

        Database::delete('media', 'id = ?', [$id]);
        return ['success' => true];
    }

    /**
     * Get all folders
     */
    public static function getFolders(): array
    {
        $folders = Database::query("SELECT * FROM media_folders ORDER BY name ASC");
        
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

        $existing = Database::queryOne(
            "SELECT id FROM media_folders WHERE name = ?",
            [$name]
        );

        if ($existing) {
            return ['success' => false, 'error' => 'A folder with this name already exists'];
        }

        $id = Database::insert('media_folders', [
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
        Database::execute(
            "UPDATE media SET folder_id = NULL WHERE folder_id = ?",
            [$id]
        );

        Database::delete('media_folders', 'id = ?', [$id]);
        return ['success' => true];
    }

    /**
     * Get full URL for media
     */
    public static function getUrl(array $media): string
    {
        return UPLOADS_URL . '/' . $media['filepath'];
    }

    /**
     * Get full path for media
     */
    public static function getPath(array $media): string
    {
        return UPLOADS_PATH . '/' . $media['filepath'];
    }

    /**
     * Check if media is an image
     */
    public static function isImage(array $media): bool
    {
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
        return match ($code) {
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload',
            default => 'Unknown upload error',
        };
    }
}
