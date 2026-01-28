<?php

include_once 'functions.php';
class Auth {
    private $conn;
    
    public function __construct($db = null) {
        if ($db) {
            $this->conn = $db;
        }
        
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function login($email, $password) {
        if (!$this->conn) {
            throw new Exception("Database connection not available");
        }

        // ShelfAlert login query - uses users table only
        $query = "SELECT u.*, ur.role_name
                  FROM users u 
                  LEFT JOIN user_role_assignments ura ON u.user_id = ura.user_id
                  LEFT JOIN user_roles ur ON ura.role_id = ur.role_id
                  WHERE (u.email = :login_email OR u.username = :login_username) AND u.is_active = 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":login_email", $email);
        $stmt->bindValue(":login_username", $email);
        $stmt->execute();

        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role_name'] = $user['role_name'] ?? 'Staff';
                $_SESSION['full_name'] = $user['full_name'] ?? $user['username'];
                $_SESSION['must_change_password'] = (bool)($user['must_change_password'] ?? false);
                $_SESSION['login_time'] = time();
                
                // Update last login
                $this->updateLastLogin($user['user_id']);
                
                return true;
            }
        }
        return false;
    }

    private function updateLastLogin($user_id) {
        $query = "UPDATE users SET last_login = NOW() WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
    }

    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && !$this->isSessionExpired();
    }

    private function isSessionExpired() {
        $max_session_duration = 8 * 60 * 60; // 8 hours
        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > $max_session_duration)) {
            $this->logout();
            return true;
        }
        return false;
    }

    public function logout() {
        $_SESSION = array();
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
        header("Location: " . base_url('login.php'));
        exit;

    }

    public function hasPermission($required_permission) {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        // Optimization: Check session cache first
        if (isset($_SESSION['permissions']) && in_array($required_permission, $_SESSION['permissions'])) {
            return true;
        }
        
        // Fetch permissions from DB if not cached
        if (!isset($_SESSION['permissions'])) {
            $this->loadPermissions();
        }

        // Fetch permissions from DB if not cached
        if (!isset($_SESSION['permissions'])) {
            $this->loadPermissions();
        }

        return isset($_SESSION['permissions']) && in_array($required_permission, $_SESSION['permissions']);
    }

    private function loadPermissions() {
        if (!isset($_SESSION['user_id'])) return;

        $query = "
            SELECT DISTINCT p.permission_name 
            FROM user_role_assignments ura
            JOIN role_permissions rp ON ura.role_id = rp.role_id
            JOIN permissions p ON rp.permission_id = p.permission_id
            WHERE ura.user_id = :user_id
        ";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':user_id' => $_SESSION['user_id']]);
        $_SESSION['permissions'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function requireAuth() {
        if (!$this->isLoggedIn()) {
            header("Location: " . base_url('login.php'));
            exit;
        }
    }

    public function requirePermission($required_role) {
        $this->requireAuth();
        
        if (!$this->hasPermission($required_role)) {
            http_response_code(403);
            echo '<div style="text-align:center;padding:50px;font-family:sans-serif;">
                <h1>403 - Access Denied</h1>
                <p>You do not have permission to access this page.</p>
                <a href="' . base_url('dashboard.php') . '">Return to Dashboard</a>
            </div>';
            exit;
        }
    }

    public function getCurrentUser() {
        return [
            'user_id' => $_SESSION['user_id'] ?? null,
            'username' => $_SESSION['username'] ?? null,
            'role_name' => $_SESSION['role_name'] ?? null,
            'employee_id' => $_SESSION['employee_id'] ?? null,
            'full_name' => $_SESSION['full_name'] ?? null
        ];
    }

    // Add this method to check if current user is admin (no employee_id)
    public function isSystemAdmin() {
        return ($_SESSION['role_name'] ?? '') === 'Admin';
    }

    /**
     * Get full user information by ID
     */
    public function getUserInfo($user_id) {
        $query = "SELECT u.*, ur.role_name 
                  FROM users u 
                  LEFT JOIN user_role_assignments ura ON u.user_id = ura.user_id
                  LEFT JOIN user_roles ur ON ura.role_id = ur.role_id
                  WHERE u.user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':user_id' => $user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Update user profile information
     */
    public function updateProfile($user_id, $data) {
        $query = "UPDATE users SET 
                  full_name = :full_name, 
                  phone = :phone,
                  updated_at = NOW()
                  WHERE user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $result = $stmt->execute([
            ':full_name' => $data['full_name'],
            ':phone' => $data['phone'],
            ':user_id' => $user_id
        ]);

        if ($result) {
            // Update session data
            $_SESSION['full_name'] = $data['full_name'];
            return ['success' => true, 'message' => 'Profile updated successfully'];
        }
        return ['success' => false, 'message' => 'Failed to update profile'];
    }

    /**
     * Update user password
     */
    public function updatePassword($user_id, $current_password, $new_password) {
        // First verify current password
        $query = "SELECT password_hash FROM users WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':user_id' => $user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($current_password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Incorrect current password'];
        }

        // Hash new password
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);

        $query = "UPDATE users SET 
                  password_hash = :password_hash,
                  must_change_password = 0,
                  updated_at = NOW()
                  WHERE user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $result = $stmt->execute([
            ':password_hash' => $new_hash,
            ':user_id' => $user_id
        ]);

        if ($result) {
            $_SESSION['must_change_password'] = false;
            return ['success' => true, 'message' => 'Password updated successfully'];
        }
        return ['success' => false, 'message' => 'Failed to update password'];
    }

    /**
     * Get recent activity logs for a specific user
     */
    public function getRecentActivity($user_id, $limit = 5) {
        $query = "SELECT * FROM activity_logs 
                  WHERE user_id = :user_id 
                  ORDER BY created_at DESC 
                  LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get system-wide stats for admin profile overview
     */
    public function getSystemStats() {
        $stats = [];
        
        // Active Alerts
        $stmt = $this->conn->query("SELECT COUNT(*) FROM alerts WHERE status = 'active'");
        $stats['active_alerts'] = $stmt->fetchColumn();
        
        // Critical Expiry (within 7 days)
        $stmt = $this->conn->query("SELECT COUNT(*) FROM products WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND expiry_date >= CURDATE() AND status = 'active'");
        $stats['critical_expiry'] = $stmt->fetchColumn();
        
        // Total Users
        $stmt = $this->conn->query("SELECT COUNT(*) FROM users WHERE is_active = 1");
        $stats['total_users'] = $stmt->fetchColumn();

        return $stats;
    }
}
?>