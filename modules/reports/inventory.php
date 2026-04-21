<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../classes/ReportGenerator.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$auth->requireAuth();

$reportGenerator = new ReportGenerator($db);

// Get filter parameters
$filters = [
    'category_id' => $_GET['category'] ?? '',
    'supplier_id' => $_GET['supplier'] ?? '',
    'status' => $_GET['status'] ?? ''
];

$report = $reportGenerator->getInventoryReport($filters);

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $reportGenerator->exportToCsv($report['data'], 'inventory_report_' . date('Y-m-d') . '.csv');
    exit;
}

// Get categories for filter
try {
    $stmt = $db->query("SELECT category_id, category_name FROM categories WHERE is_active = 1 ORDER BY category_name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categories = [];
}

// Get suppliers for filter
try {
    $stmt = $db->query("SELECT supplier_id, supplier_name FROM suppliers WHERE is_active = 1 ORDER BY supplier_name");
    $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $suppliers = [];
}

$page_title = "Inventory Report";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Report - ShelfAlert | Ace Supermarket</title>
    <link href="<?php echo asset_url('css/bootstrap.min.css'); ?>" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo asset_url('css/style.css'); ?>">
    <link rel="icon" href="<?php echo base_url('images/logo.png'); ?>">
    <style>
        .report-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            overflow: hidden;
        }
        
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            padding: 1.25rem;
            background: var(--hover-bg);
            border-bottom: 1px solid var(--border-color);
        }
        
        .summary-item {
            background: var(--card-bg);
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
            border: 1px solid var(--border-color);
        }
        
        .summary-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
        }
        
        .summary-label {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }
        
        .expiry-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        
        .expiry-badge.Expired { background: #fee2e2; color: #ef4444; }
        .expiry-badge.Critical { background: #fff7ed; color: #f97316; }
        .expiry-badge.Warning { background: #fefce8; color: #eab308; }
        .expiry-badge.Good { background: #ecfdf5; color: #059669; }
        
        @media print {
            .no-print { display: none !important; }
            .main-wrapper { padding: 0 !important; }
            .sidebar, .main-header { display: none !important; }
            .main-content { margin: 0 !important; padding: 1rem !important; }
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <?php include '../../includes/header.php'; ?>
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <?php include '../../includes/sidebar.php'; ?>

        <main class="main-content">
            <div class="page-header no-print">
                <div>
                    <h1 class="page-title">Inventory Report</h1>
                    <p class="page-subtitle">Complete product inventory overview</p>
                </div>
                <div class="d-flex gap-2">
                    <button onclick="window.print()" class="btn btn-outline-secondary">
                        <i data-lucide="printer" style="width: 18px; height: 18px; margin-right: 6px;"></i>
                        Print
                    </button>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>" class="btn btn-success">
                        <i data-lucide="download" style="width: 18px; height: 18px; margin-right: 6px;"></i>
                        Export CSV
                    </a>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-4 no-print">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
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
                        <div class="col-md-3">
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
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="active" <?php echo $filters['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="discontinued" <?php echo $filters['status'] === 'discontinued' ? 'selected' : ''; ?>>Discontinued</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Generate Report</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Report -->
            <div class="report-card">
                <div class="summary-cards">
                    <div class="summary-item">
                        <div class="summary-value"><?php echo number_format($report['summary']['total_products']); ?></div>
                        <div class="summary-label">Total Products</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-value"><?php echo number_format($report['summary']['total_quantity']); ?></div>
                        <div class="summary-label">Total Quantity</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-value"><?php echo formatCurrency($report['summary']['total_value']); ?></div>
                        <div class="summary-label">Inventory Value</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-value"><?php echo $report['summary']['generated_at']; ?></div>
                        <div class="summary-label">Generated At</div>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Product Code</th>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Total Value</th>
                                <th>Expiry Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($report['data'])): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">No products found</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($report['data'] as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['product_code']); ?></td>
                                <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></td>
                                <td><?php echo number_format($product['quantity']); ?> <?php echo $product['unit_of_measure']; ?></td>
                                <td><?php echo formatCurrency($product['unit_price']); ?></td>
                                <td><?php echo formatCurrency($product['total_value']); ?></td>
                                <td><?php echo formatDate($product['expiry_date'], 'M d, Y'); ?></td>
                                <td><span class="expiry-badge <?php echo $product['expiry_status']; ?>"><?php echo $product['expiry_status']; ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>

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
