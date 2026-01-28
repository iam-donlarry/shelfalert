<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
if ($_POST) {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($auth->login($email, $password)) {
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Invalid Credentials!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ShelfAlert | Ace Supermarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="images/logo.png">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

        :root {
            --primary-color: #dc2626;
            --primary-hover: #b91c1c;
            --primary-light: #fef2f2;
            --secondary-color: #ea580c;
            --border-color: #e5e7eb;
            --hover-bg: #f9fafb;
            --text-primary: #0f172a;
            --text-secondary: #64748b;
            --body-bg: linear-gradient(135deg, #f8fafc 0%, #fef2f2 100%);
            --card-bg: #ffffff;
            --input-bg: #ffffff;
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--body-bg);
            color: var(--text-primary);
            overflow-x: hidden;
            min-height: 100vh;
        }

        .login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .login-card {
            background: var(--card-bg);
            border-radius: 1rem;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-lg);
            max-width: 960px;
            width: 100%;
            overflow: hidden;
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
        }

        .login-main {
            padding: 2.5rem 2.75rem;
        }

        .login-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 2.5rem;
        }

        .login-logo-icon {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 0.75rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 0.875rem;
            letter-spacing: -0.05em;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
        }

        .login-logo-text {
            font-weight: 700;
            letter-spacing: -0.03em;
            font-size: 1.25rem;
            color: var(--text-primary);
        }

        .login-title {
            font-weight: 700;
            font-size: 1.875rem;
            letter-spacing: -0.03em;
            margin-bottom: 0.5rem;
        }

        .login-subtitle {
            font-size: 0.9375rem;
            color: var(--text-secondary);
            margin-bottom: 2rem;
            font-weight: 500;
        }

        .error-alert {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 0.75rem 1rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
        }

        .login-form-group {
            margin-bottom: 1.25rem;
        }

        .login-label-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.35rem;
            align-items: center;
        }

        .login-label {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .login-input {
            width: 100%;
            border-radius: 0.75rem;
            border: 1px solid var(--border-color);
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            background: var(--input-bg);
            color: var(--text-primary);
            transition: var(--transition);
        }

        .login-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.12);
        }

        .login-checkbox-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            gap: 1rem;
        }

        .login-checkbox {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            font-size: 0.875rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .login-checkbox-input {
            appearance: none;
            width: 1rem;
            height: 1rem;
            border-radius: 0.35rem;
            border: 1px solid var(--border-color);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--card-bg);
            transition: var(--transition);
        }

        .login-checkbox-input:checked {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }

        .login-checkbox-input:checked::after {
            content: '';
            width: 0.5rem;
            height: 0.5rem;
            border-radius: 0.2rem;
            background: white;
        }

        .login-button {
            width: 100%;
            border-radius: 0.75rem;
            border: none;
            padding: 0.875rem 1.25rem;
            font-size: 0.9375rem;
            font-weight: 600;
            color: white;
            cursor: pointer;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .login-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 16px 24px rgba(220, 38, 38, 0.3);
        }

        .login-footnote {
            margin-top: 1.5rem;
            font-size: 0.8125rem;
            color: var(--text-secondary);
            text-align: center;
        }

        .login-side {
            padding: 2.5rem 2.75rem;
            border-left: 1px solid var(--border-color);
            background: linear-gradient(135deg, #dc2626 0%, #ea580c 100%);
            position: relative;
            color: white;
        }

        .login-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            background: rgba(255,255,255,0.2);
            font-size: 0.75rem;
            color: white;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }

        .login-side-title {
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: -0.03em;
            margin-bottom: 0.75rem;
            color: white;
        }

        .login-side-text {
            font-size: 0.9375rem;
            color: rgba(255,255,255,0.9);
            margin-bottom: 1.75rem;
            font-weight: 500;
        }

        .login-side-metrics {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }

        .login-side-metric {
            padding: 0.85rem 0.9rem;
            border-radius: 0.9rem;
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.2);
            backdrop-filter: blur(8px);
        }

        .login-side-metric-label {
            font-size: 0.75rem;
            color: rgba(255,255,255,0.8);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 0.1rem;
            font-weight: 600;
        }

        .login-side-metric-value {
            font-size: 1.125rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: white;
        }

        .login-side-badge {
            position: absolute;
            bottom: 2.25rem;
            right: 2.5rem;
            font-size: 0.75rem;
            color: rgba(255,255,255,0.9);
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.4rem 0.75rem;
            border-radius: 999px;
            background: rgba(255,255,255,0.15);
            font-weight: 500;
        }

        @media (max-width: 900px) {
            .login-card {
                grid-template-columns: 1fr;
            }
            .login-side {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-main">
                <div class="login-logo">
                    <div class="login-logo-icon">
                        <i data-lucide="package-check" style="width: 20px; height: 20px;"></i>
                    </div>
                    <div class="login-logo-text">ShelfAlert</div>
                </div>
                <h1 class="login-title">Sign in to your account</h1>
                <p class="login-subtitle">Access the product expiry monitoring system.</p>

                <?php if ($error): ?>
                    <div class="error-alert">
                        <strong><?php echo htmlspecialchars($error); ?></strong>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="login-form-group">
                        <div class="login-label-row">
                            <label class="login-label" for="email">Username or Email</label>
                        </div>
                        <input type="text" class="login-input" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" placeholder="admin@acesupermarket.com" required autofocus>
                    </div>
                    <div class="login-form-group">
                        <div class="login-label-row">
                            <label class="login-label" for="password">Password</label>
                        </div>
                        <input id="password" type="password" class="login-input" name="password" placeholder="••••••••" required>
                    </div>

                    <div class="login-checkbox-row">
                        <label class="login-checkbox">
                            <input type="checkbox" class="login-checkbox-input" name="remember">
                            <span>Remember me</span>
                        </label>
                    </div>

                    <button type="submit" class="login-button">
                        <i data-lucide="log-in" style="width: 18px; height: 18px; stroke-width: 1.5;"></i>
                        Sign in
                    </button>

                    <div class="login-footnote">
                        Ace Supermarket - Product Expiry Alert System
                    </div>
                </form>
            </div>
            <div class="login-side">
                <div class="login-tag">
                    <i data-lucide="shield-check" style="width: 14px; height: 14px; stroke-width: 1.5;"></i>
                    Secure Inventory Management
                </div>
                <h2 class="login-side-title">Product Expiry Monitoring</h2>
                <p class="login-side-text">
                    Track product expiration dates, receive timely alerts, and generate comprehensive reports to minimize waste and ensure customer safety.
                </p>
                <div class="login-side-metrics">
                    <div class="login-side-metric">
                        <div class="login-side-metric-label">Products</div>
                        <div class="login-side-metric-value">Track All</div>
                    </div>
                    <div class="login-side-metric">
                        <div class="login-side-metric-label">Alerts</div>
                        <div class="login-side-metric-value">Real-time</div>
                    </div>
                    <div class="login-side-metric">
                        <div class="login-side-metric-label">Reports</div>
                        <div class="login-side-metric-value">Detailed</div>
                    </div>
                    <div class="login-side-metric">
                        <div class="login-side-metric-label">Monitoring</div>
                        <div class="login-side-metric-value">24/7</div>
                    </div>
                </div>
                <div class="login-side-badge">
                    <span>Reduce Waste • Ensure Safety</span>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize Lucide Icons
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();
        });
    </script>
</body>
</html>