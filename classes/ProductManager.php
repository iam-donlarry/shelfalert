<?php
/**
 * ProductManager Class
 * Handles all product-related CRUD operations and queries
 */
class ProductManager {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Create a new product and its initial batch
     */
    public function create($data) {
        try {
            $this->db->beginTransaction();

            $query = "INSERT INTO products (
                product_code, barcode, product_name, category_id, supplier_id,
                description, unit_of_measure, unit_price, cost_price, quantity,
                reorder_level, storage_location, manufacture_date, expiry_date,
                batch_number, status, created_by
            ) VALUES (
                :product_code, :barcode, :product_name, :category_id, :supplier_id,
                :description, :unit_of_measure, :unit_price, :cost_price, :quantity,
                :reorder_level, :storage_location, :manufacture_date, :expiry_date,
                :batch_number, :status, :created_by
            )";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':product_code' => $data['product_code'],
                ':barcode' => $data['barcode'] ?? null,
                ':product_name' => $data['product_name'],
                ':category_id' => $data['category_id'] ?: null,
                ':supplier_id' => $data['supplier_id'] ?: null,
                ':description' => $data['description'] ?? null,
                ':unit_of_measure' => $data['unit_of_measure'] ?? 'piece',
                ':unit_price' => $data['unit_price'] ?? 0,
                ':cost_price' => $data['cost_price'] ?? 0,
                ':quantity' => $data['quantity'] ?? 0,
                ':reorder_level' => $data['reorder_level'] ?? 10,
                ':storage_location' => $data['storage_location'] ?? null,
                ':manufacture_date' => $data['manufacture_date'] ?: null,
                ':expiry_date' => $data['expiry_date'],
                ':batch_number' => $data['batch_number'] ?? 'Initial',
                ':status' => $data['status'] ?? 'active',
                ':created_by' => $data['created_by'] ?? null
            ]);
            
            $product_id = $this->db->lastInsertId();

            // Create Initial Batch Tracking Entry
            $batchQuery = "INSERT INTO product_batches 
                (product_id, batch_number, manufacture_date, expiry_date, initial_quantity, current_quantity, status)
                VALUES (:pid, :bno, :mfg, :exp, :iqty, :cqty, :status)";
            
            $batchStmt = $this->db->prepare($batchQuery);
            $batchStmt->execute([
                ':pid' => $product_id,
                ':bno' => $data['batch_number'] ?: 'Initial',
                ':mfg' => $data['manufacture_date'] ?: null,
                ':exp' => $data['expiry_date'],
                ':iqty' => $data['quantity'] ?? 0,
                ':cqty' => $data['quantity'] ?? 0,
                ':status' => ($data['quantity'] ?? 0) > 0 ? 'active' : 'depleted'
            ]);
            
            $this->db->commit();

            return [
                'success' => true,
                'product_id' => $product_id,
                'message' => 'Product and initial batch created successfully'
            ];
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log("ProductManager::create error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create product: ' . $e->getMessage()];
        }
    }
    
    /**
     * Update an existing product
     */
    public function update($product_id, $data) {
        try {
            $query = "UPDATE products SET 
                product_name = :product_name,
                barcode = :barcode,
                category_id = :category_id,
                supplier_id = :supplier_id,
                description = :description,
                unit_of_measure = :unit_of_measure,
                unit_price = :unit_price,
                cost_price = :cost_price,
                quantity = :quantity,
                reorder_level = :reorder_level,
                storage_location = :storage_location,
                manufacture_date = :manufacture_date,
                expiry_date = :expiry_date,
                batch_number = :batch_number,
                status = :status
                WHERE product_id = :product_id";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':product_id' => $product_id,
                ':product_name' => $data['product_name'],
                ':barcode' => $data['barcode'] ?? null,
                ':category_id' => $data['category_id'] ?: null,
                ':supplier_id' => $data['supplier_id'] ?: null,
                ':description' => $data['description'] ?? null,
                ':unit_of_measure' => $data['unit_of_measure'] ?? 'piece',
                ':unit_price' => $data['unit_price'] ?? 0,
                ':cost_price' => $data['cost_price'] ?? 0,
                ':quantity' => $data['quantity'] ?? 0,
                ':reorder_level' => $data['reorder_level'] ?? 10,
                ':storage_location' => $data['storage_location'] ?? null,
                ':manufacture_date' => $data['manufacture_date'] ?: null,
                ':expiry_date' => $data['expiry_date'],
                ':batch_number' => $data['batch_number'] ?? null,
                ':status' => $data['status'] ?? 'active'
            ]);
            
            return ['success' => true, 'message' => 'Product updated successfully'];
        } catch (PDOException $e) {
            error_log("ProductManager::update error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update product'];
        }
    }
    
    /**
     * Delete a product (soft delete by changing status)
     */
    public function delete($product_id) {
        try {
            $query = "UPDATE products SET status = 'discontinued' WHERE product_id = :product_id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':product_id' => $product_id]);
            
            return ['success' => true, 'message' => 'Product archived successfully'];
        } catch (PDOException $e) {
            error_log("ProductManager::delete error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to delete product'];
        }
    }
    
    /**
     * Get all products with filters
     */
    public function getAll($filters = []) {
        try {
            $where = ["1=1"];
            $params = [];
            
            if (!empty($filters['category_id'])) {
                $where[] = "p.category_id = :category_id";
                $params[':category_id'] = $filters['category_id'];
            }
            
            if (!empty($filters['supplier_id'])) {
                $where[] = "p.supplier_id = :supplier_id";
                $params[':supplier_id'] = $filters['supplier_id'];
            }
            
            if (!empty($filters['status'])) {
                $where[] = "p.status = :status";
                $params[':status'] = $filters['status'];
            }
            
            if (!empty($filters['expiry_status'])) {
                switch ($filters['expiry_status']) {
                    case 'expired':
                        $where[] = "DATEDIFF(p.expiry_date, CURDATE()) <= 0";
                        break;
                    case 'critical':
                        $where[] = "DATEDIFF(p.expiry_date, CURDATE()) BETWEEN 1 AND 7";
                        break;
                    case 'warning':
                        $where[] = "DATEDIFF(p.expiry_date, CURDATE()) BETWEEN 8 AND 30";
                        break;
                    case 'good':
                        $where[] = "DATEDIFF(p.expiry_date, CURDATE()) > 30";
                        break;
                }
            }
            
            if (!empty($filters['search'])) {
                $where[] = "(p.product_name LIKE :search OR p.product_code LIKE :search OR p.barcode LIKE :search)";
                $params[':search'] = '%' . $filters['search'] . '%';
            }
            
            $whereClause = implode(' AND ', $where);
            
            $query = "SELECT p.*, 
                        c.category_name, c.category_code,
                        s.supplier_name,
                        DATEDIFF(p.expiry_date, CURDATE()) as days_until_expiry,
                        CASE 
                            WHEN DATEDIFF(p.expiry_date, CURDATE()) <= 0 THEN 'expired'
                            WHEN DATEDIFF(p.expiry_date, CURDATE()) <= 7 THEN 'critical'
                            WHEN DATEDIFF(p.expiry_date, CURDATE()) <= 30 THEN 'warning'
                            ELSE 'good'
                        END as expiry_status
                      FROM products p
                      LEFT JOIN categories c ON p.category_id = c.category_id
                      LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
                      WHERE {$whereClause}
                      ORDER BY p.expiry_date ASC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ProductManager::getAll error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get a single product by ID
     */
    public function getById($product_id) {
        try {
            $query = "SELECT p.*, 
                        c.category_name, c.category_code,
                        s.supplier_name,
                        DATEDIFF(p.expiry_date, CURDATE()) as days_until_expiry,
                        CASE 
                            WHEN DATEDIFF(p.expiry_date, CURDATE()) <= 0 THEN 'expired'
                            WHEN DATEDIFF(p.expiry_date, CURDATE()) <= 7 THEN 'critical'
                            WHEN DATEDIFF(p.expiry_date, CURDATE()) <= 30 THEN 'warning'
                            ELSE 'good'
                        END as expiry_status
                      FROM products p
                      LEFT JOIN categories c ON p.category_id = c.category_id
                      LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
                      WHERE p.product_id = :product_id";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([':product_id' => $product_id]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ProductManager::getById error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get products expiring within specified days
     */
    public function getNearExpiry($days = 30) {
        try {
            $query = "SELECT p.*, 
                        c.category_name,
                        s.supplier_name,
                        DATEDIFF(p.expiry_date, CURDATE()) as days_until_expiry
                      FROM products p
                      LEFT JOIN categories c ON p.category_id = c.category_id
                      LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
                      WHERE p.status = 'active' 
                        AND DATEDIFF(p.expiry_date, CURDATE()) BETWEEN 1 AND :days
                      ORDER BY p.expiry_date ASC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([':days' => $days]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ProductManager::getNearExpiry error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get all expired products
     */
    public function getExpired() {
        try {
            $query = "SELECT p.*, 
                        c.category_name,
                        s.supplier_name,
                        DATEDIFF(CURDATE(), p.expiry_date) as days_expired
                      FROM products p
                      LEFT JOIN categories c ON p.category_id = c.category_id
                      LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
                      WHERE p.status = 'active' 
                        AND p.expiry_date < CURDATE()
                      ORDER BY p.expiry_date ASC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ProductManager::getExpired error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get low stock products
     */
    public function getLowStock() {
        try {
            $query = "SELECT p.*, 
                        c.category_name,
                        s.supplier_name
                      FROM products p
                      LEFT JOIN categories c ON p.category_id = c.category_id
                      LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
                      WHERE p.status = 'active' 
                        AND p.quantity <= p.reorder_level
                      ORDER BY p.quantity ASC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ProductManager::getLowStock error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update stock quantity with movement tracking
     */
    public function updateStock($product_id, $quantity, $type, $notes = '', $user_id = null) {
        try {
            $this->db->beginTransaction();
            
            // Get current quantity
            $stmt = $this->db->prepare("SELECT quantity FROM products WHERE product_id = :id");
            $stmt->execute([':id' => $product_id]);
            $current = $stmt->fetchColumn();
            
            // Calculate new quantity
            $new_quantity = match($type) {
                'in', 'return' => $current + $quantity,
                'out', 'expired_removal' => $current - $quantity,
                'adjustment' => $quantity,
                default => $current
            };
            
            // Update product quantity
            $stmt = $this->db->prepare("UPDATE products SET quantity = :qty WHERE product_id = :id");
            $stmt->execute([':qty' => max(0, $new_quantity), ':id' => $product_id]);
            
            // Log movement
            $stmt = $this->db->prepare("INSERT INTO stock_movements 
                (product_id, movement_type, quantity, quantity_before, quantity_after, notes, performed_by)
                VALUES (:product_id, :type, :qty, :before, :after, :notes, :user_id)");
            $stmt->execute([
                ':product_id' => $product_id,
                ':type' => $type,
                ':qty' => $quantity,
                ':before' => $current,
                ':after' => max(0, $new_quantity),
                ':notes' => $notes,
                ':user_id' => $user_id
            ]);
            
            $this->db->commit();
            return ['success' => true, 'message' => 'Stock updated successfully'];
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("ProductManager::updateStock error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update stock'];
        }
    }
    
    /**
     * Generate unique product code
     */
    public function generateProductCode($category_id) {
        try {
            $prefix = 'PRD';
            
            if ($category_id) {
                $stmt = $this->db->prepare("SELECT category_code FROM categories WHERE category_id = :id");
                $stmt->execute([':id' => $category_id]);
                $code = $stmt->fetchColumn();
                if ($code) $prefix = $code;
            }
            
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM products WHERE product_code LIKE :prefix");
            $stmt->execute([':prefix' => $prefix . '%']);
            $count = $stmt->fetchColumn();
            
            return $prefix . '-' . str_pad($count + 1, 5, '0', STR_PAD_LEFT);
        } catch (PDOException $e) {
            return 'PRD-' . time();
        }
    }
    
    /**
     * Get dashboard statistics
     */
    public function getStats() {
        try {
            $stats = [];
            
            // Total active products
            $stmt = $this->db->query("SELECT COUNT(*) FROM products WHERE status = 'active'");
            $stats['total_products'] = $stmt->fetchColumn();
            
            // Expiring soon (7 days)
            $stmt = $this->db->query("SELECT COUNT(*) FROM products 
                WHERE status = 'active' AND DATEDIFF(expiry_date, CURDATE()) BETWEEN 1 AND 7");
            $stats['expiring_soon'] = $stmt->fetchColumn();
            
            // Expired products
            $stmt = $this->db->query("SELECT COUNT(*) FROM products 
                WHERE status = 'active' AND expiry_date < CURDATE()");
            $stats['expired'] = $stmt->fetchColumn();
            
            // Low stock
            $stmt = $this->db->query("SELECT COUNT(*) FROM products 
                WHERE status = 'active' AND quantity <= reorder_level");
            $stats['low_stock'] = $stmt->fetchColumn();
            
            // Total inventory value
            $stmt = $this->db->query("SELECT SUM(quantity * unit_price) FROM products WHERE status = 'active'");
            $stats['inventory_value'] = $stmt->fetchColumn() ?: 0;
            
            return $stats;
        } catch (PDOException $e) {
            error_log("ProductManager::getStats error: " . $e->getMessage());
            return [
                'total_products' => 0,
                'expiring_soon' => 0,
                'expired' => 0,
                'low_stock' => 0,
                'inventory_value' => 0
            ];
        }
    }
}
