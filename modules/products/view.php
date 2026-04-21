<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../classes/ProductManager.php';
require_once '../../classes/InventoryManager.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$auth->requireAuth();

$productManager = new ProductManager($db);
$inventoryManager = new InventoryManager($db);

// Get product ID
$product_id = $_GET['id'] ?? 0;
if (!$product_id) {
    header('Location: ' . base_url('modules/products/index'));
    exit;
}

// Handle Stock Adjustment POST
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'adjust_stock') {
    $qty = (int)$_POST['quantity'];
    $type = $_POST['type'];
    $notes = trim($_POST['notes']);
    $user_id = $_SESSION['user_id'];
    
    // Batch Data for Restock
    $batch_data = [];
    if ($type === 'in' || $type === 'return') {
        $batch_data = [
            'expiry_date' => $_POST['expiry_date'] ?? '',
            'batch_number' => $_POST['batch_number'] ?? ''
        ];
    }
    
    // Basic validation
    if ($qty <= 0) {
        $error_msg = "Quantity must be greater than zero.";
    } else {
        $result = $inventoryManager->adjustStock($product_id, $qty, $type, $user_id, $notes, $batch_data);
        if ($result['success']) {
            $success_msg = $result['message'];
        } else {
            $error_msg = $result['message'];
        }
    }
}

$product = $productManager->getById($product_id);
if (!$product) {
    header('Location: ' . base_url('modules/products/index'));
    exit;
}

// NEW: Get Active Batches
$stmtB = $db->prepare("SELECT * FROM product_batches WHERE product_id = :pid AND status = 'active' ORDER BY expiry_date ASC");
$stmtB->execute([':pid' => $product_id]);
$batches_list = $stmtB->fetchAll(PDO::FETCH_ASSOC);

// Get Movement History
$movements = $inventoryManager->getMovementsByProduct($product_id);

// Get expiry status badge class
function getExpiryBadge($status) {
    switch ($status) {
        case 'expired': return 'badge-danger';
        case 'critical': return 'badge-warning';
        case 'warning': return 'badge-info';
        default: return 'badge-success';
    }
}

$page_title = "View Product";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['product_name']); ?> - ShelfAlert</title>
    <link href="<?php echo asset_url('css/bootstrap.min.css'); ?>" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo asset_url('css/style.css'); ?>">
    <link rel="icon" href="<?php echo base_url('images/logo.png'); ?>">
    <style>
        .product-header {
            background: linear-gradient(135deg, var(--primary-light) 0%, #fff 100%);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid var(--border-color);
        }
        .product-title { font-size: 1.75rem; font-weight: 700; margin-bottom: 0.5rem; }
        .product-code { color: var(--text-secondary); font-family: monospace; font-size: 0.9rem; }
        .info-label { color: var(--text-secondary); font-size: 0.875rem; font-weight: 600; margin-bottom: 0.25rem; }
        .info-value { font-size: 1rem; font-weight: 500; }
        .expiry-countdown {
            text-align: center;
            padding: 1.5rem;
            border-radius: 12px;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
        }
        .countdown-number { font-size: 2.5rem; font-weight: 700; }
        .countdown-label { color: var(--text-secondary); font-size: 0.875rem; }
        .status-expired { background: #fee2e2; border-color: #fecaca; }
        .status-expired .countdown-number { color: #ef4444; }
        .status-critical { background: #fff7ed; border-color: #fed7aa; }
        .status-critical .countdown-number { color: #f97316; }
        .status-warning { background: #f0fdfa; border-color: #99f6e4; }
        .status-warning .countdown-number { color: #0d9488; }
        .status-good { background: #ecfdf5; border-color: #bbf7d0; }
        .status-good .countdown-number { color: #059669; }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <?php include '../../includes/header.php'; ?>
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <?php include '../../includes/sidebar.php'; ?>

        <main class="main-content">
            <div class="page-header d-flex justify-content-between align-items-center">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="<?php echo base_url('modules/products/index'); ?>">Products</a></li>
                            <li class="breadcrumb-item active"><?php echo htmlspecialchars($product['product_code']); ?></li>
                        </ol>
                    </nav>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?php echo base_url('modules/products/edit?id=' . $product['product_id']); ?>" class="btn btn-primary">
                        <i data-lucide="pencil" style="width: 18px; height: 18px; margin-right: 6px;"></i>
                        Edit Product
                    </a>
                    <a href="<?php echo base_url('modules/products/index'); ?>" class="btn btn-outline-secondary">
                        <i data-lucide="arrow-left" style="width: 18px; height: 18px; margin-right: 6px;"></i>
                        Back
                    </a>
                </div>
            </div>

                <?php if ($success_msg): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i data-lucide="check-circle" style="width: 18px; height: 18px; margin-right: 8px;"></i>
                    <?php echo htmlspecialchars($success_msg); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <?php if ($error_msg): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i data-lucide="alert-circle" style="width: 18px; height: 18px; margin-right: 8px;"></i>
                    <?php echo htmlspecialchars($error_msg); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <div class="product-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="product-title"><?php echo htmlspecialchars($product['product_name']); ?></h1>
                        <p class="product-code mb-2">
                            <i data-lucide="tag" style="width: 14px; height: 14px;"></i>
                            <?php echo htmlspecialchars($product['product_code']); ?>
                            <?php if ($product['barcode']): ?>
                            &nbsp;&bull;&nbsp;
                            <i data-lucide="barcode" style="width: 14px; height: 14px;"></i>
                            <?php echo htmlspecialchars($product['barcode']); ?>
                            <?php endif; ?>
                        </p>
                        <div class="d-flex gap-2 flex-wrap mb-3">
                            <span class="badge <?php echo getExpiryBadge($product['expiry_status']); ?>">
                                <?php echo ucfirst($product['expiry_status']); ?>
                            </span>
                            <span class="badge badge-<?php echo $product['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                <?php echo ucfirst($product['status']); ?>
                            </span>
                            <?php if ($product['quantity'] <= $product['reorder_level']): ?>
                            <span class="badge badge-warning">Low Stock</span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Actions -->
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#adjustStockModal">
                                <i data-lucide="arrow-left-right" style="width: 18px; height: 18px; margin-right: 6px;"></i>
                                Adjust Stock
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="expiry-countdown status-<?php echo $product['expiry_status']; ?>">
                            <?php if ($product['days_until_expiry'] <= 0): ?>
                            <div class="countdown-number"><?php echo abs($product['days_until_expiry']); ?></div>
                            <div class="countdown-label">Days Expired</div>
                            <?php else: ?>
                            <div class="countdown-number"><?php echo $product['days_until_expiry']; ?></div>
                            <div class="countdown-label">Days Until Expiry</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <ul class="nav nav-tabs mb-4" id="productTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#details" type="button">Product Details</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#history" type="button">Stock Movement History</button>
                </li>
            </ul>

            <div class="tab-content">
                <!-- Details Tab -->
                <div class="tab-pane fade show active" id="details">
                    <div class="row">
                        <!-- Product Details -->
                        <div class="col-lg-8">
                            <div class="content-card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Product Details</h5>
                                </div>
                                <div class="p-4">
                                    <div class="row g-4">
                                        <div class="col-md-6">
                                            <div class="info-label">Category</div>
                                            <div class="info-value"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="info-label">Supplier</div>
                                            <div class="info-value"><?php echo htmlspecialchars($product['supplier_name'] ?? 'N/A'); ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="info-label">Unit of Measure</div>
                                            <div class="info-value"><?php echo htmlspecialchars($product['unit_of_measure']); ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="info-label">Batch Number</div>
                                            <div class="info-value"><?php echo htmlspecialchars($product['batch_number'] ?? 'N/A'); ?></div>
                                        </div>
                                        <div class="col-12">
                                            <div class="info-label">Description</div>
                                            <div class="info-value"><?php echo nl2br(htmlspecialchars($product['description'] ?? 'No description')); ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="info-label">Storage Location</div>
                                            <div class="info-value"><?php echo htmlspecialchars($product['storage_location'] ?? 'Not specified'); ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Batches Card (NEW) -->
                            <div class="content-card mb-4">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">Active Batches</h5>
                                    <span class="badge bg-primary"><?php echo count($batches_list); ?> Batches</span>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Batch #</th>
                                                <th>Expiry Date</th>
                                                <th>Quantity</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($batches_list)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center py-3 text-muted">No active batches for this product.</td>
                                            </tr>
                                            <?php else: ?>
                                            <?php foreach ($batches_list as $batch): ?>
                                            <?php 
                                            $days = floor((strtotime($batch['expiry_date']) - time()) / 86400);
                                            $bBadge = 'bg-success';
                                            if ($days <= 0) $bBadge = 'bg-danger';
                                            elseif ($days <= 7) $bBadge = 'bg-warning text-dark';
                                            ?>
                                            <tr>
                                                <td><code><?php echo htmlspecialchars($batch['batch_number']); ?></code></td>
                                                <td>
                                                    <?php echo formatDate($batch['expiry_date'], 'M d, Y'); ?>
                                                    <span class="badge <?php echo $bBadge; ?> ms-1" style="font-size: 0.7rem;">
                                                        <?php echo $days <= 0 ? 'Expired' : ($days . 'd left'); ?>
                                                    </span>
                                                </td>
                                                <td><strong><?php echo number_format($batch['current_quantity']); ?></strong></td>
                                                <td><span class="badge bg-light text-dark border"><?php echo ucfirst($batch['status']); ?></span></td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Dates Card -->
                            <div class="content-card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Important Dates</h5>
                                </div>
                                <div class="p-4">
                                    <div class="row g-4">
                                        <div class="col-md-4">
                                            <div class="info-label">Manufacture Date</div>
                                            <div class="info-value">
                                                <?php echo $product['manufacture_date'] ? formatDate($product['manufacture_date'], 'M d, Y') : 'N/A'; ?>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="info-label">Expiry Date</div>
                                            <div class="info-value text-<?php echo $product['expiry_status'] === 'expired' ? 'danger' : ($product['expiry_status'] === 'critical' ? 'warning' : 'body'); ?>">
                                                <strong><?php echo formatDate($product['expiry_date'], 'M d, Y'); ?></strong>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="info-label">Added On</div>
                                            <div class="info-value"><?php echo formatDate($product['created_at'], 'M d, Y'); ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pricing & Stock -->
                        <div class="col-lg-4">
                            <div class="content-card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Pricing</h5>
                                </div>
                                <div class="p-4">
                                    <div class="mb-3">
                                        <div class="info-label">Selling Price</div>
                                        <div class="info-value fs-4 fw-bold text-success">
                                            <?php echo formatCurrency($product['unit_price']); ?>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="info-label">Cost Price</div>
                                        <div class="info-value"><?php echo formatCurrency($product['cost_price']); ?></div>
                                    </div>
                                    <div>
                                        <div class="info-label">Profit Margin</div>
                                        <?php 
                                        $margin = $product['cost_price'] > 0 
                                            ? (($product['unit_price'] - $product['cost_price']) / $product['cost_price']) * 100 
                                            : 0;
                                        ?>
                                        <div class="info-value text-<?php echo $margin > 0 ? 'success' : 'danger'; ?>">
                                            <?php echo number_format($margin, 1); ?>%
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="content-card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Stock Information</h5>
                                </div>
                                <div class="p-4">
                                    <div class="mb-3">
                                        <div class="info-label">Current Quantity</div>
                                        <div class="info-value fs-4 fw-bold <?php echo $product['quantity'] <= $product['reorder_level'] ? 'text-danger' : ''; ?>">
                                            <?php echo number_format($product['quantity']); ?> <?php echo $product['unit_of_measure']; ?>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="info-label">Reorder Level</div>
                                        <div class="info-value"><?php echo number_format($product['reorder_level']); ?> <?php echo $product['unit_of_measure']; ?></div>
                                    </div>
                                    <div>
                                        <div class="info-label">Stock Value</div>
                                        <div class="info-value"><?php echo formatCurrency($product['quantity'] * $product['unit_price']); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- History Tab -->
                <div class="tab-pane fade" id="history">
                    <div class="content-card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Stock Movement History</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Change</th>
                                        <th>Before</th>
                                        <th>After</th>
                                        <th>User</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($movements)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">No stock movements recorded yet.</td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($movements as $mov): ?>
                                    <tr>
                                        <td><?php echo formatDate($mov['created_at'], 'M d, Y H:i'); ?></td>
                                        <td>
                                            <?php
                                            $badgeClass = 'bg-secondary';
                                            $label = ucfirst(str_replace('_', ' ', $mov['movement_type']));
                                            switch($mov['movement_type']) {
                                                case 'in': $badgeClass = 'bg-success'; break;
                                                case 'out': $badgeClass = 'bg-danger'; break;
                                                case 'expired_removal': $badgeClass = 'bg-danger'; break;
                                                case 'return': $badgeClass = 'bg-warning text-dark'; break;
                                                case 'adjustment': $badgeClass = 'bg-info text-dark'; break;
                                            }
                                            ?>
                                            <span class="badge <?php echo $badgeClass; ?>"><?php echo $label; ?></span>
                                        </td>
                                        <td class="<?php echo $mov['quantity'] > 0 ? 'text-success' : 'text-danger'; ?>">
                                            <?php echo $mov['quantity'] > 0 ? '+' . $mov['quantity'] : $mov['quantity']; ?>
                                        </td>
                                        <td><?php echo number_format($mov['quantity_before']); ?></td>
                                        <td><strong><?php echo number_format($mov['quantity_after']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($mov['user_name'] ?? 'System'); ?></td>
                                        <td><?php echo htmlspecialchars($mov['notes']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Adjust Stock Modal -->
        <div class="modal fade" id="adjustStockModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="" method="POST">
                        <input type="hidden" name="action" value="adjust_stock">
                        <div class="modal-header">
                            <h5 class="modal-title">Adjust Stock</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Movement Type</label>
                                <select name="type" class="form-select" required onchange="updateQtyLabel(this.value)">
                                    <option value="in">Restock (In)</option>
                                    <option value="out">Sale / Usage (Out)</option>
                                    <option value="expired_removal">Expired Removal (Out)</option>
                                    <option value="return">Customer Return (In)</option>
                                    <option value="adjustment">Correction (In/Out)</option>
                                </select>
                            </div>
                            
                            <!-- Batch Fields (For Restock) -->
                            <div id="batchFields">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">New Expiry Date</label>
                                        <input type="date" name="expiry_date" class="form-control" value="<?php echo date('Y-m-d', strtotime('+1 year')); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Batch Number</label>
                                        <input type="text" name="batch_number" class="form-control" placeholder="e.g. BN-<?php echo date('Ymd'); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" id="qtyLabel">Quantity to Add</label>
                                <input type="number" name="quantity" class="form-control" min="1" required placeholder="e.g. 10">
                                <div class="form-text">System uses FIFO for removals.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control" rows="3" placeholder="Reason for adjustment..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Adjustment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <script>
        function updateQtyLabel(val) {
            const label = document.getElementById('qtyLabel');
            const input = document.querySelector('input[name="quantity"]');
            const batchFields = document.getElementById('batchFields');
            
            if (val === 'in' || val === 'return') {
                label.innerText = 'Quantity to Add';
                input.min = "1";
                input.placeholder = "e.g. 10";
                batchFields.style.display = 'block';
            } else if (val === 'out' || val === 'expired_removal') {
                label.innerText = 'Quantity to Remove';
                input.min = "1";
                input.placeholder = "e.g. 5";
                batchFields.style.display = 'none';
            } else {
                label.innerText = 'Adjustment Amount (Positive adds, Negative removes)';
                input.removeAttribute('min');
                input.placeholder = "e.g. -2 or 5";
                batchFields.style.display = 'none';
            }
        }
        
        // Run on load to set initial state
        document.addEventListener('DOMContentLoaded', function() {
            updateQtyLabel(document.querySelector('select[name="type"]').value);

            // Auto-open modal if action=resolve_expiry
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('action') === 'resolve_expiry') {
                const modalEl = document.getElementById('adjustStockModal');
                const modal = new bootstrap.Modal(modalEl);
                modal.show();

                // Pre-select 'out' / 'expired_removal'
                const typeSelect = document.querySelector('select[name="type"]');
                typeSelect.value = 'expired_removal';
                updateQtyLabel('expired_removal');

                // Pre-fill notes if batch is present
                const batch = urlParams.get('batch');
                if (batch) {
                    const notesArea = document.querySelector('textarea[name="notes"]');
                    notesArea.value = "Resolving expiry alert for Batch: " + batch;
                }
            }
        });
        </script>

        <?php include '../../includes/footer.php'; ?>
    </div>

    <script src="<?php echo asset_url('js/jquery.min.js'); ?>"></script>
    <script src="<?php echo asset_url('js/bootstrap.bundle.min.js'); ?>"></script>
    <script src="<?php echo asset_url('js/lucide.min.js'); ?>"></script>
    <script src="<?php echo asset_url('js/sidebar.js'); ?>"></script>
    <script src="<?php echo asset_url('js/main.js'); ?>"></script>
    <script>lucide.createIcons();</script>
</body>
</html>
