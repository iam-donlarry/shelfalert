<?php
/**
 * AlertManager Class
 * Handles expiry alert generation, management, and notifications
 */
class AlertManager {
    private $db;
    private $critical_days = 7;
    private $warning_days = 30;
    
    public function __construct($db) {
        $this->db = $db;
        $this->loadSettings();
    }
    
    /**
     * Load alert thresholds from system settings
     */
    private function loadSettings() {
        try {
            $stmt = $this->db->prepare("SELECT setting_key, setting_value FROM system_settings 
                WHERE setting_key IN ('alert_critical_days', 'alert_warning_days')");
            $stmt->execute();
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if ($row['setting_key'] === 'alert_critical_days') {
                    $this->critical_days = (int)$row['setting_value'];
                } elseif ($row['setting_key'] === 'alert_warning_days') {
                    $this->warning_days = (int)$row['setting_value'];
                }
            }
        } catch (PDOException $e) {
            error_log("AlertManager::loadSettings error: " . $e->getMessage());
        }
    }
    
    /**
     * Generate alerts for products based on expiry dates
     * Should be run periodically (e.g., via cron job)
     */
    public function generateExpiryAlerts() {
        try {
            $created = 0;
            
            // Get products that need alerts
            $query = "SELECT p.product_id, p.product_name, p.product_code, p.expiry_date,
                        DATEDIFF(p.expiry_date, CURDATE()) as days_until_expiry
                      FROM products p
                      WHERE p.status = 'active'
                        AND DATEDIFF(p.expiry_date, CURDATE()) <= :warning_days";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([':warning_days' => $this->warning_days]);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($products as $product) {
                $days = (int)$product['days_until_expiry'];
                
                // Determine alert type
                if ($days <= 0) {
                    $alert_type = 'expired';
                    $message = "Product '{$product['product_name']}' ({$product['product_code']}) has EXPIRED on " . date('M d, Y', strtotime($product['expiry_date']));
                } elseif ($days <= $this->critical_days) {
                    $alert_type = 'critical';
                    $message = "Product '{$product['product_name']}' ({$product['product_code']}) expires in {$days} day(s) on " . date('M d, Y', strtotime($product['expiry_date']));
                } else {
                    $alert_type = 'warning';
                    $message = "Product '{$product['product_name']}' ({$product['product_code']}) expires in {$days} days on " . date('M d, Y', strtotime($product['expiry_date']));
                }
                
                // Check if active alert already exists for this product and type
                $check = $this->db->prepare("SELECT alert_id FROM alerts 
                    WHERE product_id = :product_id AND alert_type = :alert_type AND status IN ('active', 'acknowledged')");
                $check->execute([':product_id' => $product['product_id'], ':alert_type' => $alert_type]);
                
                if (!$check->fetch()) {
                    // Create new alert
                    $insert = $this->db->prepare("INSERT INTO alerts 
                        (product_id, alert_type, alert_message, days_until_expiry, status)
                        VALUES (:product_id, :alert_type, :message, :days, 'active')");
                    $insert->execute([
                        ':product_id' => $product['product_id'],
                        ':alert_type' => $alert_type,
                        ':message' => $message,
                        ':days' => $days
                    ]);
                    $created++;
                }
            }
            
            return ['success' => true, 'alerts_created' => $created];
        } catch (PDOException $e) {
            error_log("AlertManager::generateExpiryAlerts error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Get all unacknowledged alerts
     */
    public function getUnacknowledged($limit = 50) {
        try {
            $query = "SELECT a.*, 
                        p.product_name, p.product_code, p.expiry_date, p.quantity,
                        c.category_name
                      FROM alerts a
                      JOIN products p ON a.product_id = p.product_id
                      LEFT JOIN categories c ON p.category_id = c.category_id
                      WHERE a.status = 'active'
                      ORDER BY 
                        CASE a.alert_type 
                            WHEN 'expired' THEN 1 
                            WHEN 'critical' THEN 2 
                            WHEN 'warning' THEN 3
                            ELSE 4 
                        END,
                        a.created_at DESC
                      LIMIT :limit";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("AlertManager::getUnacknowledged error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get all alerts with optional filters
     */
    public function getAll($filters = []) {
        try {
            $where = ["1=1"];
            $params = [];
            
            if (!empty($filters['status'])) {
                $where[] = "a.status = :status";
                $params[':status'] = $filters['status'];
            }
            
            if (!empty($filters['alert_type'])) {
                $where[] = "a.alert_type = :alert_type";
                $params[':alert_type'] = $filters['alert_type'];
            }
            
            if (!empty($filters['date_from'])) {
                $where[] = "DATE(a.created_at) >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $where[] = "DATE(a.created_at) <= :date_to";
                $params[':date_to'] = $filters['date_to'];
            }
            
            $whereClause = implode(' AND ', $where);
            
            $query = "SELECT a.*, 
                        p.product_name, p.product_code, p.expiry_date,
                        c.category_name,
                        u.full_name as acknowledged_by_name
                      FROM alerts a
                      JOIN products p ON a.product_id = p.product_id
                      LEFT JOIN categories c ON p.category_id = c.category_id
                      LEFT JOIN users u ON a.acknowledged_by = u.user_id
                      WHERE {$whereClause}
                      ORDER BY a.created_at DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("AlertManager::getAll error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Acknowledge an alert
     */
    public function acknowledge($alert_id, $user_id) {
        try {
            $query = "UPDATE alerts SET 
                        status = 'acknowledged',
                        acknowledged_by = :user_id,
                        acknowledged_at = NOW()
                      WHERE alert_id = :alert_id";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([':alert_id' => $alert_id, ':user_id' => $user_id]);
            
            return ['success' => true, 'message' => 'Alert acknowledged'];
        } catch (PDOException $e) {
            error_log("AlertManager::acknowledge error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to acknowledge alert'];
        }
    }
    
    /**
     * Resolve an alert
     */
    public function resolve($alert_id) {
        try {
            $query = "UPDATE alerts SET 
                        status = 'resolved',
                        resolved_at = NOW()
                      WHERE alert_id = :alert_id";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([':alert_id' => $alert_id]);
            
            return ['success' => true, 'message' => 'Alert resolved'];
        } catch (PDOException $e) {
            error_log("AlertManager::resolve error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to resolve alert'];
        }
    }
    
    /**
     * Get alert statistics for dashboard
     */
    public function getStats() {
        try {
            $stats = [];
            
            // Count by type (active only)
            $stmt = $this->db->query("SELECT alert_type, COUNT(*) as count 
                FROM alerts WHERE status = 'active' GROUP BY alert_type");
            $types = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            $stats['expired'] = $types['expired'] ?? 0;
            $stats['critical'] = $types['critical'] ?? 0;
            $stats['warning'] = $types['warning'] ?? 0;
            $stats['low_stock'] = $types['low_stock'] ?? 0;
            $stats['total_active'] = array_sum($stats);
            
            // Today's alerts
            $stmt = $this->db->query("SELECT COUNT(*) FROM alerts WHERE DATE(created_at) = CURDATE()");
            $stats['today'] = $stmt->fetchColumn();
            
            return $stats;
        } catch (PDOException $e) {
            error_log("AlertManager::getStats error: " . $e->getMessage());
            return [
                'expired' => 0,
                'critical' => 0,
                'warning' => 0,
                'low_stock' => 0,
                'total_active' => 0,
                'today' => 0
            ];
        }
    }
    
    /**
     * Get alerts for a specific product
     */
    public function getByProduct($product_id) {
        try {
            $query = "SELECT a.*, u.full_name as acknowledged_by_name
                      FROM alerts a
                      LEFT JOIN users u ON a.acknowledged_by = u.user_id
                      WHERE a.product_id = :product_id
                      ORDER BY a.created_at DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([':product_id' => $product_id]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("AlertManager::getByProduct error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Acknowledge multiple alerts at once
     */
    public function acknowledgeMultiple($alert_ids, $user_id) {
        try {
            $placeholders = implode(',', array_fill(0, count($alert_ids), '?'));
            $query = "UPDATE alerts SET 
                        status = 'acknowledged',
                        acknowledged_by = ?,
                        acknowledged_at = NOW()
                      WHERE alert_id IN ({$placeholders})";
            
            $stmt = $this->db->prepare($query);
            $params = array_merge([$user_id], $alert_ids);
            $stmt->execute($params);
            
            return ['success' => true, 'message' => count($alert_ids) . ' alerts acknowledged'];
        } catch (PDOException $e) {
            error_log("AlertManager::acknowledgeMultiple error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to acknowledge alerts'];
        }
    }
}
