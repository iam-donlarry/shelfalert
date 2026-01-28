<?php
/**
 * ShelfAlert - Sidebar Navigation
 * Updated menu items for product expiry monitoring
 */
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
$user = $auth->getCurrentUser();
$is_admin = ($user['role_name'] ?? '') === 'Admin';

function isActivePage($page, $current) {
    return ($page === $current) ? 'active' : '';
}
?>
<aside class="sidebar" id="sidebar">
    <nav class="sidebar-nav">
        <div class="nav-section">
            <ul style="padding: 0;">

                <!-- Dashboard -->
                <li class="nav-item">
                    <a href="<?= base_url('dashboard'); ?>" class="nav-link <?php echo isActivePage('dashboard.php', $current_page); ?>">
                        <span class="nav-icon">
                            <i data-lucide="layout-dashboard" style="width: 20px; height: 20px; stroke-width: 1.5;"></i>
                        </span>
                        <span class="nav-text">Dashboard</span>
                        <span class="nav-tooltip">Dashboard</span>
                    </a>
                </li>

                <!-- Products -->
                <li class="nav-item">
                    <a href="<?= base_url('modules/products/index'); ?>" class="nav-link <?php echo ($current_page === 'index.php' && $current_dir === 'products') ? 'active' : ''; ?>">
                        <span class="nav-icon">
                            <i data-lucide="package" style="width: 20px; height: 20px; stroke-width: 1.5;"></i>
                        </span>
                        <span class="nav-text">All Products</span>
                        <span class="nav-tooltip">All Products</span>
                    </a>
                </li>

                <!-- Add Product -->
                <li class="nav-item">
                    <a href="<?= base_url('modules/products/add'); ?>" class="nav-link <?php echo isActivePage('add.php', $current_page); ?>">
                        <span class="nav-icon">
                            <i data-lucide="package-plus" style="width: 20px; height: 20px; stroke-width: 1.5;"></i>
                        </span>
                        <span class="nav-text">Add Product</span>
                        <span class="nav-tooltip">Add Product</span>
                    </a>
                </li>

                <!-- Expiry Alerts -->
                <li class="nav-item">
                    <a href="<?= base_url('modules/alerts/index'); ?>" class="nav-link <?php echo ($current_dir === 'alerts') ? 'active' : ''; ?>">
                        <span class="nav-icon">
                            <i data-lucide="bell-ring" style="width: 20px; height: 20px; stroke-width: 1.5;"></i>
                        </span>
                        <span class="nav-text">Expiry Alerts</span>
                        <span class="nav-tooltip">Expiry Alerts</span>
                    </a>
                </li>

                <!-- Inventory Report -->
                <li class="nav-item">
                    <a href="<?= base_url('modules/reports/inventory'); ?>" class="nav-link <?php echo isActivePage('inventory.php', $current_page); ?>">
                        <span class="nav-icon">
                            <i data-lucide="clipboard-list" style="width: 20px; height: 20px; stroke-width: 1.5;"></i>
                        </span>
                        <span class="nav-text">Inventory Report</span>
                        <span class="nav-tooltip">Inventory Report</span>
                    </a>
                </li>

                <!-- Near Expiry Report -->
                <li class="nav-item">
                    <a href="<?= base_url('modules/reports/near_expiry'); ?>" class="nav-link <?php echo isActivePage('near_expiry.php', $current_page); ?>">
                        <span class="nav-icon">
                            <i data-lucide="clock" style="width: 20px; height: 20px; stroke-width: 1.5;"></i>
                        </span>
                        <span class="nav-text">Near Expiry</span>
                        <span class="nav-tooltip">Near Expiry</span>
                    </a>
                </li>

                <!-- Expired Products -->
                <li class="nav-item">
                    <a href="<?= base_url('modules/reports/expired'); ?>" class="nav-link <?php echo isActivePage('expired.php', $current_page); ?>">
                        <span class="nav-icon">
                            <i data-lucide="alert-circle" style="width: 20px; height: 20px; stroke-width: 1.5;"></i>
                        </span>
                        <span class="nav-text">Expired Products</span>
                        <span class="nav-tooltip">Expired Products</span>
                    </a>
                </li>

            </ul>
        </div>

        <?php if ($is_admin): ?>
        <!-- Administration Section -->
        <div class="nav-section">
            <div class="nav-section-title">Administration</div>
            <ul style="padding: 0;">
                <!-- Categories -->
                <li class="nav-item">
                    <a href="<?= base_url('modules/categories/index'); ?>" class="nav-link <?php echo ($current_dir === 'categories') ? 'active' : ''; ?>">
                        <span class="nav-icon">
                            <i data-lucide="tags" style="width: 20px; height: 20px; stroke-width: 1.5;"></i>
                        </span>
                        <span class="nav-text">Categories</span>
                        <span class="nav-tooltip">Categories</span>
                    </a>
                </li>

                <!-- Suppliers -->
                <li class="nav-item">
                    <a href="<?= base_url('modules/suppliers/index'); ?>" class="nav-link <?php echo ($current_dir === 'suppliers') ? 'active' : ''; ?>">
                        <span class="nav-icon">
                            <i data-lucide="truck" style="width: 20px; height: 20px; stroke-width: 1.5;"></i>
                        </span>
                        <span class="nav-text">Suppliers</span>
                        <span class="nav-tooltip">Suppliers</span>
                    </a>
                </li>

                <!-- User Management -->
                <li class="nav-item">
                    <a href="<?= base_url('users'); ?>" class="nav-link <?php echo isActivePage('users.php', $current_page); ?>">
                        <span class="nav-icon">
                            <i data-lucide="users" style="width: 20px; height: 20px; stroke-width: 1.5;"></i>
                        </span>
                        <span class="nav-text">User Management</span>
                        <span class="nav-tooltip">Users</span>
                    </a>
                </li>

                <!-- Roles & Permissions -->
                <li class="nav-item">
                    <a href="<?= base_url('roles'); ?>" class="nav-link <?php echo isActivePage('roles.php', $current_page); ?>">
                        <span class="nav-icon">
                            <i data-lucide="shield" style="width: 20px; height: 20px; stroke-width: 1.5;"></i>
                        </span>
                        <span class="nav-text">Roles & Permissions</span>
                        <span class="nav-tooltip">Roles</span>
                    </a>
                </li>

                <!-- Settings -->
                <li class="nav-item">
                    <a href="<?= base_url('settings'); ?>" class="nav-link <?php echo isActivePage('settings.php', $current_page); ?>">
                        <span class="nav-icon">
                            <i data-lucide="settings" style="width: 20px; height: 20px; stroke-width: 1.5;"></i>
                        </span>
                        <span class="nav-text">Settings</span>
                        <span class="nav-tooltip">Settings</span>
                    </a>
                </li>

                <!-- Cron Jobs -->
                <li class="nav-item">
                    <a href="<?= base_url('cron/index'); ?>" class="nav-link <?php echo ($current_dir === 'cron') ? 'active' : ''; ?>">
                        <span class="nav-icon">
                            <i data-lucide="timer" style="width: 20px; height: 20px; stroke-width: 1.5;"></i>
                        </span>
                        <span class="nav-text">Cron Jobs</span>
                        <span class="nav-tooltip">Cron Jobs</span>
                    </a>
                </li>
            </ul>
        </div>
        <?php endif; ?>
    </nav>
</aside>
