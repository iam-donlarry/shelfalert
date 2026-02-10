<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../classes/ShelfAlertManagerV2.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$auth->requireAuth();

// Only admins can access cron management
if (($_SESSION['role_name'] ?? '') !== 'Admin') {
    header('Location: ' . base_url('dashboard'));
    exit;
}

$alertManager = new ShelfAlertManagerV2($db);

$message = '';
$error = '';
$cronOutput = '';

// Handle manual trigger
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_cron'])) {
    ob_start();
    
    echo "Running expiry alert check...\n\n";
    
    $result = $alertManager->generateExpiryAlerts();
    
    if ($result['success']) {
        echo "✓ Alert generation completed\n";
        echo "  New alerts created: " . $result['alerts_created'] . "\n";
        $message = "Cron job executed successfully. Created " . $result['alerts_created'] . " new alert(s).";
    } else {
        echo "✗ Error: " . ($result['message'] ?? 'Unknown error') . "\n";
        $error = "Cron job failed: " . ($result['message'] ?? 'Unknown error');
    }
    
    // Log the manual run
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS cron_logs (
            log_id INT AUTO_INCREMENT PRIMARY KEY,
            job_name VARCHAR(100) NOT NULL,
            status VARCHAR(50) NOT NULL,
            details TEXT,
            executed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_job_name (job_name),
            INDEX idx_executed_at (executed_at)
        )");
        
        $logStmt = $db->prepare("INSERT INTO cron_logs (job_name, status, details, executed_at) VALUES (?, ?, ?, NOW())");
        $logStmt->execute([
            'check_expiry',
            $result['success'] ? 'completed' : 'failed',
            json_encode(['alerts_created' => $result['alerts_created'] ?? 0, 'triggered_by' => 'manual'])
        ]);
    } catch (PDOException $e) {
        // Ignore logging errors
    }
    
    $cronOutput = ob_get_clean();
}

// Get cron logs
$cronLogs = [];
try {
    $stmt = $db->query("SELECT * FROM cron_logs ORDER BY executed_at DESC LIMIT 20");
    $cronLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Table might not exist yet
}

// Get alert stats
$stats = $alertManager->getStats();

$page_title = "Cron Management";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cron Management - ShelfAlert</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="icon" href="../images/logo.png">
    <style>
        .cron-output {
            background: #1e293b;
            color: #10b981;
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 0.875rem;
            padding: 1rem;
            border-radius: 8px;
            max-height: 300px;
            overflow-y: auto;
            white-space: pre-wrap;
        }
        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.25rem;
            text-align: center;
        }
        .stat-value { font-size: 1.75rem; font-weight: 700; }
        .stat-label { color: var(--text-secondary); font-size: 0.875rem; }
        .cron-info {
            background: var(--primary-light);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.25rem;
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <?php include '../includes/header.php'; ?>
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">Cron Job Management</h1>
                <p class="page-subtitle">Manage automated expiry alert checking</p>
            </div>

            <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i data-lucide="check-circle" style="width: 18px; height: 18px; margin-right: 8px;"></i>
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i data-lucide="alert-circle" style="width: 18px; height: 18px; margin-right: 8px;"></i>
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Current Alert Stats -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-value text-danger"><?php echo $stats['expired']; ?></div>
                        <div class="stat-label">Expired Alerts</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-value text-warning"><?php echo $stats['critical']; ?></div>
                        <div class="stat-label">Critical Alerts</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-value text-info"><?php echo $stats['warning']; ?></div>
                        <div class="stat-label">Warning Alerts</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $stats['total_active']; ?></div>
                        <div class="stat-label">Total Active</div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-6">
                    <!-- Manual Trigger -->
                    <div class="content-card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i data-lucide="play" style="width: 20px; height: 20px; margin-right: 8px;"></i>
                                Run Alert Check
                            </h5>
                        </div>
                        <div class="p-4">
                            <p class="text-muted">Manually trigger the expiry alert check. This will scan all products and create alerts for items that are expired or expiring soon.</p>
                            <form method="POST">
                                <button type="submit" name="run_cron" class="btn btn-primary">
                                    <i data-lucide="zap" style="width: 18px; height: 18px; margin-right: 6px;"></i>
                                    Run Now
                                </button>
                            </form>
                            
                            <?php if ($cronOutput): ?>
                            <div class="mt-3">
                                <label class="form-label">Output:</label>
                                <div class="cron-output"><?php echo htmlspecialchars($cronOutput); ?></div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Setup Instructions -->
                    <div class="cron-info">
                        <h6><i data-lucide="info" style="width: 18px; height: 18px; margin-right: 8px;"></i>Automated Setup</h6>
                        <p class="mb-2 small">To run this automatically, set up a scheduled task:</p>
                        
                        <strong class="small">Windows Task Scheduler:</strong>
                        <pre class="bg-light p-2 rounded small mb-2">Program: C:\xampp\php\php.exe
Arguments: C:\xampp\htdocs\shelfalert\cron\check_expiry.php</pre>
                        
                        <strong class="small">Linux Crontab (daily at midnight):</strong>
                        <pre class="bg-light p-2 rounded small mb-2">0 0 * * * /usr/bin/php /path/to/shelfalert/cron/check_expiry.php</pre>
                        
                        <strong class="small">Web URL (with secret key):</strong>
                        <pre class="bg-light p-2 rounded small mb-0"><?php echo base_url('cron/check_expiry.php?key=shelfalert_cron_2024_secure'); ?></pre>
                    </div>
                </div>

                <div class="col-lg-6">
                    <!-- Cron Logs -->
                    <div class="content-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i data-lucide="history" style="width: 20px; height: 20px; margin-right: 8px;"></i>
                                Execution History
                            </h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date/Time</th>
                                        <th>Status</th>
                                        <th>Alerts</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($cronLogs)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-4 text-muted">No execution history yet</td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($cronLogs as $log): 
                                        $details = json_decode($log['details'], true) ?? [];
                                    ?>
                                    <tr>
                                        <td><?php echo formatDate($log['executed_at'], 'M d, H:i'); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $log['status'] === 'completed' ? 'success' : 'danger'; ?>">
                                                <?php echo ucfirst($log['status']); ?>
                                            </span>
                                            <?php if (!empty($details['triggered_by'])): ?>
                                            <small class="text-muted">(<?php echo $details['triggered_by']; ?>)</small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $details['alerts_created'] ?? 0; ?></td>
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

        <?php include '../includes/footer.php'; ?>
    </div>

    <script src="../js/jquery.min.js"></script>
    <script src="../js/bootstrap.bundle.min.js"></script>
    <script src="../js/lucide.min.js"></script>
    <script src="../js/sidebar.js"></script>
    <script src="../js/main.js"></script>
    <script>lucide.createIcons();</script>
</body>
</html>
