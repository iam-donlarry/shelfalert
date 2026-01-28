<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../classes/AlertManager.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$auth->requireAuth();

$alertManager = new AlertManager($db);

// Generate new alerts
$alertManager->generateExpiryAlerts();

// Get filter parameters
$filters = [
    'status' => $_GET['status'] ?? 'active',
    'alert_type' => $_GET['type'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? ''
];

$alerts = $alertManager->getAll($filters);
$stats = $alertManager->getStats();

// Handle acknowledge action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'acknowledge' && isset($_POST['alert_id'])) {
        $result = $alertManager->acknowledge($_POST['alert_id'], $_SESSION['user_id']);
        if ($result['success']) {
            header('Location: index.php?success=Alert acknowledged');
            exit;
        }
    }
}

$page_title = "Expiry Alerts";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expiry Alerts - ShelfAlert | Ace Supermarket</title>
    <link href="../../css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="icon" href="../../images/logo.png">
    <style>
        .stats-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        
        .stat-mini {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            flex: 1;
            min-width: 150px;
        }
        
        .stat-mini-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .stat-mini-icon.expired { background: #fee2e2; color: #dc2626; }
        .stat-mini-icon.critical { background: #ffedd5; color: #ea580c; }
        .stat-mini-icon.warning { background: #fef3c7; color: #ca8a04; }
        .stat-mini-icon.total { background: #dbeafe; color: #2563eb; }
        
        .stat-mini-value {
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .stat-mini-label {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }
        
        .filter-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
        }
        
        .alert-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            overflow: hidden;
        }
        
        .alert-item {
            display: flex;
            align-items: flex-start;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--border-color);
            gap: 1rem;
        }
        
        .alert-item:last-child {
            border-bottom: none;
        }
        
        .alert-item:hover {
            background: var(--hover-bg);
        }
        
        .alert-type-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .alert-type-icon.expired { background: #fee2e2; color: #dc2626; }
        .alert-type-icon.critical { background: #ffedd5; color: #ea580c; }
        .alert-type-icon.warning { background: #fef3c7; color: #ca8a04; }
        .alert-type-icon.low_stock { background: #f3f4f6; color: #6b7280; }
        
        .alert-content {
            flex-grow: 1;
        }
        
        .alert-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .alert-message {
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }
        
        .alert-meta {
            display: flex;
            gap: 1rem;
            font-size: 0.75rem;
            color: var(--text-secondary);
        }
        
        .alert-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .alert-badge.expired { background: #fee2e2; color: #dc2626; }
        .alert-badge.critical { background: #ffedd5; color: #ea580c; }
        .alert-badge.warning { background: #fef3c7; color: #ca8a04; }
        .alert-badge.acknowledged { background: #dcfce7; color: #16a34a; }
        .alert-badge.resolved { background: #f3f4f6; color: #6b7280; }
        
        .alert-actions {
            display: flex;
            gap: 0.5rem;
            align-items: flex-start;
        }
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
                    <h1 class="page-title">Expiry Alerts</h1>
                    <p class="page-subtitle">Monitor and manage product expiry notifications</p>
                </div>
            </div>

            <!-- Stats Row -->
            <div class="stats-row">
                <div class="stat-mini">
                    <div class="stat-mini-icon total">
                        <i data-lucide="bell" style="width: 20px; height: 20px;"></i>
                    </div>
                    <div>
                        <div class="stat-mini-value"><?php echo $stats['total_active']; ?></div>
                        <div class="stat-mini-label">Total Active</div>
                    </div>
                </div>
                <div class="stat-mini">
                    <div class="stat-mini-icon expired">
                        <i data-lucide="x-circle" style="width: 20px; height: 20px;"></i>
                    </div>
                    <div>
                        <div class="stat-mini-value"><?php echo $stats['expired']; ?></div>
                        <div class="stat-mini-label">Expired</div>
                    </div>
                </div>
                <div class="stat-mini">
                    <div class="stat-mini-icon critical">
                        <i data-lucide="alert-triangle" style="width: 20px; height: 20px;"></i>
                    </div>
                    <div>
                        <div class="stat-mini-value"><?php echo $stats['critical']; ?></div>
                        <div class="stat-mini-label">Critical</div>
                    </div>
                </div>
                <div class="stat-mini">
                    <div class="stat-mini-icon warning">
                        <i data-lucide="clock" style="width: 20px; height: 20px;"></i>
                    </div>
                    <div>
                        <div class="stat-mini-value"><?php echo $stats['warning']; ?></div>
                        <div class="stat-mini-label">Warning</div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="filter-card">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="active" <?php echo $filters['status'] === 'active' ? 'selected' : ''; ?>>Active Only</option>
                            <option value="acknowledged" <?php echo $filters['status'] === 'acknowledged' ? 'selected' : ''; ?>>Acknowledged</option>
                            <option value="resolved" <?php echo $filters['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                            <option value="" <?php echo $filters['status'] === '' ? 'selected' : ''; ?>>All Statuses</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Alert Type</label>
                        <select name="type" class="form-select">
                            <option value="">All Types</option>
                            <option value="expired" <?php echo $filters['alert_type'] === 'expired' ? 'selected' : ''; ?>>Expired</option>
                            <option value="critical" <?php echo $filters['alert_type'] === 'critical' ? 'selected' : ''; ?>>Critical</option>
                            <option value="warning" <?php echo $filters['alert_type'] === 'warning' ? 'selected' : ''; ?>>Warning</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">From Date</label>
                        <input type="date" name="date_from" class="form-control" value="<?php echo $filters['date_from']; ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">To Date</label>
                        <input type="date" name="date_to" class="form-control" value="<?php echo $filters['date_to']; ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i data-lucide="search" style="width: 16px; height: 16px; margin-right: 6px;"></i>
                            Filter
                        </button>
                    </div>
                </form>
            </div>

            <!-- Alerts List -->
            <div class="alert-card">
                <?php if (empty($alerts)): ?>
                <div class="p-5 text-center text-muted">
                    <i data-lucide="check-circle" style="width: 48px; height: 48px; color: #10b981; margin-bottom: 12px;"></i>
                    <h5>No Alerts Found</h5>
                    <p class="mb-0">No alerts match your filter criteria.</p>
                </div>
                <?php else: ?>
                <?php foreach ($alerts as $alert): ?>
                <div class="alert-item">
                    <div class="alert-type-icon <?php echo $alert['alert_type']; ?>">
                        <i data-lucide="<?php echo $alert['alert_type'] === 'expired' ? 'x-circle' : 'alert-triangle'; ?>" style="width: 22px; height: 22px;"></i>
                    </div>
                    <div class="alert-content">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <div class="alert-title"><?php echo htmlspecialchars($alert['product_name']); ?></div>
                            <span class="alert-badge <?php echo $alert['alert_type']; ?>"><?php echo ucfirst($alert['alert_type']); ?></span>
                        </div>
                        <div class="alert-message"><?php echo htmlspecialchars($alert['alert_message']); ?></div>
                        <div class="alert-meta">
                            <span><i data-lucide="tag" style="width: 12px; height: 12px;"></i> <?php echo htmlspecialchars($alert['product_code']); ?></span>
                            <span><i data-lucide="folder" style="width: 12px; height: 12px;"></i> <?php echo htmlspecialchars($alert['category_name'] ?? 'Uncategorized'); ?></span>
                            <span><i data-lucide="clock" style="width: 12px; height: 12px;"></i> Created: <?php echo formatDate($alert['created_at'], 'M d, H:i'); ?></span>
                        </div>
                    </div>
                    <div class="alert-actions">
                        <?php if ($alert['status'] === 'active'): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="acknowledge">
                            <input type="hidden" name="alert_id" value="<?php echo $alert['alert_id']; ?>">
                            <button type="submit" class="btn btn-sm btn-outline-success" title="Acknowledge">
                                <i data-lucide="check" style="width: 16px; height: 16px;"></i>
                            </button>
                        </form>
                        <?php else: ?>
                        <span class="alert-badge <?php echo $alert['status']; ?>"><?php echo ucfirst($alert['status']); ?></span>
                        <?php endif; ?>
                        <a href="../products/view.php?id=<?php echo $alert['product_id']; ?>" class="btn btn-sm btn-outline-primary" title="View Product">
                            <i data-lucide="eye" style="width: 16px; height: 16px;"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="mt-3 text-muted">
                <small>Showing <?php echo count($alerts); ?> alert(s)</small>
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
