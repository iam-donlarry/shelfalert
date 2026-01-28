<?php
/**
 * UserManager Class
 * Handles all user-related operations
 */
class UserManager {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Get all users
     */
    public function getAll($includeInactive = false) {
        try {
            $sql = "SELECT u.*, ur.role_name 
                    FROM users u 
                    LEFT JOIN user_role_assignments ura ON u.user_id = ura.user_id
                    LEFT JOIN user_roles ur ON ura.role_id = ur.role_id";
            
            if (!$includeInactive) {
                $sql .= " WHERE u.is_active = 1";
            }
            
            $sql .= " ORDER BY u.created_at DESC";
            
            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Get a single user by ID
     */
    public function getById($id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT u.*, ur.role_id, ur.role_name 
                FROM users u 
                LEFT JOIN user_role_assignments ura ON u.user_id = ura.user_id
                LEFT JOIN user_roles ur ON ura.role_id = ur.role_id
                WHERE u.user_id = :id
            ");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }
    
    /**
     * Create a new user
     */
    public function create($data) {
        try {
            // Check if username exists
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
            $stmt->execute([':username' => $data['username']]);
            if ($stmt->fetchColumn() > 0) {
                return ['success' => false, 'message' => 'Username already exists'];
            }
            
            // Check if email exists
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
            $stmt->execute([':email' => $data['email']]);
            if ($stmt->fetchColumn() > 0) {
                return ['success' => false, 'message' => 'Email already exists'];
            }
            
            $this->conn->beginTransaction();
            
            $stmt = $this->conn->prepare("
                INSERT INTO users (username, email, password_hash, full_name, is_active)
                VALUES (:username, :email, :password_hash, :full_name, :is_active)
            ");
            
            $stmt->execute([
                ':username' => $data['username'],
                ':email' => $data['email'],
                ':password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
                ':full_name' => $data['full_name'],
                ':is_active' => $data['is_active'] ?? 1
            ]);
            
            $userId = $this->conn->lastInsertId();
            
            // Assign role if provided
            if (!empty($data['role_id'])) {
                $stmt = $this->conn->prepare("
                    INSERT INTO user_role_assignments (user_id, role_id)
                    VALUES (:user_id, :role_id)
                ");
                $stmt->execute([':user_id' => $userId, ':role_id' => $data['role_id']]);
            }
            
            $this->conn->commit();
            
            return [
                'success' => true,
                'message' => 'User created successfully',
                'id' => $userId
            ];
        } catch (PDOException $e) {
            $this->conn->rollBack();
            return [
                'success' => false,
                'message' => 'Error creating user: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Update a user
     */
    public function update($id, $data) {
        try {
            $this->conn->beginTransaction();
            
            $stmt = $this->conn->prepare("
                UPDATE users 
                SET full_name = :full_name,
                    email = :email,
                    is_active = :is_active
                WHERE user_id = :id
            ");
            
            $stmt->execute([
                ':id' => $id,
                ':full_name' => $data['full_name'],
                ':email' => $data['email'],
                ':is_active' => $data['is_active'] ?? 1
            ]);
            
            // Update role if provided
            if (isset($data['role_id'])) {
                // Remove existing role
                $stmt = $this->conn->prepare("DELETE FROM user_role_assignments WHERE user_id = :user_id");
                $stmt->execute([':user_id' => $id]);
                
                // Add new role
                if (!empty($data['role_id'])) {
                    $stmt = $this->conn->prepare("
                        INSERT INTO user_role_assignments (user_id, role_id)
                        VALUES (:user_id, :role_id)
                    ");
                    $stmt->execute([':user_id' => $id, ':role_id' => $data['role_id']]);
                }
            }
            
            $this->conn->commit();
            
            return [
                'success' => true,
                'message' => 'User updated successfully'
            ];
        } catch (PDOException $e) {
            $this->conn->rollBack();
            return [
                'success' => false,
                'message' => 'Error updating user: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Reset user password
     */
    public function resetPassword($id, $newPassword) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE users 
                SET password_hash = :password_hash, must_change_password = 0
                WHERE user_id = :id
            ");
            
            $stmt->execute([
                ':id' => $id,
                ':password_hash' => password_hash($newPassword, PASSWORD_DEFAULT)
            ]);
            
            return [
                'success' => true,
                'message' => 'Password reset successfully'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error resetting password: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Deactivate a user
     */
    public function deactivate($id) {
        try {
            $stmt = $this->conn->prepare("UPDATE users SET is_active = 0 WHERE user_id = :id");
            $stmt->execute([':id' => $id]);
            
            return [
                'success' => true,
                'message' => 'User deactivated successfully'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error deactivating user: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get all roles for dropdown
     */
    public function getRoles() {
        try {
            $stmt = $this->conn->query("SELECT role_id, role_name FROM user_roles WHERE is_active = 1 ORDER BY role_name");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
}
