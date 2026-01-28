<?php
/**
 * Products API Endpoint
 * Handles AJAX requests for product operations
 */
header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../classes/ProductManager.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

// Check if user is authenticated
if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$productManager = new ProductManager($db);
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'delete':
        $id = $_GET['id'] ?? $_POST['id'] ?? 0;
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Product ID is required']);
            exit;
        }
        
        $result = $productManager->delete($id);
        echo json_encode($result);
        break;
        
    case 'get':
        $id = $_GET['id'] ?? 0;
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Product ID is required']);
            exit;
        }
        
        $product = $productManager->getById($id);
        if ($product) {
            echo json_encode(['success' => true, 'data' => $product]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
        }
        break;
        
    case 'list':
        $filters = [
            'category_id' => $_GET['category'] ?? '',
            'supplier_id' => $_GET['supplier'] ?? '',
            'status' => $_GET['status'] ?? 'active',
            'expiry_status' => $_GET['expiry_status'] ?? '',
            'search' => $_GET['search'] ?? ''
        ];
        
        $products = $productManager->getAll($filters);
        echo json_encode(['success' => true, 'data' => $products]);
        break;
        
    case 'stats':
        $stats = $productManager->getStats();
        echo json_encode(['success' => true, 'data' => $stats]);
        break;
        
    case 'update_stock':
        $id = $_POST['product_id'] ?? 0;
        $quantity = $_POST['quantity'] ?? 0;
        $type = $_POST['type'] ?? 'adjustment';
        $notes = $_POST['notes'] ?? '';
        
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Product ID is required']);
            exit;
        }
        
        $result = $productManager->updateStock($id, $quantity, $type, $notes, $_SESSION['user_id'] ?? null);
        echo json_encode($result);
        break;
        
    case 'near_expiry':
        $days = $_GET['days'] ?? 30;
        $products = $productManager->getNearExpiry($days);
        echo json_encode(['success' => true, 'data' => $products]);
        break;
        
    case 'expired':
        $products = $productManager->getExpired();
        echo json_encode(['success' => true, 'data' => $products]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
