<?php
/**
 * InventoryManager Class
 * Handles stock movements, adjustments, and audit trails
 */
class InventoryManager {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Adjust stock quantity and log the movement using FIFO logic
     * 
     * @param int $product_id
     * @param int $quantity Amount to add/remove
     * @param string $type 'in', 'out', 'adjustment', 'expired_removal', 'return'
     * @param int $user_id
     * @param string $notes
     * @param array $batch_data Optional data for NEW batch (expiry_date, batch_number)
     * @return array
     */
    public function adjustStock($product_id, $quantity, $type, $user_id, $notes = '', $batch_data = []) {
        try {
            $this->db->beginTransaction();
            
            // 1. Get product current total for summary update
            $stmt = $this->db->prepare("SELECT quantity FROM products WHERE product_id = :id FOR UPDATE");
            $stmt->execute([':id' => $product_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$product) throw new Exception("Product not found");
            
            $total_qty_before = (int)$product['quantity'];
            $qty_change = $quantity; // Magnitude for logging
            
            if ($type === 'in' || $type === 'return') {
                // RESTOCK: Create new batch or add to existing if same expiry? 
                // Best practice: Always create new batch for restock to keep track of lots.
                $expiry = $batch_data['expiry_date'] ?? date('Y-m-d', strtotime('+1 year'));
                $batch_no = $batch_data['batch_number'] ?? 'RESTOCK-' . date('Ymd');
                
                $ins = $this->db->prepare("INSERT INTO product_batches 
                    (product_id, batch_number, expiry_date, initial_quantity, current_quantity, status)
                    VALUES (:pid, :bno, :exp, :iqty, :cqty, 'active')");
                $ins->execute([
                    ':pid' => $product_id,
                    ':bno' => $batch_no,
                    ':exp' => $expiry,
                    ':iqty' => abs($quantity),
                    ':cqty' => abs($quantity)
                ]);
                $batch_id = $this->db->lastInsertId();
                $total_qty_after = $total_qty_before + abs($quantity);
                
            } else {
                // REMOVAL (Out, Expired Removal, Adjustment < 0): FIFO logic
                $abs_qty_to_remove = abs($quantity);
                if ($total_qty_before < $abs_qty_to_remove) {
                    throw new Exception("Insufficient total stock. Available: $total_qty_before");
                }
                
                // Get active batches sorted by expiry (FIFO)
                $batches = $this->db->prepare("SELECT batch_id, current_quantity FROM product_batches 
                    WHERE product_id = :pid AND status = 'active' ORDER BY expiry_date ASC, created_at ASC FOR UPDATE");
                $batches->execute([':pid' => $product_id]);
                $active_batches = $batches->fetchAll(PDO::FETCH_ASSOC);
                
                $remaining_to_remove = $abs_qty_to_remove;
                foreach ($active_batches as $batch) {
                    if ($remaining_to_remove <= 0) break;
                    
                    $take = min($batch['current_quantity'], $remaining_to_remove);
                    $new_batch_qty = $batch['current_quantity'] - $take;
                    $status = ($new_batch_qty <= 0) ? 'depleted' : 'active';
                    
                    $upd = $this->db->prepare("UPDATE product_batches SET current_quantity = :qty, status = :status WHERE batch_id = :bid");
                    $upd->execute([':qty' => $new_batch_qty, ':status' => $status, ':bid' => $batch['batch_id']]);
                    
                    if ($status === 'depleted') {
                        // Auto-resolve alerts for this specific batch
                        $resAlert = $this->db->prepare("UPDATE alerts SET status = 'resolved', resolved_at = NOW() 
                            WHERE batch_id = :bid AND status IN ('active', 'acknowledged')");
                        $resAlert->execute([':bid' => $batch['batch_id']]);
                    }
                    
                    $remaining_to_remove -= $take;
                }
                $total_qty_after = $total_qty_before - $abs_qty_to_remove;
                $batch_id = null; // Multiple batches might have been affected
            }
            
            // 3. Update Product Total and Expiry Cache (Soonest Batch)
            // We update the product's main expiry_date and batch_number so the main list/UI 
            // always shows the "Soonest" expiring items.
            $soonest = $this->db->prepare("SELECT batch_number, expiry_date FROM product_batches 
                WHERE product_id = :pid AND status = 'active' 
                ORDER BY expiry_date ASC, created_at ASC LIMIT 1");
            $soonest->execute([':pid' => $product_id]);
            $soonest_batch = $soonest->fetch(PDO::FETCH_ASSOC);

            if ($soonest_batch) {
                $updateProd = $this->db->prepare("UPDATE products SET 
                    quantity = :qty, 
                    expiry_date = :exp, 
                    batch_number = :bno 
                    WHERE product_id = :id");
                $updateProd->execute([
                    ':qty' => $total_qty_after, 
                    ':exp' => $soonest_batch['expiry_date'], 
                    ':bno' => $soonest_batch['batch_number'], 
                    ':id' => $product_id
                ]);
            } else {
                // No active batches left
                $updateProd = $this->db->prepare("UPDATE products SET quantity = :qty WHERE product_id = :id");
                $updateProd->execute([':qty' => $total_qty_after, ':id' => $product_id]);
            }
            
            // 4. Log Movement
            $log = $this->db->prepare("INSERT INTO stock_movements 
                (product_id, movement_type, quantity, quantity_before, quantity_after, notes, performed_by)
                VALUES (:pid, :type, :qty, :before, :after, :notes, :uid)");
            $log->execute([
                ':pid' => $product_id,
                ':type' => $type,
                ':qty' => abs($quantity),
                ':before' => $total_qty_before,
                ':after' => $total_qty_after,
                ':notes' => $notes,
                ':uid' => $user_id
            ]);
            
            $this->db->commit();
            return ['success' => true, 'message' => 'Inventory updated. New total: ' . $total_qty_after];
            
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Get movement history for a product
     */
    public function getMovementsByProduct($product_id, $limit = 50) {
        $stmt = $this->db->prepare("SELECT m.*, u.full_name as user_name 
            FROM stock_movements m 
            LEFT JOIN users u ON m.performed_by = u.user_id 
            WHERE m.product_id = :pid 
            ORDER BY m.created_at DESC LIMIT :limit");
        $stmt->bindValue(':pid', $product_id);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
