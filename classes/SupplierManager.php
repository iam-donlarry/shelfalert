<?php
/**
 * SupplierManager Class
 * Handles all supplier-related operations
 */
class SupplierManager {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Get all suppliers
     */
    public function getAll($includeInactive = false) {
        try {
            $sql = "SELECT s.*, 
                    (SELECT COUNT(*) FROM products p WHERE p.supplier_id = s.supplier_id) as product_count
                    FROM suppliers s";
            
            if (!$includeInactive) {
                $sql .= " WHERE s.is_active = 1";
            }
            
            $sql .= " ORDER BY s.supplier_name";
            
            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Get a single supplier by ID
     */
    public function getById($id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT s.*, 
                (SELECT COUNT(*) FROM products p WHERE p.supplier_id = s.supplier_id) as product_count
                FROM suppliers s 
                WHERE s.supplier_id = :id
            ");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }
    
    /**
     * Create a new supplier
     */
    public function create($data) {
        try {
            // Generate supplier code if not provided
            if (empty($data['supplier_code'])) {
                $data['supplier_code'] = $this->generateSupplierCode($data['supplier_name']);
            }
            
            $stmt = $this->conn->prepare("
                INSERT INTO suppliers (supplier_code, supplier_name, contact_person, email, phone, address, is_active)
                VALUES (:code, :name, :contact_person, :email, :phone, :address, :is_active)
            ");
            
            $stmt->execute([
                ':code' => $data['supplier_code'],
                ':name' => $data['supplier_name'],
                ':contact_person' => $data['contact_person'] ?? '',
                ':email' => $data['email'] ?? '',
                ':phone' => $data['phone'] ?? '',
                ':address' => $data['address'] ?? '',
                ':is_active' => $data['is_active'] ?? 1
            ]);
            
            return [
                'success' => true,
                'message' => 'Supplier created successfully',
                'id' => $this->conn->lastInsertId()
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error creating supplier: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Update a supplier
     */
    public function update($id, $data) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE suppliers 
                SET supplier_name = :name,
                    contact_person = :contact_person,
                    email = :email,
                    phone = :phone,
                    address = :address,
                    is_active = :is_active
                WHERE supplier_id = :id
            ");
            
            $stmt->execute([
                ':id' => $id,
                ':name' => $data['supplier_name'],
                ':contact_person' => $data['contact_person'] ?? '',
                ':email' => $data['email'] ?? '',
                ':phone' => $data['phone'] ?? '',
                ':address' => $data['address'] ?? '',
                ':is_active' => $data['is_active'] ?? 1
            ]);
            
            return [
                'success' => true,
                'message' => 'Supplier updated successfully'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error updating supplier: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Delete a supplier (soft delete)
     */
    public function delete($id) {
        try {
            // Check if supplier has products
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM products WHERE supplier_id = :id");
            $stmt->execute([':id' => $id]);
            $productCount = $stmt->fetchColumn();
            
            if ($productCount > 0) {
                return [
                    'success' => false,
                    'message' => "Cannot delete supplier with {$productCount} product(s). Remove or reassign products first."
                ];
            }
            
            $stmt = $this->conn->prepare("UPDATE suppliers SET is_active = 0 WHERE supplier_id = :id");
            $stmt->execute([':id' => $id]);
            
            return [
                'success' => true,
                'message' => 'Supplier deleted successfully'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error deleting supplier: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate a unique supplier code
     */
    private function generateSupplierCode($name) {
        $prefix = 'SUP';
        $namePrefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $name), 0, 2));
        $code = $prefix . $namePrefix . sprintf('%03d', rand(1, 999));
        
        // Check if exists
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM suppliers WHERE supplier_code = :code");
        $stmt->execute([':code' => $code]);
        
        if ($stmt->fetchColumn() > 0) {
            return $this->generateSupplierCode($name);
        }
        
        return $code;
    }
    
    /**
     * Get suppliers for dropdown
     */
    public function getForDropdown() {
        try {
            $stmt = $this->conn->query("
                SELECT supplier_id, supplier_name 
                FROM suppliers 
                WHERE is_active = 1 
                ORDER BY supplier_name
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
}
