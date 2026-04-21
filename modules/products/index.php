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

// Get filter parameters
$filters = [
    'category_id' => $_GET['category'] ?? '',
    'supplier_id' => $_GET['supplier'] ?? '',
    'status' => $_GET['status'] ?? 'active',
    'expiry_status' => $_GET['expiry_status'] ?? '',
    'search' => $_GET['search'] ?? ''
];

$products = $productManager->getAll($filters);

// Get categories for filter dropdown
try {
    $stmt = $db->query("SELECT category_id, category_name FROM categories WHERE is_active = 1 ORDER BY category_name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categories = [];
}

// Get suppliers for filter dropdown
try {
    $stmt = $db->query("SELECT supplier_id, supplier_name FROM suppliers WHERE is_active = 1 ORDER BY supplier_name");
    $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $suppliers = [];
}

$page_title = "Products";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - ShelfAlert | Ace Supermarket</title>
    <link href="<?php echo asset_url('css/bootstrap.min.css'); ?>" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo asset_url('css/style.css'); ?>">
    <link rel="icon" href="<?php echo base_url('images/logo.png'); ?>">
    <style>
        .filter-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
        }
        
        .product-table {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            overflow: hidden;
        }
        
        .expiry-badge {
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .expiry-badge.expired { background: #fee2e2; color: #ef4444; }
        .expiry-badge.critical { background: #fff7ed; color: #f97316; }
        .expiry-badge.warning { background: #fefce8; color: #eab308; }
        .expiry-badge.good { background: #ecfdf5; color: #059669; }
        
        .status-badge {
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-badge.active { background: #ecfdf5; color: #059669; }
        .status-badge.expired { background: #fee2e2; color: #ef4444; }
        .status-badge.discontinued { background: #f8fafc; color: #64748b; }
        .status-badge.out_of_stock { background: #fefce8; color: #eab308; }
        
        .action-btn {
            padding: 0.35rem 0.5rem;
            border-radius: 6px;
            border: none;
            background: transparent;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .action-btn:hover {
            background: var(--hover-bg);
        }
        
        .action-btn.edit { color: var(--primary-color); }
        .action-btn.view { color: #059669; }
        .action-btn.delete { color: #ef4444; }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <?php include '../../includes/header.php'; ?>
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <?php include '../../includes/sidebar.php'; ?>

        <main class="main-content">
            <div class="page-header">
                <div>
                    <h1 class="page-title">Products</h1>
                    <p class="page-subtitle">Manage your product inventory</p>
                </div>
                <div>
                    <a href="add.php" class="btn btn-primary">
                        <i data-lucide="plus" style="width: 18px; height: 18px; margin-right: 6px;"></i>
                        Add Product
                    </a>
                </div>
            </div>

            <!-- Filters -->
            <div class="filter-card">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control" placeholder="Product name, code, barcode..." value="<?php echo htmlspecialchars($filters['search']); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['category_id']; ?>" <?php echo $filters['category_id'] == $cat['category_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['category_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Supplier</label>
                        <select name="supplier" class="form-select">
                            <option value="">All Suppliers</option>
                            <?php foreach ($suppliers as $sup): ?>
                            <option value="<?php echo $sup['supplier_id']; ?>" <?php echo $filters['supplier_id'] == $sup['supplier_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($sup['supplier_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Expiry Status</label>
                        <select name="expiry_status" class="form-select">
                            <option value="">All Status</option>
                            <option value="expired" <?php echo $filters['expiry_status'] === 'expired' ? 'selected' : ''; ?>>Expired</option>
                            <option value="critical" <?php echo $filters['expiry_status'] === 'critical' ? 'selected' : ''; ?>>Critical (1-7 days)</option>
                            <option value="warning" <?php echo $filters['expiry_status'] === 'warning' ? 'selected' : ''; ?>>Warning (8-30 days)</option>
                            <option value="good" <?php echo $filters['expiry_status'] === 'good' ? 'selected' : ''; ?>>Good (>30 days)</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All</option>
                            <option value="active" <?php echo $filters['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="discontinued" <?php echo $filters['status'] === 'discontinued' ? 'selected' : ''; ?>>Discontinued</option>
                        </select>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i data-lucide="search" style="width: 16px; height: 16px;"></i>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Products Table -->
            <div class="product-table">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Supplier</th>
                                <th>Qty</th>
                                <th>Expiry Date</th>
                                <th>Expiry Status</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i data-lucide="package-x" style="width: 48px; height: 48px; margin-bottom: 12px; opacity: 0.5;"></i>
                                    <p class="mb-0">No products found</p>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($product['product_name']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($product['product_code']); ?></small>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($product['supplier_name'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php 
                                    $qtyClass = $product['quantity'] <= $product['reorder_level'] ? 'text-danger fw-bold' : '';
                                    ?>
                                    <span class="<?php echo $qtyClass; ?>"><?php echo number_format($product['quantity']); ?></span>
                                </td>
                                <td><?php echo formatDate($product['expiry_date'], 'M d, Y'); ?></td>
                                <td>
                                    <?php
                                    $days = $product['days_until_expiry'];
                                    $expiryStatus = $product['expiry_status'];
                                    $expiryText = match($expiryStatus) {
                                        'expired' => 'Expired',
                                        'critical' => $days . ' day(s) left',
                                        'warning' => $days . ' days left',
                                        default => 'Good'
                                    };
                                    ?>
                                    <span class="expiry-badge <?php echo $expiryStatus; ?>"><?php echo $expiryText; ?></span>
                                    <?php if (!empty($product['batch_number'])): ?>
                                        <div class="mt-1 small text-muted">
                                            Batch: <strong><?php echo htmlspecialchars($product['batch_number']); ?></strong>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $product['status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $product['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="view.php?id=<?php echo $product['product_id']; ?>" class="action-btn view" title="View">
                                        <i data-lucide="eye" style="width: 16px; height: 16px;"></i>
                                    </a>
                                    <a href="edit.php?id=<?php echo $product['product_id']; ?>" class="action-btn edit" title="Edit">
                                        <i data-lucide="pencil" style="width: 16px; height: 16px;"></i>
                                    </a>
                                    <button class="action-btn delete" onclick="confirmDelete(<?php echo $product['product_id']; ?>)" title="Delete">
                                        <i data-lucide="trash-2" style="width: 16px; height: 16px;"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="mt-3 text-muted">
                <small>Showing <?php echo count($products); ?> product(s)</small>
            </div>
        </main>

        <?php include '../../includes/footer.php'; ?>
    </div>

    <script src="<?php echo asset_url('js/jquery.min.js'); ?>"></script>
    <script src="<?php echo asset_url('js/bootstrap.bundle.min.js'); ?>"></script>
    <script src="<?php echo asset_url('js/lucide.min.js'); ?>"></script>
    <script src="<?php echo asset_url('js/sidebar.js'); ?>"></script>
    <script src="<?php echo asset_url('js/main.js'); ?>"></script>
    <script>
        lucide.createIcons();
        
        function confirmDelete(productId) {
            if (confirm('Are you sure you want to archive this product? It will be marked as discontinued.')) {
                fetch('../../api/products.php?action=delete&id=' + productId, {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Error deleting product');
                });
            }
        }
    </script>
</body>
</html>
