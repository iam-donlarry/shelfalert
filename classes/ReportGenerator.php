<?php
/**
 * ReportGenerator Class
 * Handles report generation and data export
 */
class ReportGenerator {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Get full inventory report
     */
    public function getInventoryReport($filters = []) {
        try {
            $where = ["p.status != 'discontinued'"];
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
            
            $whereClause = implode(' AND ', $where);
            
            $query = "SELECT 
                        p.product_code,
                        p.product_name,
                        c.category_name,
                        s.supplier_name,
                        p.quantity,
                        p.unit_of_measure,
                        p.unit_price,
                        p.cost_price,
                        (p.quantity * p.unit_price) as total_value,
                        p.reorder_level,
                        p.manufacture_date,
                        p.expiry_date,
                        DATEDIFF(p.expiry_date, CURDATE()) as days_until_expiry,
                        p.batch_number,
                        p.storage_location,
                        p.status,
                        CASE 
                            WHEN DATEDIFF(p.expiry_date, CURDATE()) <= 0 THEN 'Expired'
                            WHEN DATEDIFF(p.expiry_date, CURDATE()) <= 7 THEN 'Critical'
                            WHEN DATEDIFF(p.expiry_date, CURDATE()) <= 30 THEN 'Warning'
                            ELSE 'Good'
                        END as expiry_status
                      FROM products p
                      LEFT JOIN categories c ON p.category_id = c.category_id
                      LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
                      WHERE {$whereClause}
                      ORDER BY c.category_name, p.product_name";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate summary
            $summary = [
                'total_products' => count($data),
                'total_quantity' => array_sum(array_column($data, 'quantity')),
                'total_value' => array_sum(array_column($data, 'total_value')),
                'generated_at' => date('Y-m-d H:i:s')
            ];
            
            return ['data' => $data, 'summary' => $summary];
        } catch (PDOException $e) {
            error_log("ReportGenerator::getInventoryReport error: " . $e->getMessage());
            return ['data' => [], 'summary' => []];
        }
    }
    
    /**
     * Get near-expiry products report
     */
    public function getNearExpiryReport($days = 30) {
        try {
            $query = "SELECT 
                        p.product_code,
                        p.product_name,
                        c.category_name,
                        s.supplier_name,
                        b.current_quantity as quantity,
                        p.unit_price,
                        (b.current_quantity * p.unit_price) as at_risk_value,
                        b.expiry_date,
                        DATEDIFF(b.expiry_date, CURDATE()) as days_until_expiry,
                        b.batch_number,
                        p.storage_location,
                        CASE 
                            WHEN DATEDIFF(b.expiry_date, CURDATE()) <= 7 THEN 'Critical'
                            ELSE 'Warning'
                        END as urgency
                      FROM product_batches b
                      JOIN products p ON b.product_id = p.product_id
                      LEFT JOIN categories c ON p.category_id = c.category_id
                      LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
                      WHERE b.status = 'active'
                        AND DATEDIFF(b.expiry_date, CURDATE()) BETWEEN 1 AND :days
                      ORDER BY b.expiry_date ASC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([':days' => $days]);
            
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate summary
            $critical = array_filter($data, fn($p) => $p['urgency'] === 'Critical');
            $summary = [
                'total_products' => count($data),
                'critical_count' => count($critical),
                'warning_count' => count($data) - count($critical),
                'total_at_risk_value' => array_sum(array_column($data, 'at_risk_value')),
                'generated_at' => date('Y-m-d H:i:s')
            ];
            
            return ['data' => $data, 'summary' => $summary];
        } catch (PDOException $e) {
            error_log("ReportGenerator::getNearExpiryReport error: " . $e->getMessage());
            return ['data' => [], 'summary' => []];
        }
    }
    
    /**
     * Get expired products report
     */
    public function getExpiredReport() {
        try {
            $query = "SELECT 
                        p.product_code,
                        p.product_name,
                        c.category_name,
                        s.supplier_name,
                        b.current_quantity as quantity,
                        p.unit_price,
                        p.cost_price,
                        (b.current_quantity * p.cost_price) as loss_value,
                        b.expiry_date,
                        DATEDIFF(CURDATE(), b.expiry_date) as days_expired,
                        b.batch_number,
                        p.storage_location
                      FROM product_batches b
                      JOIN products p ON b.product_id = p.product_id
                      LEFT JOIN categories c ON p.category_id = c.category_id
                      LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
                      WHERE b.status = 'active'
                        AND b.expiry_date < CURDATE()
                      ORDER BY b.expiry_date ASC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate summary
            $summary = [
                'total_expired' => count($data),
                'total_quantity' => array_sum(array_column($data, 'quantity')),
                'total_loss_value' => array_sum(array_column($data, 'loss_value')),
                'generated_at' => date('Y-m-d H:i:s')
            ];
            
            return ['data' => $data, 'summary' => $summary];
        } catch (PDOException $e) {
            error_log("ReportGenerator::getExpiredReport error: " . $e->getMessage());
            return ['data' => [], 'summary' => []];
        }
    }
    
    /**
     * Get category-wise summary report
     */
    public function getCategorySummary() {
        try {
            $query = "SELECT 
                        c.category_name,
                        COUNT(p.product_id) as product_count,
                        SUM(p.quantity) as total_quantity,
                        SUM(p.quantity * p.unit_price) as total_value,
                        SUM(CASE WHEN DATEDIFF(p.expiry_date, CURDATE()) <= 0 THEN 1 ELSE 0 END) as expired_count,
                        SUM(CASE WHEN DATEDIFF(p.expiry_date, CURDATE()) BETWEEN 1 AND 7 THEN 1 ELSE 0 END) as critical_count,
                        SUM(CASE WHEN DATEDIFF(p.expiry_date, CURDATE()) BETWEEN 8 AND 30 THEN 1 ELSE 0 END) as warning_count
                      FROM categories c
                      LEFT JOIN products p ON c.category_id = p.category_id AND p.status = 'active'
                      WHERE c.is_active = 1
                      GROUP BY c.category_id, c.category_name
                      ORDER BY c.category_name";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ReportGenerator::getCategorySummary error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get supplier-wise summary report
     */
    public function getSupplierSummary() {
        try {
            $query = "SELECT 
                        s.supplier_name,
                        s.contact_person,
                        s.phone,
                        COUNT(p.product_id) as product_count,
                        SUM(p.quantity) as total_quantity,
                        SUM(p.quantity * p.unit_price) as total_value,
                        SUM(CASE WHEN DATEDIFF(p.expiry_date, CURDATE()) <= 7 THEN 1 ELSE 0 END) as expiring_soon
                      FROM suppliers s
                      LEFT JOIN products p ON s.supplier_id = p.supplier_id AND p.status = 'active'
                      WHERE s.is_active = 1
                      GROUP BY s.supplier_id, s.supplier_name, s.contact_person, s.phone
                      ORDER BY s.supplier_name";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ReportGenerator::getSupplierSummary error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get stock movement history report
     */
    public function getStockMovementReport($filters = []) {
        try {
            $where = ["1=1"];
            $params = [];
            
            if (!empty($filters['product_id'])) {
                $where[] = "sm.product_id = :product_id";
                $params[':product_id'] = $filters['product_id'];
            }
            
            if (!empty($filters['movement_type'])) {
                $where[] = "sm.movement_type = :movement_type";
                $params[':movement_type'] = $filters['movement_type'];
            }
            
            if (!empty($filters['date_from'])) {
                $where[] = "DATE(sm.created_at) >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $where[] = "DATE(sm.created_at) <= :date_to";
                $params[':date_to'] = $filters['date_to'];
            }
            
            $whereClause = implode(' AND ', $where);
            
            $query = "SELECT 
                        sm.*,
                        p.product_code,
                        p.product_name,
                        u.full_name as performed_by_name
                      FROM stock_movements sm
                      JOIN products p ON sm.product_id = p.product_id
                      LEFT JOIN users u ON sm.performed_by = u.user_id
                      WHERE {$whereClause}
                      ORDER BY sm.created_at DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ReportGenerator::getStockMovementReport error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Export data to CSV
     */
    public function exportToCsv($data, $filename, $headers = []) {
        if (empty($data)) {
            return false;
        }
        
        // Set headers for download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        
        // Add BOM for Excel UTF-8 compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Write headers
        if (empty($headers)) {
            $headers = array_keys($data[0]);
        }
        fputcsv($output, $headers);
        
        // Write data rows
        foreach ($data as $row) {
            fputcsv($output, array_values($row));
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Get expiry trend data for charts
     */
    public function getExpiryTrend($days = 30) {
        try {
            $query = "SELECT 
                        DATE(expiry_date) as date,
                        COUNT(*) as count
                      FROM products
                      WHERE status = 'active'
                        AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)
                      GROUP BY DATE(expiry_date)
                      ORDER BY date";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([':days' => $days]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ReportGenerator::getExpiryTrend error: " . $e->getMessage());
            return [];
        }
    }
}
