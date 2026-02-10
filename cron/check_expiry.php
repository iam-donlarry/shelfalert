<?php
/**
 * ShelfAlert - Expiry Alert Cron Job
 * 
 * This script checks all products for expiry dates and generates alerts.
 * Run this script periodically (recommended: every day at midnight)
 * 
 * USAGE:
 * 
 * 1. Via Command Line (recommended for cron):
 *    php c:\xampp\htdocs\shelfalert\cron\check_expiry.php
 * 
 * 2. Via Web Browser (with secret key):
 *    http://localhost/shelfalert/cron/check_expiry.php?key=YOUR_SECRET_KEY
 * 
 * 3. Windows Task Scheduler:
 *    Program: C:\xampp\php\php.exe
 *    Arguments: C:\xampp\htdocs\shelfalert\cron\check_expiry.php
 *    Schedule: Daily at 00:00
 * 
 * 4. Linux Crontab:
 *    0 0 * * * /usr/bin/php /path/to/shelfalert/cron/check_expiry.php >> /var/log/shelfalert_cron.log 2>&1
 */

// Configuration
define('CRON_SECRET_KEY', 'shelfalert_cron_2024_secure'); // Change this for production!
define('ALLOW_CLI', true);      // Allow command line execution
define('ALLOW_WEB', true);      // Allow web-based execution with key
define('LOG_TO_FILE', true);    // Log output to file
define('LOG_FILE', __DIR__ . '/../logs/cron_expiry.log');

// Check execution context
$isCli = (php_sapi_name() === 'cli');
$isValidWebRequest = false;

if (!$isCli) {
    // Web request - verify secret key
    $providedKey = $_GET['key'] ?? '';
    $isValidWebRequest = ($providedKey === CRON_SECRET_KEY);
    
    if (!ALLOW_WEB || !$isValidWebRequest) {
        header('HTTP/1.1 403 Forbidden');
        exit('Access denied. Invalid or missing key.');
    }
    
    header('Content-Type: text/plain');
} else {
    if (!ALLOW_CLI) {
        exit("CLI execution is disabled.\n");
    }
}

// Start output buffering for logging
ob_start();

echo "==============================================\n";
echo "ShelfAlert - Expiry Alert Check\n";
echo "Started: " . date('Y-m-d H:i:s') . "\n";
echo "==============================================\n\n";

// Load required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/ShelfAlertManagerV2.php';

try {
    // Connect to database
    $database = new Database();
    $db = $database->getConnection();
    
    echo "[OK] Database connection established\n\n";
    
    // Initialize Alert Manager
    $alertManager = new ShelfAlertManagerV2($db);
    
    echo "[STEP 1] Generating expiry alerts...\n";
    
    // Generate alerts
    $result = $alertManager->generateExpiryAlerts();
    
    if ($result['success']) {
        echo "[OK] Alert generation completed\n";
        echo "     - New alerts created: " . $result['alerts_created'] . "\n\n";
    } else {
        echo "[ERROR] Alert generation failed: " . ($result['message'] ?? 'Unknown error') . "\n\n";
    }
    
    // Get current alert statistics
    echo "[STEP 2] Current alert statistics...\n";
    $stats = $alertManager->getStats();
    
    echo "     - Expired products: " . $stats['expired'] . "\n";
    echo "     - Critical (1-7 days): " . $stats['critical'] . "\n";
    echo "     - Warning (8-30 days): " . $stats['warning'] . "\n";
    echo "     - Low stock: " . $stats['low_stock'] . "\n";
    echo "     - Total active alerts: " . $stats['total_active'] . "\n";
    echo "     - Alerts created today: " . $stats['today'] . "\n\n";
    
    // Clean up old resolved alerts (optional - keep for 90 days)
    echo "[STEP 3] Cleaning up old alerts...\n";
    try {
        $cleanupQuery = "DELETE FROM alerts WHERE status = 'resolved' AND resolved_at < DATE_SUB(NOW(), INTERVAL 90 DAY)";
        $cleanedUp = $db->exec($cleanupQuery);
        echo "[OK] Cleaned up {$cleanedUp} old resolved alerts\n\n";
    } catch (PDOException $e) {
        echo "[WARN] Could not clean old alerts: " . $e->getMessage() . "\n\n";
    }
    
    // Log this cron run
    echo "[STEP 4] Logging cron execution...\n";
    try {
        $logQuery = "INSERT INTO cron_logs (job_name, status, details, executed_at) VALUES (?, ?, ?, NOW())";
        $logStmt = $db->prepare($logQuery);
        $logStmt->execute([
            'check_expiry',
            'completed',
            json_encode([
                'alerts_created' => $result['alerts_created'] ?? 0,
                'stats' => $stats
            ])
        ]);
        echo "[OK] Cron execution logged\n\n";
    } catch (PDOException $e) {
        // cron_logs table might not exist, create it
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
            echo "[INFO] Created cron_logs table\n\n";
        } catch (PDOException $e2) {
            echo "[WARN] Could not log cron execution\n\n";
        }
    }
    
    $exitCode = 0;
    echo "[SUCCESS] Cron job completed successfully!\n";
    
} catch (Exception $e) {
    $exitCode = 1;
    echo "[FATAL ERROR] " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n==============================================\n";
echo "Finished: " . date('Y-m-d H:i:s') . "\n";
echo "==============================================\n";

// Get output and log it
$output = ob_get_flush();

// Log to file if enabled
if (LOG_TO_FILE) {
    $logDir = dirname(LOG_FILE);
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    
    $logEntry = "\n" . str_repeat('=', 60) . "\n";
    $logEntry .= $output;
    
    @file_put_contents(LOG_FILE, $logEntry, FILE_APPEND);
}

// Exit with appropriate code for CLI
if ($isCli) {
    exit($exitCode ?? 0);
}
