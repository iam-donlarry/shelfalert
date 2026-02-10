<?php
/**
 * ShelfAlert - User Profile Page
 * Admin only access for managing own profile and password
 */
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

// Restrict to logged in users
$auth->requireAuth();

// Per requirements: Only Admin has a profile page
if (($_SESSION['role_name'] ?? '') !== 'Admin') {
    header("Location: dashboard.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$profile_user = $auth->getUserInfo($user_id);
$activities = $auth->getRecentActivity($user_id);
$system_stats = $auth->getSystemStats();

$success_msg = '';
$error_msg = '';

// Handle Profile Update
if (isset($_POST['update_profile'])) {
    $data = [
        'full_name' => trim($_POST['full_name']),
        'phone' => trim($_POST['phone'])
    ];
    
    if (empty($data['full_name'])) {
        $error_msg = "Full Name is required";
    } else {
        $result = $auth->updateProfile($user_id, $data);
        if ($result['success']) {
            $success_msg = $result['message'];
            $profile_user = $auth->getUserInfo($user_id); // Refresh data
        } else {
            $error_msg = $result['message'];
        }
    }
}
// ... (Password section uses $user_id, so it's fine) ...

// HTML section
$page_title = "Profile";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - ShelfAlert</title>
    <!-- CSS -->
    <link href="<?php echo base_url('css/bootstrap.min.css'); ?>" rel="stylesheet">
    <link href="<?php echo base_url('css/style.css'); ?>" rel="stylesheet">
    <link rel="icon" href="<?php echo base_url('images/logo.png'); ?>">
    <style>
        .profile-header {
            background: white;
            color: var(--text-primary);
            padding: 2.5rem 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
        }
        .profile-avatar-large {
            width: 80px;
            height: 80px;
            background: var(--primary-light);
            color: var(--primary-color);
            border: 2px solid var(--primary-light);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 700;
        }
        .nav-tabs-custom {
            border-bottom: 2px solid var(--border-color);
            margin-bottom: 0;
            background: white;
            padding: 0 1rem;
        }
        .nav-tabs-custom .nav-link {
            border: none;
            color: var(--text-secondary);
            font-weight: 600;
            padding: 1rem 1.5rem;
            position: relative;
            background: transparent;
        }
        .nav-tabs-custom .nav-link.active {
            color: var(--primary-color);
        }
        .nav-tabs-custom .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: var(--primary-color);
        }
        .info-label {
            color: var(--text-secondary);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
        }
        .info-value {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1.25rem;
        }
        .readonly-field {
            background-color: var(--hover-bg);
            cursor: not-allowed;
            border-color: var(--border-color);
            color: var(--text-secondary);
        }
        .stat-card {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.25rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all 0.2s;
        }
        .stat-icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .stat-value { font-size: 1.25rem; font-weight: 700; color: var(--text-primary); }
        .stat-label { font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase; font-weight: 600; }
        
        .activity-item {
            position: relative;
            padding-left: 2rem;
            padding-bottom: 1.5rem;
            border-left: 2px solid var(--border-color);
            margin-left: 0.5rem;
        }
        .activity-item:last-child { border-left-color: transparent; }
        .activity-item::before {
            content: '';
            position: absolute;
            left: -7px;
            top: 0;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--primary-color);
            border: 2px solid white;
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <?php include 'includes/header.php'; ?>
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <!-- Header Section -->
            <div class="profile-header">
                <div class="profile-avatar-large">
                    <?php echo strtoupper(substr($profile_user['full_name'] ?? 'U', 0, 1)); ?>
                </div>
                <div>
                    <h2 class="mb-1"><?php echo htmlspecialchars($profile_user['full_name'] ?? 'Admin'); ?></h2>
                    <p class="mb-0 text-secondary">
                        <i data-lucide="shield" style="width: 14px; height: 14px; margin-right: 4px;"></i>
                        <?php echo htmlspecialchars($_SESSION['role_name'] ?? 'Administrator'); ?> &bull; @<?php echo htmlspecialchars($profile_user['username'] ?? 'admin'); ?>
                    </p>
                </div>
            </div>

            <!-- Messages -->
            <?php if ($success_msg): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if ($error_msg): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="content-card p-0 overflow-hidden">
                <!-- Tab Navigation -->
                <ul class="nav nav-tabs nav-tabs-custom" id="profileTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button" role="tab">
                            <i data-lucide="user" style="width: 18px; height: 18px; margin-right: 8px;"></i>Details
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="insights-tab" data-bs-toggle="tab" data-bs-target="#insights" type="button" role="tab">
                            <i data-lucide="layout-dashboard" style="width: 18px; height: 18px; margin-right: 8px;"></i>Insights
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="activity-tab" data-bs-toggle="tab" data-bs-target="#activity" type="button" role="tab">
                            <i data-lucide="history" style="width: 18px; height: 18px; margin-right: 8px;"></i>Activity
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab">
                            <i data-lucide="lock" style="width: 18px; height: 18px; margin-right: 8px;"></i>Security
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content p-4" id="profileTabsContent">
                    <!-- Tab 1: Details -->
                    <div class="tab-pane active" id="details" role="tabpanel">
                        <div class="row">
                            <div class="col-md-7">
                                <form method="POST" action="">
                                    <div class="mb-3">
                                        <label class="form-label">Full Name</label>
                                        <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($profile_user['full_name']); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Email Address</label>
                                        <input type="email" class="form-control readonly-field" value="<?php echo htmlspecialchars($profile_user['email']); ?>" readonly>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Phone Number</label>
                                        <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($profile_user['phone'] ?? ''); ?>">
                                    </div>
                                    <div class="mt-4">
                                        <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                                    </div>
                                </form>
                            </div>
                            <div class="col-md-5 d-none d-md-block border-start ps-4">
                                <div class="info-label">Account Info</div>
                                <p class="small text-muted mb-4">You are logged in with **<?php echo htmlspecialchars($_SESSION['role_name'] ?? 'Admin'); ?>** privileges. Contact the system owner if you need to change your unique username or email address.</p>
                                <div class="info-label">Last Login</div>
                                <div class="info-value"><?php echo $profile_user['last_login'] ? date('M j, Y, H:i', strtotime($profile_user['last_login'])) : 'Never'; ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 2: Insights -->
                    <div class="tab-pane" id="insights" role="tabpanel">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="stat-card">
                                    <div class="stat-icon bg-danger-light text-danger">
                                        <i data-lucide="alert-triangle"></i>
                                    </div>
                                    <div class="stat-info">
                                        <span class="stat-value"><?php echo $system_stats['active_alerts']; ?></span><br>
                                        <span class="stat-label">Active Alerts</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="stat-card">
                                    <div class="stat-icon bg-warning-light text-warning">
                                        <i data-lucide="hourglass"></i>
                                    </div>
                                    <div class="stat-info">
                                        <span class="stat-value"><?php echo $system_stats['critical_expiry']; ?></span><br>
                                        <span class="stat-label">Expiring Soon</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="stat-card">
                                    <div class="stat-icon bg-info-light text-info">
                                        <i data-lucide="users"></i>
                                    </div>
                                    <div class="stat-info">
                                        <span class="stat-value"><?php echo $system_stats['total_users']; ?></span><br>
                                        <span class="stat-label">Active Users</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 3: Activity -->
                    <div class="tab-pane" id="activity" role="tabpanel">
                        <h6 class="mb-4">Recent Actions</h6>
                        <?php if (empty($activities)): ?>
                            <p class="text-muted">No recent activity detected.</p>
                        <?php else: ?>
                            <div class="activity-timeline">
                                <?php foreach ($activities as $log): ?>
                                    <div class="activity-item">
                                        <div class="small text-muted"><?php echo date('M j, Y, H:i', strtotime($log['created_at'])); ?></div>
                                        <div class="fw-bold"><?php echo htmlspecialchars($log['action']); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Tab 4: Security (The one that was missing) -->
                    <div class="tab-pane" id="security" role="tabpanel">
                        <div class="row">
                            <div class="col-md-7">
                                <form method="POST" action="">
                                    <div class="mb-3">
                                        <label class="form-label">Current Password</label>
                                        <input type="password" name="current_password" class="form-control" required>
                                    </div>
                                    <hr class="my-4">
                                    <div class="mb-3">
                                        <label class="form-label">New Password</label>
                                        <input type="password" name="new_password" id="new_password" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Confirm New Password</label>
                                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                                    </div>
                                    <div id="match_message" class="small mb-3" style="display:none;"></div>
                                    <div class="mt-4">
                                        <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                                    </div>
                                </form>
                            </div>
                            <div class="col-md-5 d-none d-md-block border-start ps-4">
                                <h6 class="mb-3">Password Requirements</h6>
                                <ul class="small text-muted list-unstyled" style="line-height: 2;">
                                    <li id="req_len"><i data-lucide="circle" style="width: 14px; height: 14px; margin-right: 8px;"></i> Minimum 8 characters</li>
                                    <li id="req_upper"><i data-lucide="circle" style="width: 14px; height: 14px; margin-right: 8px;"></i> One uppercase letter (A-Z)</li>
                                    <li id="req_lower"><i data-lucide="circle" style="width: 14px; height: 14px; margin-right: 8px;"></i> One lowercase letter (a-z)</li>
                                    <li id="req_num"><i data-lucide="circle" style="width: 14px; height: 14px; margin-right: 8px;"></i> One number (0-9)</li>
                                    <li id="req_spec"><i data-lucide="circle" style="width: 14px; height: 14px; margin-right: 8px;"></i> One special character (@#$%^&*)</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div> <!-- End Tab Content -->
            </div> <!-- End Content Card -->
        </main>

        <?php include 'includes/footer.php'; ?>
    </div>

    <!-- Scripts -->
    <script src="/js/jquery.min.js'); ?>"></script>
    <script src="<?php echo base_url('js/bootstrap.bundle.min.js'); ?>"></script>
    <script src="<?php echo base_url('js/lucide.min.js'); ?>"></script>
    <script src="<?php echo base_url('js/sidebar.js'); ?>"></script>
    <script src="<?php echo base_url('js/main.js'); ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();

            const password = document.getElementById('new_password');
            const confirm = document.getElementById('confirm_password');
            const matchMsg = document.getElementById('match_message');

            const reqs = {
                len: { el: document.getElementById('req_len'), reg: /.{8,}/ },
                upper: { el: document.getElementById('req_upper'), reg: /[A-Z]/ },
                lower: { el: document.getElementById('req_lower'), reg: /[a-z]/ },
                num: { el: document.getElementById('req_num'), reg: /[0-9]/ },
                spec: { el: document.getElementById('req_spec'), reg: /[^A-Za-z0-9]/ }
            };

            function validate() {
                const val = password.value;
                Object.keys(reqs).forEach(k => {
                    const isValid = reqs[k].reg.test(val);
                    const el = reqs[k].el;
                    const icon = el.children[0];
                    
                    if (isValid) {
                        el.classList.remove('text-muted');
                        el.classList.add('text-success');
                        icon.setAttribute('data-lucide', 'check-circle-2');
                        icon.style.color = 'var(--success)';
                    } else {
                        el.classList.add('text-muted');
                        el.classList.remove('text-success');
                        icon.setAttribute('data-lucide', 'circle');
                        icon.style.color = '';
                    }
                });
                lucide.createIcons();
                checkMatch();
            }

            function checkMatch() {
                if (!confirm.value) {
                    matchMsg.style.display = 'none';
                    return;
                }
                matchMsg.style.display = 'block';
                if (password.value === confirm.value) {
                    matchMsg.className = 'small mb-3 text-success';
                    matchMsg.innerHTML = '<i data-lucide="check-circle-2" style="width:14px;height:14px;"></i> Passwords match';
                } else {
                    matchMsg.className = 'small mb-3 text-danger';
                    matchMsg.innerHTML = '<i data-lucide="x-circle" style="width:14px;height:14px;"></i> Passwords do not match';
                }
                lucide.createIcons();
            }

            password.addEventListener('input', validate);
            confirm.addEventListener('input', checkMatch);
        });
    </script>
</body>
</html>
