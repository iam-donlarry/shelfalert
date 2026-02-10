<?php
/**
 * ShelfAlert - Header Component
 */
?>
<header class="header">
    <button class="sidebar-toggle" id="sidebarToggle">
        <i data-lucide="menu" style="width: 20px; height: 20px;"></i>
    </button>
    
    <a href="<?php echo base_url('dashboard.php'); ?>" class="logo">
        <span class="logo-icon">
            <img src="<?php echo base_url('images/logo.png'); ?>" alt="ShelfAlert Logo" style="width: 18px; height: 18px;">
        </span>
        <span>ShelfAlert</span>
    </a>
    
    <button class="collapse-toggle" id="collapseToggle" title="Collapse sidebar">
        <i data-lucide="panel-left-close" style="width: 18px; height: 18px;"></i>
    </button>
    
    <div class="header-actions">
        <!-- Notifications -->
        <div class="dropdown">
            <button class="icon-btn" data-bs-toggle="dropdown" aria-expanded="false">
                <i data-lucide="bell" style="width: 20px; height: 20px;"></i>
                <?php
                $active_alerts = 0;
                try {
                    if (isset($db)) {
                        $stmt = $db->query("SELECT COUNT(*) FROM alerts WHERE status = 'active'");
                        $active_alerts = $stmt->fetchColumn();
                        if ($active_alerts > 0):
                ?>
                <span class="notification-badge"><?php echo $active_alerts > 99 ? '99+' : $active_alerts; ?></span>
                <?php 
                        endif;
                    }
                } catch (Exception $e) {}
                ?>
            </button>
            <div class="dropdown-menu dropdown-menu-end" style="width: 320px;">
                <div class="dropdown-header">
                    <span class="dropdown-header-title">Expiry Alerts</span>
                </div>
                <?php
                try {
                    if (isset($db)) {
                        $stmt = $db->query("SELECT a.*, p.product_name, p.product_code 
                            FROM alerts a 
                            JOIN products p ON a.product_id = p.product_id 
                            WHERE a.status = 'active' 
                            ORDER BY FIELD(a.alert_type, 'expired', 'critical', 'warning'), a.created_at DESC 
                            LIMIT 5");
                        $nav_alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        if (empty($nav_alerts)):
                ?>
                <div class="dropdown-item text-center text-muted py-3">
                    <p class="mb-0">No active alerts</p>
                </div>
                <?php 
                        else:
                            foreach ($nav_alerts as $alert):
                ?>
                <a href="<?php echo base_url('modules/products/view.php?id=' . $alert['product_id']); ?>" class="dropdown-item">
                    <span class="badge badge-<?php echo $alert['alert_type'] === 'expired' ? 'danger' : ($alert['alert_type'] === 'critical' ? 'warning' : 'info'); ?> me-2">
                        <?php echo ucfirst($alert['alert_type']); ?>
                    </span>
                    <?php echo htmlspecialchars($alert['product_name']); ?>
                </a>
                <?php 
                            endforeach;
                        endif;
                    }
                } catch (Exception $e) {}
                ?>
                <div class="dropdown-divider"></div>
                <a href="<?php echo base_url('modules/alerts/index.php'); ?>" class="dropdown-item text-center">
                    View All Alerts
                </a>
            </div>
        </div>
        
        <!-- User Management -->
        <?php if (($_SESSION['role_name'] ?? '') === 'Admin'): ?>
        <div class="profile-dropdown dropdown">
            <button class="profile-btn" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="profile-avatar">
                    <?php echo strtoupper(substr($_SESSION['full_name'] ?? 'U', 0, 1)); ?>
                </span>
                <span class="profile-info">
                    <span class="profile-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?></span>
                    <span class="profile-role"><?php echo htmlspecialchars($_SESSION['role_name'] ?? 'Staff'); ?></span>
                </span>
                <i data-lucide="chevron-down" style="width: 16px; height: 16px; color: var(--text-secondary);"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="<?php echo base_url('profile.php'); ?>">
                    <i data-lucide="user" style="width: 16px; height: 16px; margin-right: 8px;"></i> My Profile
                </a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="<?php echo base_url('logout.php'); ?>">
                    <i data-lucide="log-out" style="width: 16px; height: 16px; margin-right: 8px;"></i> Logout
                </a></li>
            </ul>
        </div>
        <?php else: ?>
        <a href="<?php echo base_url('logout.php'); ?>" class="btn btn-outline-danger btn-sm d-flex align-items-center gap-2">
            <i data-lucide="log-out" style="width: 16px; height: 16px;"></i>
            <span>Logout</span>
        </a>
        <?php endif; ?>
    </div>
</header>