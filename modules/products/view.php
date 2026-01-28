<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../classes/ProductManager.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$auth->requireAuth();

$productManager = new ProductManager($db);

// Get product ID
$product_id = $_GET['id'] ?? 0;
if (!$product_id) {
    header('Location: ' . base_url('modules/products/index'));
    exit;
}

$product = $productManager->getById($product_id);
if (!$product) {
    header('Location: ' . base_url('modules/products/index'));
    exit;
}

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
    <link href="../../css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="icon" href="../../images/logo.png">
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
        .status-expired { background: #fef2f2; border-color: #fecaca; }
        .status-expired .countdown-number { color: #dc2626; }
        .status-critical { background: #fffbeb; border-color: #fed7aa; }
        .status-critical .countdown-number { color: #ea580c; }
        .status-warning { background: #eff6ff; border-color: #bfdbfe; }
        .status-warning .countdown-number { color: #2563eb; }
        .status-good { background: #f0fdf4; border-color: #bbf7d0; }
        .status-good .countdown-number { color: #16a34a; }
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

            <!-- Product Header -->
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
                        <div class="d-flex gap-2 flex-wrap">
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
        </main>

        <?php include '../../includes/footer.php'; ?>
    </div>

    <script src="../../js/jquery.min.js"></script>
    <script src="../../js/bootstrap.bundle.min.js"></script>
    <script src="../../js/lucide.min.js"></script>
    <script src="../../js/sidebar.js"></script>
    <script src="../../js/main.js"></script>
    <script>lucide.createIcons();</script>
</body>
</html>
