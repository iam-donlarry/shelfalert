<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$auth->requireAuth();

// Only admins can manage settings
// Only admins can manage settings
if (($_SESSION['role_name'] ?? '') !== 'Admin') {
    header('Location: ' . base_url('dashboard'));
    exit;
}

$message = '';
$error = '';

// Get current settings
function getSettings($db) {
    try {
        $stmt = $db->query("SELECT setting_key, setting_value FROM system_settings");
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    } catch (PDOException $e) {
        return [];
    }
}

// Save settings
function saveSetting($db, $key, $value) {
    try {
        $stmt = $db->prepare("
            INSERT INTO system_settings (setting_key, setting_value) 
            VALUES (:key, :value)
            ON DUPLICATE KEY UPDATE setting_value = :value2
        ");
        $stmt->execute([':key' => $key, ':value' => $value, ':value2' => $value]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $success = true;
    
    // Save each setting
    $settingsToSave = [
        'company_name' => $_POST['company_name'] ?? 'Ace Supermarket',
        'company_address' => $_POST['company_address'] ?? '',
        'company_phone' => $_POST['company_phone'] ?? '',
        'company_email' => $_POST['company_email'] ?? '',
        'warning_days' => $_POST['warning_days'] ?? '30',
        'critical_days' => $_POST['critical_days'] ?? '7',
        'low_stock_threshold' => $_POST['low_stock_threshold'] ?? '10'
    ];
    
    foreach ($settingsToSave as $key => $value) {
        if (!saveSetting($db, $key, $value)) {
            $success = false;
        }
    }
    
    if ($success) {
        $message = 'Settings saved successfully';
    } else {
        $error = 'Error saving some settings';
    }
}

$settings = getSettings($db);
$page_title = "System Settings";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - ShelfAlert | Ace Supermarket</title>
    <link href="<?php echo asset_url('css/bootstrap.min.css'); ?>" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo asset_url('css/style.css'); ?>">
    <link rel="icon" href="<?php echo base_url('images/logo.png'); ?>">
</head>
<body>
    <div class="main-wrapper">
        <?php include 'includes/header.php'; ?>
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">System Settings</h1>
                <p class="page-subtitle">Configure ShelfAlert system preferences</p>
            </div>

            <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <form method="POST">
                <!-- Company Information -->
                <div class="content-card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i data-lucide="building-2" style="width: 20px; height: 20px; margin-right: 8px;"></i>
                            Company Information
                        </h5>
                    </div>
                    <div class="p-4">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Company Name</label>
                                <input type="text" name="company_name" class="form-control" 
                                    value="<?php echo htmlspecialchars($settings['company_name'] ?? 'Ace Supermarket'); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="company_email" class="form-control" 
                                    value="<?php echo htmlspecialchars($settings['company_email'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" name="company_phone" class="form-control" 
                                    value="<?php echo htmlspecialchars($settings['company_phone'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Address</label>
                                <input type="text" name="company_address" class="form-control" 
                                    value="<?php echo htmlspecialchars($settings['company_address'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Alert Thresholds -->
                <div class="content-card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i data-lucide="bell-ring" style="width: 20px; height: 20px; margin-right: 8px;"></i>
                            Alert Thresholds
                        </h5>
                    </div>
                    <div class="p-4">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Warning Days</label>
                                <input type="number" name="warning_days" class="form-control" min="1" max="90"
                                    value="<?php echo htmlspecialchars($settings['warning_days'] ?? '30'); ?>">
                                <small class="text-muted">Products expiring within this many days get a warning alert</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Critical Days</label>
                                <input type="number" name="critical_days" class="form-control" min="1" max="30"
                                    value="<?php echo htmlspecialchars($settings['critical_days'] ?? '7'); ?>">
                                <small class="text-muted">Products expiring within this many days get a critical alert</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Low Stock Threshold</label>
                                <input type="number" name="low_stock_threshold" class="form-control" min="1"
                                    value="<?php echo htmlspecialchars($settings['low_stock_threshold'] ?? '10'); ?>">
                                <small class="text-muted">Default units below which stock is considered low</small>
                            </div>
                        </div>
                        
                        <div class="alert alert-info mt-3">
                            <strong>How alerts work:</strong>
                            <ul class="mb-0 mt-2">
                                <li><span class="badge badge-danger">Expired</span> - Past expiry date</li>
                                <li><span class="badge badge-warning">Critical</span> - Within <?php echo htmlspecialchars($settings['critical_days'] ?? '7'); ?> days of expiry</li>
                                <li><span class="badge badge-indigo">Warning</span> - Within <?php echo htmlspecialchars($settings['warning_days'] ?? '30'); ?> days of expiry</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- System Info -->
                <div class="content-card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i data-lucide="info" style="width: 20px; height: 20px; margin-right: 8px;"></i>
                            System Information
                        </h5>
                    </div>
                    <div class="p-4">
                        <div class="row">
                            <div class="col-md-4">
                                <p><strong>System:</strong> ShelfAlert v1.0</p>
                            </div>
                            <div class="col-md-4">
                                <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
                            </div>
                            <div class="col-md-4">
                                <p><strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="<?php echo base_url('dashboard'); ?>" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="save" style="width: 18px; height: 18px; margin-right: 6px;"></i>
                        Save Settings
                    </button>
                </div>
            </form>
        </main>

        <?php include 'includes/footer.php'; ?>
    </div>

    <script src="<?php echo asset_url('js/jquery.min.js'); ?>"></script>
    <script src="<?php echo asset_url('js/bootstrap.bundle.min.js'); ?>"></script>
    <script src="<?php echo asset_url('js/lucide.min.js'); ?>"></script>
    <script src="<?php echo asset_url('js/sidebar.js'); ?>"></script>
    <script src="<?php echo asset_url('js/main.js'); ?>"></script>
    <script>lucide.createIcons();</script>
</body>
</html>
