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

// Get days parameter
$days = $_GET['days'] ?? 30;
$report = $reportGenerator->getNearExpiryReport($days);

$page_title = "Near-Expiry Products Report";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Near-Expiry Report - ShelfAlert | Ace Supermarket</title>
    <link href="<?php echo asset_url('css/bootstrap.min.css'); ?>" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo asset_url('css/style.css'); ?>">
    <link rel="icon" href="<?php echo base_url('images/logo1.png'); ?>">
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
            background: linear-gradient(135deg, var(--primary-light) 0%, rgba(255,255,255,1) 100%);
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
        
        .summary-value.danger { color: #ef4444; }
        .summary-value.warning { color: #f97316; }
        
        .summary-label {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }
        
        .urgency-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        
        .urgency-badge.Critical { background: #fee2e2; color: #ef4444; }
        .urgency-badge.Warning { background: #fefce8; color: #eab308; }
        
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
                    <h1 class="page-title">Near-Expiry Products</h1>
                    <p class="page-subtitle">Products expiring within the next <?php echo $days; ?> days</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-4 no-print">
                <div class="card-body">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Expiry Window</label>
                            <select name="days" class="form-select">
                                <option value="7" <?php echo $days == 7 ? 'selected' : ''; ?>>Next 7 days (Critical)</option>
                                <option value="14" <?php echo $days == 14 ? 'selected' : ''; ?>>Next 14 days</option>
                                <option value="30" <?php echo $days == 30 ? 'selected' : ''; ?>>Next 30 days</option>
                                <option value="60" <?php echo $days == 60 ? 'selected' : ''; ?>>Next 60 days</option>
                                <option value="90" <?php echo $days == 90 ? 'selected' : ''; ?>>Next 90 days</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Update</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Report -->
            <div class="report-card">
                <div class="summary-cards">
                    <div class="summary-item">
                        <div class="summary-value"><?php echo number_format($report['summary']['total_products']); ?></div>
                        <div class="summary-label">Products at Risk</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-value danger"><?php echo number_format($report['summary']['critical_count']); ?></div>
                        <div class="summary-label">Critical (1-7 days)</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-value warning"><?php echo number_format($report['summary']['warning_count']); ?></div>
                        <div class="summary-label">Warning (8-30 days)</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-value"><?php echo formatCurrency($report['summary']['total_at_risk_value']); ?></div>
                        <div class="summary-label">Value at Risk</div>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Product Code</th>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Supplier</th>
                                <th>Quantity</th>
                                <th>At Risk Value</th>
                                <th>Expiry Date</th>
                                <th>Days Left</th>
                                <th>Urgency</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($report['data'])): ?>
                            <tr>
                                <td colspan="9" class="text-center py-5 text-success">
                                    <i data-lucide="check-circle" style="width: 32px; height: 32px; margin-bottom: 8px;"></i>
                                    <p class="mb-0">No products expiring within <?php echo $days; ?> days. Great job!</p>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($report['data'] as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['product_code']); ?></td>
                                <td><strong><?php echo htmlspecialchars($product['product_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($product['supplier_name'] ?? 'N/A'); ?></td>
                                <td><?php echo number_format($product['quantity']); ?></td>
                                <td><?php echo formatCurrency($product['at_risk_value']); ?></td>
                                <td><?php echo formatDate($product['expiry_date'], 'M d, Y'); ?></td>
                                <td class="<?php echo $product['days_until_expiry'] <= 7 ? 'text-danger fw-bold' : ''; ?>">
                                    <?php echo $product['days_until_expiry']; ?> day(s)
                                </td>
                                <td><span class="urgency-badge <?php echo $product['urgency']; ?>"><?php echo $product['urgency']; ?></span></td>
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
    <script src="<?php echo asset_url('js/main.js'); ?>"></script>
    <script>lucide.createIcons();</script>
</body>
</html>
