<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../classes/ProductManager.php';
require_once '../../classes/CategoryManager.php';
require_once '../../classes/SupplierManager.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$auth->requireAuth();

$productManager = new ProductManager($db);
$categoryManager = new CategoryManager($db);
$supplierManager = new SupplierManager($db);

// Get product ID
$product_id = $_GET['id'] ?? 0;
if (!$product_id) {
    header('Location: ' . base_url('modules/products/index'));
    exit;
}

$product = $productManager->getById($product_id);
if (!$product) {
    header('Location: ' . base_url('modules/products/index'));
    exit;
}

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'product_name' => $_POST['product_name'] ?? '',
        'barcode' => $_POST['barcode'] ?? '',
        'category_id' => $_POST['category_id'] ?? null,
        'supplier_id' => $_POST['supplier_id'] ?? null,
        'description' => $_POST['description'] ?? '',
        'unit_of_measure' => $_POST['unit_of_measure'] ?? 'piece',
        'unit_price' => $_POST['unit_price'] ?? 0,
        'cost_price' => $_POST['cost_price'] ?? 0,
        'quantity' => $_POST['quantity'] ?? 0,
        'reorder_level' => $_POST['reorder_level'] ?? 10,
        'storage_location' => $_POST['storage_location'] ?? '',
        'manufacture_date' => $_POST['manufacture_date'] ?? '',
        'expiry_date' => $_POST['expiry_date'] ?? '',
        'batch_number' => $_POST['batch_number'] ?? '',
        'status' => $_POST['status'] ?? 'active'
    ];
    
    // Validation
    if (empty($data['product_name'])) {
        $error = 'Product name is required';
    } elseif (empty($data['expiry_date'])) {
        $error = 'Expiry date is required';
    } else {
        $result = $productManager->update($product_id, $data);
        
        if ($result['success']) {
            $message = $result['message'];
            // Refresh product data
            $product = $productManager->getById($product_id);
        } else {
            $error = $result['message'];
        }
    }
}

$categories = $categoryManager->getForDropdown();
$suppliers = $supplierManager->getForDropdown();

$page_title = "Edit Product";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit <?php echo htmlspecialchars($product['product_name']); ?> - ShelfAlert</title>
    <link href="../../css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="icon" href="../../images/logo.png">
    <style>
        .section-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            margin-bottom: 1.5rem;
        }
        .section-header {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
        }
        .section-body { padding: 1.25rem; }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <?php include '../../includes/header.php'; ?>
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <?php include '../../includes/sidebar.php'; ?>

        <main class="main-content">
            <div class="page-header d-flex justify-content-between align-items-center mb-4">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-1">
                            <li class="breadcrumb-item"><a href="<?php echo base_url('modules/products/index'); ?>">Products</a></li>
                            <li class="breadcrumb-item"><a href="<?php echo base_url('modules/products/view?id=' . $product['product_id']); ?>"><?php echo htmlspecialchars($product['product_code']); ?></a></li>
                            <li class="breadcrumb-item active">Edit</li>
                        </ol>
                    </nav>
                    <h1 class="page-title">Edit Product</h1>
                </div>
                <a href="<?php echo base_url('modules/products/view?id=' . $product['product_id']); ?>" class="btn btn-outline-secondary">
                    <i data-lucide="x" style="width: 18px; height: 18px; margin-right: 6px;"></i>
                    Cancel
                </a>
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

            <form method="POST" id="editProductForm">
                <div class="row">
                    <div class="col-lg-8">
                        <!-- Basic Information -->
                        <div class="section-card">
                            <div class="section-header">
                                <i data-lucide="package" style="width: 20px; height: 20px;"></i>
                                Basic Information
                            </div>
                            <div class="section-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Product Code</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($product['product_code']); ?>" disabled>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Barcode</label>
                                        <input type="text" name="barcode" class="form-control" value="<?php echo htmlspecialchars($product['barcode'] ?? ''); ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Product Name <span class="text-danger">*</span></label>
                                        <input type="text" name="product_name" class="form-control" required
                                            value="<?php echo htmlspecialchars($product['product_name']); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Category</label>
                                        <select name="category_id" class="form-select">
                                            <option value="">-- Select Category --</option>
                                            <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo $cat['category_id']; ?>" <?php echo $product['category_id'] == $cat['category_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($cat['category_name']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Supplier</label>
                                        <select name="supplier_id" class="form-select">
                                            <option value="">-- Select Supplier --</option>
                                            <?php foreach ($suppliers as $sup): ?>
                                            <option value="<?php echo $sup['supplier_id']; ?>" <?php echo $product['supplier_id'] == $sup['supplier_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($sup['supplier_name']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Description</label>
                                        <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pricing & Stock -->
                        <div class="section-card">
                            <div class="section-header">
                                <i data-lucide="banknote" style="width: 20px; height: 20px;"></i>
                                Pricing & Stock
                            </div>
                            <div class="section-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Unit of Measure</label>
                                        <select name="unit_of_measure" class="form-select">
                                            <?php
                                            $units = ['piece', 'kg', 'gram', 'liter', 'ml', 'pack', 'box', 'carton', 'bottle', 'can'];
                                            foreach ($units as $unit):
                                            ?>
                                            <option value="<?php echo $unit; ?>" <?php echo $product['unit_of_measure'] == $unit ? 'selected' : ''; ?>>
                                                <?php echo ucfirst($unit); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Cost Price</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₦</span>
                                            <input type="number" name="cost_price" class="form-control" step="0.01" min="0"
                                                value="<?php echo number_format($product['cost_price'], 2, '.', ''); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Selling Price <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text">₦</span>
                                            <input type="number" name="unit_price" class="form-control" step="0.01" min="0" required
                                                value="<?php echo number_format($product['unit_price'], 2, '.', ''); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Current Quantity</label>
                                        <input type="number" name="quantity" class="form-control" min="0"
                                            value="<?php echo $product['quantity']; ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Reorder Level</label>
                                        <input type="number" name="reorder_level" class="form-control" min="0"
                                            value="<?php echo $product['reorder_level']; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Expiry Information -->
                        <div class="section-card">
                            <div class="section-header text-danger">
                                <i data-lucide="calendar-clock" style="width: 20px; height: 20px;"></i>
                                Expiry Information
                            </div>
                            <div class="section-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Manufacture Date</label>
                                        <input type="date" name="manufacture_date" class="form-control"
                                            value="<?php echo $product['manufacture_date'] ?? ''; ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Expiry Date <span class="text-danger">*</span></label>
                                        <input type="date" name="expiry_date" class="form-control" required
                                            value="<?php echo $product['expiry_date']; ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Batch Number</label>
                                        <input type="text" name="batch_number" class="form-control"
                                            value="<?php echo htmlspecialchars($product['batch_number'] ?? ''); ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Storage Location</label>
                                        <input type="text" name="storage_location" class="form-control" placeholder="e.g., Aisle A, Shelf 3"
                                            value="<?php echo htmlspecialchars($product['storage_location'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <!-- Status -->
                        <div class="section-card">
                            <div class="section-header">
                                <i data-lucide="toggle-right" style="width: 20px; height: 20px;"></i>
                                Status
                            </div>
                            <div class="section-body">
                                <label class="form-label">Product Status</label>
                                <select name="status" class="form-select">
                                    <option value="active" <?php echo $product['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $product['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    <option value="discontinued" <?php echo $product['status'] == 'discontinued' ? 'selected' : ''; ?>>Discontinued</option>
                                </select>
                                <small class="text-muted mt-1 d-block">Active products appear in reports and alerts</small>
                            </div>
                        </div>

                        <!-- Current Expiry Status -->
                        <div class="section-card">
                            <div class="section-header">
                                <i data-lucide="info" style="width: 20px; height: 20px;"></i>
                                Current Status
                            </div>
                            <div class="section-body text-center">
                                <?php
                                $statusClass = match($product['expiry_status']) {
                                    'expired' => 'danger',
                                    'critical' => 'warning', 
                                    'warning' => 'info',
                                    default => 'success'
                                };
                                ?>
                                <div class="badge badge-<?php echo $statusClass; ?> fs-6 px-3 py-2 mb-2">
                                    <?php echo ucfirst($product['expiry_status']); ?>
                                </div>
                                <p class="mb-0 text-muted small">
                                    <?php if ($product['days_until_expiry'] <= 0): ?>
                                    Expired <?php echo abs($product['days_until_expiry']); ?> day(s) ago
                                    <?php else: ?>
                                    <?php echo $product['days_until_expiry']; ?> days until expiry
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="section-card">
                            <div class="section-body">
                                <button type="submit" class="btn btn-primary w-100 mb-2">
                                    <i data-lucide="save" style="width: 18px; height: 18px; margin-right: 6px;"></i>
                                    Save Changes
                                </button>
                                <a href="<?php echo base_url('modules/products/view?id=' . $product['product_id']); ?>" class="btn btn-outline-secondary w-100">
                                    Cancel
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </main>

        <?php include '../../includes/footer.php'; ?>
    </div>

    <script src="../../js/jquery.min.js"></script>
    <script src="../../js/bootstrap.bundle.min.js"></script>
    <script src="../../js/lucide.min.js"></script>
    <script src="../../js/sidebar.js"></script>
    <script src="../../js/main.js"></script>
    <script>
        lucide.createIcons();
        
        // Form validation
        document.getElementById('editProductForm').addEventListener('submit', function(e) {
            const expiryDate = document.querySelector('[name="expiry_date"]').value;
            const manufactureDate = document.querySelector('[name="manufacture_date"]').value;
            
            if (manufactureDate && expiryDate && manufactureDate >= expiryDate) {
                e.preventDefault();
                alert('Manufacture date must be before expiry date');
            }
        });
    </script>
</body>
</html>
