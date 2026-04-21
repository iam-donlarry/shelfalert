<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'classes/RoleManager.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$auth->requireAuth();

// Only admins can manage roles
// Only admins can manage roles
if (($_SESSION['role_name'] ?? '') !== 'Admin') {
    header('Location: ' . base_url('dashboard'));
    exit;
}

$roleManager = new RoleManager($db);

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $permissions = $_POST['permissions'] ?? [];
        $result = $roleManager->create([
            'role_name' => $_POST['role_name'] ?? '',
            'description' => $_POST['description'] ?? '',
            'permissions' => $permissions
        ]);
        
        if ($result['success']) {
            $message = $result['message'];
        } else {
            $error = $result['message'];
        }
    } elseif ($action === 'update') {
        $permissions = $_POST['permissions'] ?? [];
        $result = $roleManager->update($_POST['role_id'], [
            'role_name' => $_POST['role_name'] ?? '',
            'description' => $_POST['description'] ?? '',
            'permissions' => $permissions,
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ]);
        
        if ($result['success']) {
            $message = $result['message'];
        } else {
            $error = $result['message'];
        }
    } elseif ($action === 'delete') {
        $result = $roleManager->delete($_POST['role_id']);
        
        if ($result['success']) {
            $message = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}

$roles = $roleManager->getAll(true);
$availablePermissions = RoleManager::$availablePermissions;

$page_title = "Roles & Permissions";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Roles & Permissions - ShelfAlert</title>
    <link href="<?php echo asset_url('css/bootstrap.min.css'); ?>" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo asset_url('css/style.css'); ?>">
    <link rel="icon" href="<?php echo base_url('images/logo1.png'); ?>">
    <style>
        .permission-group {
            background: var(--hover-bg);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .permission-group-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }
        .permission-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.25rem 0;
        }
        .role-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            transition: all 0.2s;
        }
        .role-card:hover {
            border-color: var(--primary-color);
            box-shadow: var(--shadow-md);
        }
        .role-title { font-weight: 600; font-size: 1.1rem; }
        .role-desc { color: var(--text-secondary); font-size: 0.875rem; margin-top: 0.25rem; }
        .permission-badge {
            display: inline-block;
            background: var(--primary-light);
            color: var(--primary-color);
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            margin: 0.15rem;
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <?php include 'includes/header.php'; ?>
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <div class="page-header d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="page-title">Roles & Permissions</h1>
                    <p class="page-subtitle">Manage user roles and access permissions</p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoleModal">
                    <i data-lucide="shield-plus" style="width: 18px; height: 18px; margin-right: 6px;"></i>
                    Add Role
                </button>
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

            <!-- Roles List -->
            <div class="row">
                <?php if (empty($roles)): ?>
                <div class="col-12">
                    <div class="content-card text-center py-5">
                        <i data-lucide="shield-off" style="width: 48px; height: 48px; opacity: 0.5; margin-bottom: 1rem;"></i>
                        <p class="text-muted">No roles created yet. Click "Add Role" to create one.</p>
                    </div>
                </div>
                <?php else: ?>
                <?php foreach ($roles as $role): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="role-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <?php $is_default = in_array($role['role_name'], ['Admin', 'Staff']); ?>
                            <div>
                                <div class="role-title">
                                    <?php echo htmlspecialchars($role['role_name']); ?>
                                    <?php if ($is_default): ?>
                                    <span class="badge badge-secondary ms-1" style="font-size: 0.65rem;">Default</span>
                                    <?php endif; ?>
                                </div>
                                <div class="role-desc"><?php echo htmlspecialchars($role['description'] ?? 'No description'); ?></div>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                    <i data-lucide="more-vertical" style="width: 16px; height: 16px;"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <?php if ($is_default): ?>
                                    <li><a class="dropdown-item" href="#" onclick="viewRole(<?php echo htmlspecialchars(json_encode($role)); ?>)">
                                        <i data-lucide="eye" style="width: 14px; height: 14px; margin-right: 8px;"></i>View Permissions
                                    </a></li>
                                    <?php else: ?>
                                    <li><a class="dropdown-item" href="#" onclick="editRole(<?php echo htmlspecialchars(json_encode($role)); ?>)">
                                        <i data-lucide="pencil" style="width: 14px; height: 14px; margin-right: 8px;"></i>Edit
                                    </a></li>
                                    <?php endif; ?>
                                    <li><a class="dropdown-item" href="#" onclick="cloneRole(<?php echo htmlspecialchars(json_encode($role)); ?>)">
                                        <i data-lucide="copy" style="width: 14px; height: 14px; margin-right: 8px;"></i>Clone
                                    </a></li>
                                    <?php if ($role['user_count'] == 0 && !$is_default): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="#" onclick="deleteRole(<?php echo $role['role_id']; ?>, '<?php echo htmlspecialchars($role['role_name']); ?>')">
                                        <i data-lucide="trash-2" style="width: 14px; height: 14px; margin-right: 8px;"></i>Delete
                                    </a></li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <span class="badge badge-<?php echo $role['is_active'] ? 'success' : 'danger'; ?> me-1">
                                <?php echo $role['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                            <span class="badge badge-info"><?php echo $role['user_count']; ?> user(s)</span>
                        </div>
                        
                        <div class="mt-3">
                            <small class="text-muted">Permissions:</small>
                            <div class="mt-1">
                                <?php if (empty($role['permissions'])): ?>
                                <span class="text-muted small">No permissions assigned</span>
                                <?php else: ?>
                                <span class="permission-badge"><?php echo count($role['permissions']); ?> permission(s)</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>

        <?php include 'includes/footer.php'; ?>
    </div>

    <!-- Add Role Modal -->
    <div class="modal fade" id="addRoleModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="create">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Role</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Role Name <span class="text-danger">*</span></label>
                                <input type="text" name="role_name" id="add_role_name" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Description</label>
                                <input type="text" name="description" id="add_description" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Clone From</label>
                                <select id="clone_from" class="form-select" onchange="loadClonePermissions()">
                                    <option value="">-- Start Fresh --</option>
                                    <?php foreach ($roles as $r): ?>
                                    <option value="<?php echo htmlspecialchars(json_encode($r['permissions'])); ?>">
                                        <?php echo htmlspecialchars($r['role_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <label class="form-label">Permissions</label>
                        <div class="row">
                            <?php foreach ($availablePermissions as $group => $groupData): ?>
                            <div class="col-md-6">
                                <div class="permission-group">
                                    <div class="permission-group-title"><?php echo $groupData['label']; ?></div>
                                    <?php foreach ($groupData['permissions'] as $key => $label): ?>
                                    <div class="permission-item">
                                        <input type="checkbox" name="permissions[]" value="<?php echo $key; ?>" id="add_<?php echo $key; ?>" class="form-check-input">
                                        <label for="add_<?php echo $key; ?>" class="form-check-label small"><?php echo $label; ?></label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Role</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Role Modal -->
    <div class="modal fade" id="editRoleModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="role_id" id="edit_role_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Role</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Role Name <span class="text-danger">*</span></label>
                                <input type="text" name="role_name" id="edit_role_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Description</label>
                                <input type="text" name="description" id="edit_description" class="form-control">
                            </div>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input type="checkbox" name="is_active" id="edit_is_active" class="form-check-input" value="1">
                            <label class="form-check-label" for="edit_is_active">Active</label>
                        </div>
                        
                        <label class="form-label">Permissions</label>
                        <div class="row">
                            <?php foreach ($availablePermissions as $group => $groupData): ?>
                            <div class="col-md-6">
                                <div class="permission-group">
                                    <div class="permission-group-title"><?php echo $groupData['label']; ?></div>
                                    <?php foreach ($groupData['permissions'] as $key => $label): ?>
                                    <div class="permission-item">
                                        <input type="checkbox" name="permissions[]" value="<?php echo $key; ?>" id="edit_<?php echo $key; ?>" class="form-check-input edit-permission">
                                        <label for="edit_<?php echo $key; ?>" class="form-check-label small"><?php echo $label; ?></label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Form -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="role_id" id="delete_role_id">
    </form>

    <script src="<?php echo asset_url('js/jquery.min.js'); ?>"></script>
    <script src="<?php echo asset_url('js/bootstrap.bundle.min.js'); ?>"></script>
    <script src="<?php echo asset_url('js/lucide.min.js'); ?>"></script>
    <script src="<?php echo asset_url('js/main.js'); ?>"></script>
    <script>
        lucide.createIcons();
        
        // Default roles that cannot be edited
        const defaultRoles = ['Admin', 'Staff'];
        
        function viewRole(role) {
            // Use the edit modal but make it read-only
            document.getElementById('edit_role_id').value = role.role_id;
            document.getElementById('edit_role_name').value = role.role_name;
            document.getElementById('edit_role_name').readOnly = true;
            document.getElementById('edit_description').value = role.description || '';
            document.getElementById('edit_description').readOnly = true;
            document.getElementById('edit_is_active').checked = role.is_active == 1;
            document.getElementById('edit_is_active').disabled = true;
            
            // Set modal title
            document.querySelector('#editRoleModal .modal-title').textContent = 'View Role: ' + role.role_name;
            
            // Hide save button
            document.querySelector('#editRoleModal .btn-primary').style.display = 'none';
            
            // Uncheck and disable all permissions first
            document.querySelectorAll('.edit-permission').forEach(cb => {
                cb.checked = false;
                cb.disabled = true;
            });
            
            // Check permissions that the role has
            if (role.permissions && role.permissions.length > 0) {
                role.permissions.forEach(perm => {
                    const cb = document.getElementById('edit_' + perm);
                    if (cb) cb.checked = true;
                });
            }
            
            new bootstrap.Modal(document.getElementById('editRoleModal')).show();
            
            // Reset modal state when closed
            document.getElementById('editRoleModal').addEventListener('hidden.bs.modal', function () {
                document.getElementById('edit_role_name').readOnly = false;
                document.getElementById('edit_description').readOnly = false;
                document.getElementById('edit_is_active').disabled = false;
                document.querySelector('#editRoleModal .modal-title').textContent = 'Edit Role';
                document.querySelector('#editRoleModal .btn-primary').style.display = 'block';
                document.querySelectorAll('.edit-permission').forEach(cb => cb.disabled = false);
            }, { once: true });
        }
        
        function editRole(role) {
            // Check if it's a default role
            if (defaultRoles.includes(role.role_name)) {
                viewRole(role);
                return;
            }
            
            document.getElementById('edit_role_id').value = role.role_id;
            document.getElementById('edit_role_name').value = role.role_name;
            document.getElementById('edit_role_name').readOnly = false;
            document.getElementById('edit_description').value = role.description || '';
            document.getElementById('edit_description').readOnly = false;
            document.getElementById('edit_is_active').checked = role.is_active == 1;
            document.getElementById('edit_is_active').disabled = false;
            
            // Set modal title
            document.querySelector('#editRoleModal .modal-title').textContent = 'Edit Role';
            
            // Show save button
            document.querySelector('#editRoleModal .btn-primary').style.display = 'block';
            
            // Uncheck and enable all permissions
            document.querySelectorAll('.edit-permission').forEach(cb => {
                cb.checked = false;
                cb.disabled = false;
            });
            
            new bootstrap.Modal(document.getElementById('editRoleModal')).show();
        }
        
        function deleteRole(id, name) {
            if (confirm('Are you sure you want to delete the role "' + name + '"?')) {
                document.getElementById('delete_role_id').value = id;
                document.getElementById('deleteForm').submit();
            }
        }
        
        function cloneRole(role) {
            // Set name with 'Copy of' prefix
            document.getElementById('add_role_name').value = 'Copy of ' + role.role_name;
            document.getElementById('add_description').value = role.description || '';
            
            // Uncheck all first
            document.querySelectorAll('#addRoleModal .form-check-input[name="permissions[]"]').forEach(cb => cb.checked = false);
            
            // Check permissions from cloned role
            if (role.permissions && role.permissions.length > 0) {
                role.permissions.forEach(perm => {
                    const cb = document.getElementById('add_' + perm);
                    if (cb) cb.checked = true;
                });
            }
            
            new bootstrap.Modal(document.getElementById('addRoleModal')).show();
        }
        
        function loadClonePermissions() {
            const select = document.getElementById('clone_from');
            const permissionsJson = select.value;
            
            // Uncheck all first
            document.querySelectorAll('#addRoleModal .form-check-input[name="permissions[]"]').forEach(cb => cb.checked = false);
            
            if (permissionsJson) {
                try {
                    const permissions = JSON.parse(permissionsJson);
                    permissions.forEach(perm => {
                        const cb = document.getElementById('add_' + perm);
                        if (cb) cb.checked = true;
                    });
                } catch(e) {
                    console.error('Error parsing permissions', e);
                }
            }
        }
    </script>
</body>
</html>
