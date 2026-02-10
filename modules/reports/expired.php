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
$report = $reportGenerator->getExpiredReport();

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $reportGenerator->exportToCsv($report['data'], 'expired_products_' . date('Y-m-d') . '.csv');
    exit;
}

$page_title = "Expired Products Report";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expired Products - ShelfAlert | Ace Supermarket</title>
    <link href="../../css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="icon" href="../../images/logo.png">
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
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border-bottom: 1px solid var(--border-color);
        }
        
        .summary-item {
            background: rgba(255,255,255,0.9);
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
        }
        
        .summary-value {
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .summary-value.danger { color: #dc2626; }
        
        .summary-label {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }
        
        @media print {
            .no-print { display: none !important; }
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
                    <h1 class="page-title">Expired Products</h1>
                    <p class="page-subtitle">Products that have passed their expiry date</p>
                </div>
                <div class="d-flex gap-2">
                    <button onclick="window.print()" class="btn btn-outline-secondary">
                        <i data-lucide="printer" style="width: 18px; height: 18px; margin-right: 6px;"></i>
                        Print
                    </button>
                    <a href="?export=csv" class="btn btn-success">
                        <i data-lucide="download" style="width: 18px; height: 18px; margin-right: 6px;"></i>
                        Export CSV
                    </a>
                </div>
            </div>

            <!-- Report -->
            <div class="report-card">
                <div class="summary-cards">
                    <div class="summary-item">
                        <div class="summary-value danger"><?php echo number_format($report['summary']['total_expired']); ?></div>
                        <div class="summary-label">Expired Products</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-value"><?php echo number_format($report['summary']['total_quantity']); ?></div>
                        <div class="summary-label">Total Quantity</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-value danger"><?php echo formatCurrency($report['summary']['total_loss_value']); ?></div>
                        <div class="summary-label">Estimated Loss</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-value"><?php echo $report['summary']['generated_at']; ?></div>
                        <div class="summary-label">Generated At</div>
                    </div>
                </div>
                
                <?php if (!empty($report['data'])): ?>
                <div class="alert alert-danger m-3 d-flex align-items-center gap-2">
                    <i data-lucide="alert-triangle" style="width: 20px; height: 20px;"></i>
                    <strong>Action Required:</strong> These products should be removed from shelves immediately.
                </div>
                <?php endif; ?>
                
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Product Code</th>
                                <th>Product Name</th>
                                <th>Batch No</th>
                                <th>Category</th>
                                <th>Supplier</th>
                                <th>Quantity</th>
                                <th>Loss Value</th>
                                <th>Expiry Date</th>
                                <th>Days Expired</th>
                                <th>Location</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($report['data'])): ?>
                            <tr>
                                <td colspan="10" class="text-center py-5 text-success">
                                    <i data-lucide="check-circle" style="width: 48px; height: 48px; margin-bottom: 12px;"></i>
                                    <h5>No Expired Products!</h5>
                                    <p class="mb-0 text-muted">All products are within their expiry dates</p>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($report['data'] as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['product_code']); ?></td>
                                <td><strong><?php echo htmlspecialchars($product['product_name']); ?></strong></td>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($product['batch_number']); ?></span></td>
                                <td><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($product['supplier_name'] ?? 'N/A'); ?></td>
                                <td><?php echo number_format($product['quantity']); ?></td>
                                <td class="text-danger fw-bold"><?php echo formatCurrency($product['loss_value']); ?></td>
                                <td><?php echo formatDate($product['expiry_date'], 'M d, Y'); ?></td>
                                <td class="text-danger fw-bold"><?php echo $product['days_expired']; ?> day(s)</td>
                                <td><?php echo htmlspecialchars($product['storage_location'] ?? 'N/A'); ?></td>
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

    <script src="../../js/jquery.min.js"></script>
    <script src="../../js/bootstrap.bundle.min.js"></script>
    <script src="../../js/lucide.min.js"></script>
    <script src="../../js/sidebar.js"></script>
    <script src="../../js/main.js"></script>
    <script>lucide.createIcons();</script>
</body>
</html>
