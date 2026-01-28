<?php
/**
 * CategoryManager Class
 * Handles all category-related operations
 */
class CategoryManager {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Get all categories
     */
    public function getAll($includeInactive = false) {
        try {
            $sql = "SELECT c.*, 
                    (SELECT COUNT(*) FROM products p WHERE p.category_id = c.category_id) as product_count
                    FROM categories c";
            
            if (!$includeInactive) {
                $sql .= " WHERE c.is_active = 1";
            }
            
            $sql .= " ORDER BY c.category_name";
            
            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Get a single category by ID
     */
    public function getById($id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT c.*, 
                (SELECT COUNT(*) FROM products p WHERE p.category_id = c.category_id) as product_count
                FROM categories c 
                WHERE c.category_id = :id
            ");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }
    
    /**
     * Create a new category
     */
    public function create($data) {
        try {
            // Generate category code if not provided
            if (empty($data['category_code'])) {
                $data['category_code'] = $this->generateCategoryCode($data['category_name']);
            }
            
            $stmt = $this->conn->prepare("
                INSERT INTO categories (category_code, category_name, description, is_active)
                VALUES (:code, :name, :description, :is_active)
            ");
            
            $stmt->execute([
                ':code' => $data['category_code'],
                ':name' => $data['category_name'],
                ':description' => $data['description'] ?? '',
                ':is_active' => $data['is_active'] ?? 1
            ]);
            
            return [
                'success' => true,
                'message' => 'Category created successfully',
                'id' => $this->conn->lastInsertId()
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error creating category: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Update a category
     */
    public function update($id, $data) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE categories 
                SET category_name = :name,
                    description = :description,
                    is_active = :is_active
                WHERE category_id = :id
            ");
            
            $stmt->execute([
                ':id' => $id,
                ':name' => $data['category_name'],
                ':description' => $data['description'] ?? '',
                ':is_active' => $data['is_active'] ?? 1
            ]);
            
            return [
                'success' => true,
                'message' => 'Category updated successfully'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error updating category: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Delete a category (soft delete by setting is_active = 0)
     */
    public function delete($id) {
        try {
            // Check if category has products
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM products WHERE category_id = :id");
            $stmt->execute([':id' => $id]);
            $productCount = $stmt->fetchColumn();
            
            if ($productCount > 0) {
                return [
                    'success' => false,
                    'message' => "Cannot delete category with {$productCount} product(s). Remove or reassign products first."
                ];
            }
            
            $stmt = $this->conn->prepare("UPDATE categories SET is_active = 0 WHERE category_id = :id");
            $stmt->execute([':id' => $id]);
            
            return [
                'success' => true,
                'message' => 'Category deleted successfully'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error deleting category: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate a unique category code
     */
    private function generateCategoryCode($name) {
        // Take first 3 letters of name, uppercase
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $name), 0, 3));
        if (strlen($prefix) < 3) {
            $prefix = str_pad($prefix, 3, 'X');
        }
        
        // Add random number
        $code = $prefix . sprintf('%03d', rand(1, 999));
        
        // Check if exists
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM categories WHERE category_code = :code");
        $stmt->execute([':code' => $code]);
        
        if ($stmt->fetchColumn() > 0) {
            return $this->generateCategoryCode($name); // Recursively generate new code
        }
        
        return $code;
    }
    
    /**
     * Get categories for dropdown (active only)
     */
    public function getForDropdown() {
        try {
            $stmt = $this->conn->query("
                SELECT category_id, category_name 
                FROM categories 
                WHERE is_active = 1 
                ORDER BY category_name
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
}
