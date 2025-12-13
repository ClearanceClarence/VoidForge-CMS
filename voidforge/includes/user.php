<?php
/**
 * User Authentication and Management
 */

defined('CMS_ROOT') or die('Direct access not allowed');

class User
{
    // Role hierarchy (higher number = more permissions)
    public const ROLES = [
        'subscriber' => 1,
        'author' => 2,
        'editor' => 3,
        'admin' => 4,
    ];

    public const ROLE_LABELS = [
        'subscriber' => 'Subscriber',
        'author' => 'Author',
        'editor' => 'Editor',
        'admin' => 'Administrator',
    ];

    /** @var array|null */
    private static $currentUser = null;

    /**
     * Start session if not already started
     */
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_start();
        }
    }

    /**
     * Get the currently logged-in user
     */
    public static function current(): ?array
    {
        if (self::$currentUser !== null) {
            return self::$currentUser;
        }

        if (!isset($_SESSION['user_id'])) {
            return null;
        }

        self::$currentUser = self::find($_SESSION['user_id']);
        return self::$currentUser;
    }

    /**
     * Check if user is logged in
     */
    public static function isLoggedIn(): bool
    {
        return self::current() !== null;
    }

    /**
     * Require user to be logged in
     */
    public static function requireLogin(): void
    {
        if (!self::isLoggedIn()) {
            redirect(ADMIN_URL . '/login.php');
        }
    }

    /**
     * Require specific role or higher
     */
    public static function requireRole(string $role): void
    {
        self::requireLogin();
        
        if (!self::hasRole($role)) {
            http_response_code(403);
            die('Access denied: insufficient permissions');
        }
    }

    /**
     * Check if current user has role or higher
     */
    public static function hasRole(string $role): bool
    {
        $user = self::current();
        if (!$user) {
            return false;
        }

        $requiredLevel = self::ROLES[$role] ?? 0;
        $userLevel = self::ROLES[$user['role']] ?? 0;

        return $userLevel >= $requiredLevel;
    }

    /**
     * Check if current user is admin
     */
    public static function isAdmin(): bool
    {
        return self::hasRole('admin');
    }

    /**
     * Login user
     */
    public static function login(string $username, string $password): bool
    {
        // Fire pre-login action
        Plugin::doAction('pre_user_login', $username);
        
        $table = Database::table('users');
        $user = Database::queryOne(
            "SELECT * FROM {$table} WHERE username = ? OR email = ?",
            [$username, $username]
        );
        
        // Allow custom authentication via filter
        $authenticated = Plugin::applyFilters('authenticate', null, $username, $password, $user);
        
        // If filter returned a result, use it; otherwise do default check
        if ($authenticated === null) {
            $authenticated = $user && password_verify($password, $user['password']);
        }

        if (!$authenticated || !$user) {
            Plugin::doAction('user_login_failed', $username);
            return false;
        }

        $_SESSION['user_id'] = $user['id'];
        self::$currentUser = $user;

        // Update last login
        Database::update(Database::table('users'), ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);
        
        // Fire logged in action
        Plugin::doAction('user_logged_in', $user['id'], $user);

        return true;
    }

    /**
     * Logout user
     */
    public static function logout(): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        $user = self::$currentUser;
        
        $_SESSION = [];
        
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        
        session_destroy();
        self::$currentUser = null;
        
        // Fire logged out action
        if ($userId) {
            Plugin::doAction('user_logged_out', $userId, $user);
        }
    }

    /**
     * Find user by ID
     */
    public static function find(int $id)
    {
        $table = Database::table('users');
        return Database::queryOne("SELECT * FROM {$table} WHERE id = ?", [$id]);
    }

    /**
     * Find user by email
     */
    public static function findByEmail(string $email)
    {
        $table = Database::table('users');
        return Database::queryOne("SELECT * FROM {$table} WHERE email = ?", [$email]);
    }

    /**
     * Find user by username
     */
    public static function findByUsername(string $username)
    {
        $table = Database::table('users');
        return Database::queryOne("SELECT * FROM {$table} WHERE username = ?", [$username]);
    }

    /**
     * Get all users
     */
    public static function all(): array
    {
        $table = Database::table('users');
        return Database::query("SELECT * FROM {$table} ORDER BY created_at DESC");
    }

    /**
     * Create a new user
     */
    public static function create(array $data): int
    {
        // Allow filtering of user data before insertion
        $data = Plugin::applyFilters('pre_insert_user', $data);
        
        $insertData = [
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT, ['cost' => HASH_COST]),
            'display_name' => $data['display_name'] ?? $data['username'],
            'role' => $data['role'] ?? 'subscriber',
            'created_at' => date('Y-m-d H:i:s'),
        ];
        
        $id = Database::insert(Database::table('users'), $insertData);
        
        // Fire user created action
        Plugin::doAction('user_inserted', $id, $insertData);
        
        return $id;
    }

    /**
     * Update user
     */
    public static function update(int $id, array $data): bool
    {
        $user = self::find($id);
        if (!$user) {
            return false;
        }
        
        $oldRole = $user['role'];
        
        // Allow filtering of update data
        $data = Plugin::applyFilters('pre_update_user', $data, $id, $user);
        
        $updateData = [];

        if (isset($data['username'])) {
            $updateData['username'] = $data['username'];
        }
        if (isset($data['email'])) {
            $updateData['email'] = $data['email'];
        }
        if (isset($data['display_name'])) {
            $updateData['display_name'] = $data['display_name'];
        }
        if (isset($data['role'])) {
            $updateData['role'] = $data['role'];
        }
        if (!empty($data['password'])) {
            $updateData['password'] = password_hash($data['password'], PASSWORD_DEFAULT, ['cost' => HASH_COST]);
        }

        if (empty($updateData)) {
            return false;
        }

        $result = Database::update(Database::table('users'), $updateData, 'id = ?', [$id]) > 0;
        
        if ($result) {
            // Fire user updated action
            Plugin::doAction('user_updated', $id, $updateData, $user);
            
            // Fire role changed action if role changed
            if (isset($updateData['role']) && $oldRole !== $updateData['role']) {
                Plugin::doAction('user_role_changed', $id, $updateData['role'], $oldRole, $user);
            }
        }

        return $result;
    }

    /**
     * Delete user
     */
    public static function delete(int $id): bool
    {
        // Prevent deleting the last admin
        $user = self::find($id);
        if (!$user) {
            return false;
        }
        
        $table = Database::table('users');
        if ($user['role'] === 'admin') {
            $adminCount = Database::queryValue(
                "SELECT COUNT(*) FROM {$table} WHERE role = 'admin'"
            );
            if ($adminCount <= 1) {
                return false;
            }
        }
        
        // Fire pre-delete action
        Plugin::doAction('pre_delete_user', $id, $user);

        $result = Database::delete(Database::table('users'), 'id = ?', [$id]) > 0;
        
        if ($result) {
            Plugin::doAction('user_deleted', $id, $user);
        }
        
        return $result;
    }

    /**
     * Validate user data
     */
    public static function validate(array $data, ?int $excludeId = null): array
    {
        $errors = [];

        // Username
        if (empty($data['username'])) {
            $errors['username'] = 'Username is required';
        } elseif (strlen($data['username']) < 3) {
            $errors['username'] = 'Username must be at least 3 characters';
        } else {
            $existing = self::findByUsername($data['username']);
            if ($existing && $existing['id'] !== $excludeId) {
                $errors['username'] = 'Username already exists';
            }
        }

        // Email
        if (empty($data['email'])) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email address';
        } else {
            $existing = self::findByEmail($data['email']);
            if ($existing && $existing['id'] !== $excludeId) {
                $errors['email'] = 'Email already exists';
            }
        }

        // Password (only required for new users)
        if ($excludeId === null && empty($data['password'])) {
            $errors['password'] = 'Password is required';
        } elseif (!empty($data['password']) && strlen($data['password']) < 8) {
            $errors['password'] = 'Password must be at least 8 characters';
        }

        // Role
        if (!empty($data['role']) && !isset(self::ROLES[$data['role']])) {
            $errors['role'] = 'Invalid role';
        }

        return $errors;
    }

    /**
     * Get role label
     */
    public static function getRoleLabel(string $role): string
    {
        return self::ROLE_LABELS[$role] ?? ucfirst($role);
    }
}
