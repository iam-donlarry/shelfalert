<?php
/**
 * RoleManager Class
 * Handles roles and permissions management
 */
class RoleManager {
    private $db;
    
    // Available permissions in the system
    public static $availablePermissions = [
        'dashboard' => [
            'label' => 'Dashboard',
            'permissions' => [
                'dashboard.view' => 'View Dashboard'
            ]
        ],
        'products' => [
            'label' => 'Products',
            'permissions' => [
                'products.view' => 'View Products',
                'products.create' => 'Add Products',
                'products.edit' => 'Edit Products',
                'products.delete' => 'Delete Products'
            ]
        ],
        'categories' => [
            'label' => 'Categories',
            'permissions' => [
                'categories.view' => 'View Categories',
                'categories.manage' => 'Manage Categories'
            ]
        ],
        'suppliers' => [
            'label' => 'Suppliers',
            'permissions' => [
                'suppliers.view' => 'View Suppliers',
                'suppliers.manage' => 'Manage Suppliers'
            ]
        ],
        'alerts' => [
            'label' => 'Alerts',
            'permissions' => [
                'alerts.view' => 'View Alerts',
                'alerts.acknowledge' => 'Acknowledge Alerts'
            ]
        ],
        'reports' => [
            'label' => 'Reports',
            'permissions' => [
                'reports.view' => 'View Reports',
                'reports.export' => 'Export Reports'
            ]
        ],
        'users' => [
            'label' => 'User Management',
            'permissions' => [
                'users.view' => 'View Users',
                'users.manage' => 'Manage Users'
            ]
        ],
        'settings' => [
            'label' => 'Settings',
            'permissions' => [
                'settings.view' => 'View Settings',
                'settings.manage' => 'Manage Settings'
            ]
        ]
    ];
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Get all roles
     */
    public function getAll($includeInactive = false) {
        try {
            $sql = "SELECT r.*, 
                    (SELECT COUNT(*) FROM user_role_assignments ura WHERE ura.role_id = r.role_id) as user_count
                    FROM user_roles r";
            
            if (!$includeInactive) {
                $sql .= " WHERE r.is_active = 1";
            }
            
            $sql .= " ORDER BY r.role_name";
            
            $stmt = $this->db->query($sql);
            $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Parse permissions JSON for each role
            foreach ($roles as &$role) {
                $role['permissions'] = json_decode($role['permissions'] ?? '[]', true) ?: [];
            }
            
            return $roles;
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Get a single role by ID
     */
    public function getById($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT r.*, 
                (SELECT COUNT(*) FROM user_role_assignments ura WHERE ura.role_id = r.role_id) as user_count
                FROM user_roles r 
                WHERE r.role_id = :id
            ");
            $stmt->execute([':id' => $id]);
            $role = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($role) {
                $role['permissions'] = json_decode($role['permissions'] ?? '[]', true) ?: [];
            }
            
            return $role;
        } catch (PDOException $e) {
            return null;
        }
    }
    
    /**
     * Create a new role
     */
    public function create($data) {
        try {
            // Check if role name exists
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM user_roles WHERE role_name = :name");
            $stmt->execute([':name' => $data['role_name']]);
            if ($stmt->fetchColumn() > 0) {
                return ['success' => false, 'message' => 'Role name already exists'];
            }
            
            $permissions = isset($data['permissions']) ? json_encode($data['permissions']) : '[]';
            
            $stmt = $this->db->prepare("
                INSERT INTO user_roles (role_name, description, permissions, is_active)
                VALUES (:name, :description, :permissions, :is_active)
            ");
            
            $stmt->execute([
                ':name' => $data['role_name'],
                ':description' => $data['description'] ?? '',
                ':permissions' => $permissions,
                ':is_active' => $data['is_active'] ?? 1
            ]);
            
            return [
                'success' => true,
                'message' => 'Role created successfully',
                'id' => $this->db->lastInsertId()
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error creating role: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Update a role
     */
    public function update($id, $data) {
        try {
            $permissions = isset($data['permissions']) ? json_encode($data['permissions']) : '[]';
            
            $stmt = $this->db->prepare("
                UPDATE user_roles 
                SET role_name = :name,
                    description = :description,
                    permissions = :permissions,
                    is_active = :is_active
                WHERE role_id = :id
            ");
            
            $stmt->execute([
                ':id' => $id,
                ':name' => $data['role_name'],
                ':description' => $data['description'] ?? '',
                ':permissions' => $permissions,
                ':is_active' => $data['is_active'] ?? 1
            ]);
            
            return [
                'success' => true,
                'message' => 'Role updated successfully'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error updating role: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Delete a role
     */
    public function delete($id) {
        try {
            // Check if role has users
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM user_role_assignments WHERE role_id = :id");
            $stmt->execute([':id' => $id]);
            $userCount = $stmt->fetchColumn();
            
            if ($userCount > 0) {
                return [
                    'success' => false,
                    'message' => "Cannot delete role with {$userCount} assigned user(s). Remove users first."
                ];
            }
            
            $stmt = $this->db->prepare("DELETE FROM user_roles WHERE role_id = :id");
            $stmt->execute([':id' => $id]);
            
            return [
                'success' => true,
                'message' => 'Role deleted successfully'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error deleting role: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Check if user has permission
     */
    public function userHasPermission($userId, $permission) {
        try {
            $stmt = $this->db->prepare("
                SELECT r.permissions
                FROM user_role_assignments ura
                JOIN user_roles r ON ura.role_id = r.role_id
                WHERE ura.user_id = :user_id AND r.is_active = 1
            ");
            $stmt->execute([':user_id' => $userId]);
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $permissions = json_decode($row['permissions'], true) ?: [];
                if (in_array($permission, $permissions)) {
                    return true;
                }
            }
            
            return false;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Get all permissions for a user
     */
    public function getUserPermissions($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT r.permissions
                FROM user_role_assignments ura
                JOIN user_roles r ON ura.role_id = r.role_id
                WHERE ura.user_id = :user_id AND r.is_active = 1
            ");
            $stmt->execute([':user_id' => $userId]);
            
            $allPermissions = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $permissions = json_decode($row['permissions'], true) ?: [];
                $allPermissions = array_merge($allPermissions, $permissions);
            }
            
            return array_unique($allPermissions);
        } catch (PDOException $e) {
            return [];
        }
    }
}
