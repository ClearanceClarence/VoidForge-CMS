<?php
/**
 * Database Connection Class
 */

defined('CMS_ROOT') or die('Direct access not allowed');

class Database
{
    /** @var PDO|null */
    private static $instance = null;

    /**
     * Check if database is configured
     */
    public static function isConfigured(): bool
    {
        return defined('DB_NAME') && !empty(DB_NAME) && defined('DB_HOST') && !empty(DB_HOST);
    }

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            // Check if database is configured
            if (!self::isConfigured()) {
                die('<h1>Database Not Configured</h1><p>Please run the <a href="install.php">installer</a> to set up Forge CMS.</p>');
            }
            
            try {
                $charset = defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4';
                $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . $charset;
                
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                die('<h1>Database Connection Failed</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>');
            }
        }
        
        return self::$instance;
    }

    /**
     * Get prefixed table name
     * For backward compatibility, defaults to empty prefix if DB_PREFIX not defined
     */
    public static function table(string $name): string
    {
        $prefix = defined('DB_PREFIX') ? DB_PREFIX : '';
        return $prefix . $name;
    }

    /**
     * Execute a query and return all results
     */
    public static function query(string $sql, array $params = []): array
    {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Execute a query and return single row
     * @return array|null
     */
    public static function queryOne(string $sql, array $params = [])
    {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Execute a query and return single value
     * @return mixed
     */
    public static function queryValue(string $sql, array $params = [])
    {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    /**
     * Execute an insert/update/delete query
     */
    public static function execute(string $sql, array $params = []): int
    {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Insert a row and return the last insert ID
     */
    public static function insert(string $table, array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute(array_values($data));
        
        return (int) self::getInstance()->lastInsertId();
    }

    /**
     * Update rows in a table
     */
    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $set = implode(' = ?, ', array_keys($data)) . ' = ?';
        $sql = "UPDATE {$table} SET {$set} WHERE {$where}";
        
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute(array_merge(array_values($data), $whereParams));
        
        return $stmt->rowCount();
    }

    /**
     * Delete rows from a table
     */
    public static function delete(string $table, string $where, array $params = []): int
    {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->rowCount();
    }
}
