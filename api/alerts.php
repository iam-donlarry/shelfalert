<?php
/**
 * Alerts API Endpoint
 * Handles AJAX requests for alert operations
 */
header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../classes/AlertManager.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

// Check authentication
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$alertManager = new AlertManager($db);
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'list':
        $filters = [
            'status' => $_GET['status'] ?? '',
            'alert_type' => $_GET['alert_type'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? ''
        ];
        $alerts = $alertManager->getAll($filters);
        echo json_encode(['success' => true, 'data' => $alerts]);
        break;

    case 'unacknowledged':
        $limit = $_GET['limit'] ?? 50;
        $alerts = $alertManager->getUnacknowledged($limit);
        echo json_encode(['success' => true, 'data' => $alerts]);
        break;

    case 'acknowledge':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }
        
        $alert_id = $_POST['alert_id'] ?? 0;
        $result = $alertManager->acknowledge($alert_id, $_SESSION['user_id']);
        echo json_encode($result);
        break;

    case 'acknowledge_multiple':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }
        
        $alert_ids = $_POST['alert_ids'] ?? [];
        if (is_string($alert_ids)) {
            $alert_ids = json_decode($alert_ids, true);
        }
        
        if (empty($alert_ids)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No alerts specified']);
            exit;
        }
        
        $result = $alertManager->acknowledgeMultiple($alert_ids, $_SESSION['user_id']);
        echo json_encode($result);
        break;

    case 'resolve':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }
        
        $alert_id = $_POST['alert_id'] ?? 0;
        $result = $alertManager->resolve($alert_id);
        echo json_encode($result);
        break;

    case 'stats':
        $stats = $alertManager->getStats();
        echo json_encode(['success' => true, 'data' => $stats]);
        break;

    case 'generate':
        // Manually trigger alert generation
        $result = $alertManager->generateExpiryAlerts();
        echo json_encode($result);
        break;

    case 'by_product':
        $product_id = $_GET['product_id'] ?? 0;
        $alerts = $alertManager->getByProduct($product_id);
        echo json_encode(['success' => true, 'data' => $alerts]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
