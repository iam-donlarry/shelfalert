<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'classes/ProductManager.php';
require_once 'classes/AlertManager.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$auth->requireAuth();

// Redirect if force password change is active
if (!empty($_SESSION['must_change_password'])) {
    header("Location: change_password_required.php");
    exit;
}

$productManager = new ProductManager($db);
$alertManager = new AlertManager($db);

// Get dashboard statistics
$stats = $productManager->getStats();
$alertStats = $alertManager->getStats();
$recentAlerts = $alertManager->getUnacknowledged(5);
$nearExpiryProducts = $productManager->getNearExpiry(7);
$expiredProducts = $productManager->getExpired();

// Generate alerts for any new near-expiry products
$alertManager->generateExpiryAlerts();

// Get category distribution for chart
try {
    $stmt = $db->query("SELECT c.category_name, COUNT(p.product_id) as count 
                        FROM categories c 
                        LEFT JOIN products p ON c.category_id = p.category_id AND p.status = 'active'
                        WHERE c.is_active = 1
                        GROUP BY c.category_id, c.category_name
                        ORDER BY count DESC
                        LIMIT 8");
    $categoryData = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categoryData = [];
}

// Get expiry trend data for chart
try {
    $stmt = $db->query("SELECT 
        CASE 
            WHEN DATEDIFF(expiry_date, CURDATE()) <= 0 THEN 'Expired'
            WHEN DATEDIFF(expiry_date, CURDATE()) <= 7 THEN 'Critical (1-7 days)'
            WHEN DATEDIFF(expiry_date, CURDATE()) <= 30 THEN 'Warning (8-30 days)'
            ELSE 'Good (>30 days)'
        END as status,
        COUNT(*) as count
        FROM products
        WHERE status = 'active'
        GROUP BY status
        ORDER BY FIELD(status, 'Expired', 'Critical (1-7 days)', 'Warning (8-30 days)', 'Good (>30 days)')");
    $expiryStatusData = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $expiryStatusData = [];
}

$page_title = "Dashboard";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ShelfAlert | Ace Supermarket</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" href="images/logo.png">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            height: 100%;
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .stat-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.875rem;
            font-weight: 500;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .stat-icon.blue { background: #3b82f6; }
        .stat-icon.green { background: #10b981; }
        .stat-icon.warning { background: #f59e0b; }
        .stat-icon.danger { background: #dc2626; }
        .stat-icon.info { background: #0ea5e9; }
        .stat-icon.orange { background: #ea580c; }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }
        
        .stat-value.danger { color: #dc2626; }
        .stat-value.warning { color: #f59e0b; }

        @media (max-width: 768px) {
            .stat-card {
                padding: 1rem;
            }
            .stat-value {
                font-size: 1.25rem;
            }
            .stat-label {
                font-size: 0.75rem;
            }
            .stat-icon {
                width: 32px;
                height: 32px;
            }
            .stat-icon i {
                width: 16px !important;
                height: 16px !important;
            }
        }

        .content-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            margin-bottom: 1.5rem;
            overflow: hidden;
        }

        .card-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            background: transparent;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
        }

        .card-body {
            padding: 1.5rem;
        }

        .custom-table {
            width: 100%;
            border-collapse: collapse;
        }

        .custom-table th {
            text-align: left;
            padding: 1rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-secondary);
            border-bottom: 1px solid var(--border-color);
            background: var(--hover-bg);
        }

        .custom-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-primary);
            font-size: 0.9375rem;
        }

        .custom-table tr:last-child td {
            border-bottom: none;
        }

        .alert-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .alert-item:last-child {
            border-bottom: none;
        }

        .alert-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
        }

        .alert-icon.expired { background: #fee2e2; color: #dc2626; }
        .alert-icon.critical { background: #ffedd5; color: #ea580c; }
        .alert-icon.warning { background: #fef3c7; color: #ca8a04; }

        .quick-action-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem;
            border-radius: 8px;
            font-weight: 500;
            transition: var(--transition);
            margin-bottom: 0.5rem;
            text-decoration: none;
        }

        .quick-action-btn:hover {
            transform: translateY(-1px);
            text-decoration: none;
        }
        
        .expiry-badge {
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .expiry-badge.expired { background: #fee2e2; color: #dc2626; }
        .expiry-badge.critical { background: #ffedd5; color: #ea580c; }
        .expiry-badge.warning { background: #fef3c7; color: #ca8a04; }
        .expiry-badge.good { background: #dcfce7; color: #16a34a; }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <!-- Header -->
        <?php include 'includes/header.php'; ?>

        <!-- Sidebar Overlay -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <div>
                    <h1 class="page-title">Dashboard Overview</h1>
                    <p class="page-subtitle">Welcome, <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?></p>
                </div>
                <div>
                    <span class="text-muted"><?php echo date('l, F j, Y'); ?></span>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="row g-4 mb-4">
                <div class="col-6 col-md-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-header">
                            <span class="stat-label">Total Products</span>
                            <div class="stat-icon blue">
                                <i data-lucide="package" style="width: 20px; height: 20px;"></i>
                            </div>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['total_products']); ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-header">
                            <span class="stat-label">Expiring Soon (7 days)</span>
                            <div class="stat-icon orange">
                                <i data-lucide="clock" style="width: 20px; height: 20px;"></i>
                            </div>
                        </div>
                        <div class="stat-value warning"><?php echo number_format($stats['expiring_soon']); ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-header">
                            <span class="stat-label">Expired Products</span>
                            <div class="stat-icon danger">
                                <i data-lucide="alert-triangle" style="width: 20px; height: 20px;"></i>
                            </div>
                        </div>
                        <div class="stat-value danger"><?php echo number_format($stats['expired']); ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-header">
                            <span class="stat-label">Low Stock Items</span>
                            <div class="stat-icon warning">
                                <i data-lucide="package-x" style="width: 20px; height: 20px;"></i>
                            </div>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['low_stock']); ?></div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Main Column -->
                <div class="col-lg-8">
                    <!-- Critical Products Table -->
                    <div class="content-card">
                        <div class="card-header">
                            <h2 class="card-title">
                                <i data-lucide="alert-circle" style="width: 20px; height: 20px; margin-right: 8px; color: #dc2626;"></i>
                                Products Requiring Attention
                            </h2>
                            <a href="<?php echo base_url('modules/reports/near_expiry.php'); ?>" class="btn btn-sm btn-outline-primary">
                                View All
                            </a>
                        </div>
                        <div class="table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Category</th>
                                        <th>Expiry Date</th>
                                        <th>Status</th>
                                        <th>Qty</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    // Combine expired and near expiry, sort by urgency
                                    $urgentProducts = array_merge($expiredProducts, $nearExpiryProducts);
                                    usort($urgentProducts, function($a, $b) {
                                        $daysA = $a['days_until_expiry'] ?? $a['days_expired'] ?? 0;
                                        $daysB = $b['days_until_expiry'] ?? $b['days_expired'] ?? 0;
                                        return $daysA - $daysB;
                                    });
                                    $urgentProducts = array_slice($urgentProducts, 0, 5);
                                    
                                    if (empty($urgentProducts)): 
                                    ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            <i data-lucide="check-circle" style="width: 32px; height: 32px; color: #10b981; margin-bottom: 8px;"></i>
                                            <p class="mb-0">No products requiring immediate attention</p>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($urgentProducts as $product): 
                                        $days = $product['days_until_expiry'] ?? (-1 * ($product['days_expired'] ?? 0));
                                        if ($days <= 0) {
                                            $statusClass = 'expired';
                                            $statusText = 'Expired';
                                        } elseif ($days <= 7) {
                                            $statusClass = 'critical';
                                            $statusText = $days . ' day(s) left';
                                        } else {
                                            $statusClass = 'warning';
                                            $statusText = $days . ' days left';
                                        }
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <strong><?php echo htmlspecialchars($product['product_name']); ?></strong><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($product['product_code']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo formatDate($product['expiry_date'], 'M d, Y'); ?></td>
                                        <td><span class="expiry-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                                        <td><?php echo number_format($product['quantity']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Charts Row -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="content-card">
                                <div class="card-header">
                                    <h2 class="card-title">Expiry Status Distribution</h2>
                                </div>
                                <div class="card-body">
                                    <div style="height: 250px;">
                                        <canvas id="expiryStatusChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="content-card">
                                <div class="card-header">
                                    <h2 class="card-title">Products by Category</h2>
                                </div>
                                <div class="card-body">
                                    <div style="height: 250px;">
                                        <canvas id="categoryChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar Column -->
                <div class="col-lg-4">
                    <!-- Quick Actions -->
                    <div class="content-card">
                        <div class="card-header">
                            <h2 class="card-title">Quick Actions</h2>
                        </div>
                        <div class="card-body">
                            <a href="<?php echo base_url('modules/products/add.php'); ?>" class="quick-action-btn btn-primary">
                                <i data-lucide="package-plus" style="width: 18px; height: 18px; margin-right: 8px;"></i>
                                Add New Product
                            </a>
                            <a href="<?php echo base_url('modules/alerts/index.php'); ?>" class="quick-action-btn btn-danger">
                                <i data-lucide="bell-ring" style="width: 18px; height: 18px; margin-right: 8px;"></i>
                                View All Alerts (<?php echo $alertStats['total_active']; ?>)
                            </a>
                            <a href="<?php echo base_url('modules/reports/inventory.php'); ?>" class="quick-action-btn btn-success">
                                <i data-lucide="clipboard-list" style="width: 18px; height: 18px; margin-right: 8px;"></i>
                                Inventory Report
                            </a>
                        </div>
                    </div>

                    <!-- Recent Alerts -->
                    <div class="content-card">
                        <div class="card-header">
                            <h2 class="card-title">Recent Alerts</h2>
                            <span class="badge bg-danger"><?php echo $alertStats['total_active']; ?> Active</span>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($recentAlerts)): ?>
                            <div class="p-4 text-center text-muted">
                                <i data-lucide="check-circle" style="width: 32px; height: 32px; color: #10b981; margin-bottom: 8px;"></i>
                                <p class="mb-0">No active alerts</p>
                            </div>
                            <?php else: ?>
                            <?php foreach ($recentAlerts as $alert): ?>
                            <div class="alert-item">
                                <div class="alert-icon <?php echo $alert['alert_type']; ?>">
                                    <i data-lucide="alert-triangle" style="width: 18px; height: 18px;"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold"><?php echo htmlspecialchars($alert['product_name']); ?></div>
                                    <small class="text-muted">
                                        <?php echo ucfirst($alert['alert_type']); ?> - 
                                        <?php echo formatDate($alert['created_at'], 'M d, H:i'); ?>
                                    </small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Alert Summary -->
                    <div class="content-card">
                        <div class="card-header">
                            <h2 class="card-title">Alert Summary</h2>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span>Expired Products</span>
                                <span class="badge bg-danger rounded-pill px-3"><?php echo $alertStats['expired']; ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span>Critical (1-7 days)</span>
                                <span class="badge bg-warning rounded-pill px-3 text-dark"><?php echo $alertStats['critical']; ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span>Warning (8-30 days)</span>
                                <span class="badge bg-info rounded-pill px-3"><?php echo $alertStats['warning']; ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Low Stock</span>
                                <span class="badge bg-secondary rounded-pill px-3"><?php echo $alertStats['low_stock']; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <?php include 'includes/footer.php'; ?>
    </div>

    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="js/lucide.min.js"></script>
    <script src="js/sidebar.js"></script>
    <script src="js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Lucide Icons
            lucide.createIcons();

            // Expiry Status Chart
            const expiryCtx = document.getElementById('expiryStatusChart').getContext('2d');
            new Chart(expiryCtx, {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode(array_column($expiryStatusData, 'status')); ?>,
                    datasets: [{
                        data: <?php echo json_encode(array_column($expiryStatusData, 'count')); ?>,
                        backgroundColor: ['#dc2626', '#ea580c', '#f59e0b', '#10b981'],
                        borderWidth: 0
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                usePointStyle: true
                            }
                        }
                    },
                    cutout: '65%'
                }
            });

            // Category Distribution Chart
            const catCtx = document.getElementById('categoryChart').getContext('2d');
            new Chart(catCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode(array_column($categoryData, 'category_name')); ?>,
                    datasets: [{
                        label: 'Products',
                        data: <?php echo json_encode(array_column($categoryData, 'count')); ?>,
                        backgroundColor: '#3b82f6',
                        borderRadius: 4
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>