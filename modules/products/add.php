<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../classes/ProductManager.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$auth->requireAuth();

$productManager = new ProductManager($db);
$error = '';
$success = '';

// Get categories
try {
    $stmt = $db->query("SELECT category_id, category_code, category_name FROM categories WHERE is_active = 1 ORDER BY category_name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categories = [];
}

// Get suppliers
try {
    $stmt = $db->query("SELECT supplier_id, supplier_code, supplier_name FROM suppliers WHERE is_active = 1 ORDER BY supplier_name");
    $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $suppliers = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    $required = ['product_name', 'expiry_date'];
    $missing = [];
    
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            $missing[] = str_replace('_', ' ', ucfirst($field));
        }
    }
    
    if (!empty($missing)) {
        $error = 'Please fill in required fields: ' . implode(', ', $missing);
    } else {
        // Generate product code if not provided
        $product_code = $_POST['product_code'] ?? '';
        if (empty($product_code)) {
            $product_code = $productManager->generateProductCode($_POST['category_id'] ?? null);
        }
        
        $data = [
            'product_code' => $product_code,
            'barcode' => $_POST['barcode'] ?? '',
            'product_name' => $_POST['product_name'],
            'category_id' => $_POST['category_id'] ?? null,
            'supplier_id' => $_POST['supplier_id'] ?? null,
            'description' => $_POST['description'] ?? '',
            'unit_of_measure' => $_POST['unit_of_measure'] ?? 'piece',
            'unit_price' => $_POST['unit_price'] ?? 0,
            'cost_price' => $_POST['cost_price'] ?? 0,
            'quantity' => $_POST['quantity'] ?? 0,
            'reorder_level' => $_POST['reorder_level'] ?? 10,
            'storage_location' => $_POST['storage_location'] ?? '',
            'manufacture_date' => $_POST['manufacture_date'] ?? null,
            'expiry_date' => $_POST['expiry_date'],
            'batch_number' => $_POST['batch_number'] ?? '',
            'status' => 'active',
            'created_by' => $_SESSION['user_id']
        ];
        
        $result = $productManager->create($data);
        
        if ($result['success']) {
            header('Location: index.php?success=Product added successfully');
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

$page_title = "Add Product";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - ShelfAlert | Ace Supermarket</title>
    <link href="<?php echo asset_url('css/bootstrap.min.css'); ?>" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo asset_url('css/style.css'); ?>">
    <link rel="icon" href="<?php echo base_url('images/logo.png'); ?>">
    <style>
        .form-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .form-card-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .required-indicator {
            color: #ef4444;
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
                    <h1 class="page-title">Add New Product</h1>
                    <p class="page-subtitle">Register a new product in the inventory</p>
                </div>
                <div>
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i data-lucide="arrow-left" style="width: 18px; height: 18px; margin-right: 6px;"></i>
                        Back to Products
                    </a>
                </div>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <form method="POST" action="" id="productForm">
                <div class="row">
                    <div class="col-lg-8">
                        <!-- Basic Information -->
                        <div class="form-card">
                            <h3 class="form-card-title">
                                <i data-lucide="package" style="width: 20px; height: 20px;"></i>
                                Basic Information
                            </h3>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Product Name <span class="required-indicator">*</span></label>
                                    <input type="text" name="product_name" class="form-control" required 
                                           value="<?php echo htmlspecialchars($_POST['product_name'] ?? ''); ?>"
                                           placeholder="Enter product name">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Product Code</label>
                                    <input type="text" name="product_code" class="form-control" 
                                           value="<?php echo htmlspecialchars($_POST['product_code'] ?? ''); ?>"
                                           placeholder="Auto-generated">
                                    <small class="text-muted">Leave blank to auto-generate</small>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Barcode</label>
                                    <input type="text" name="barcode" class="form-control" 
                                           value="<?php echo htmlspecialchars($_POST['barcode'] ?? ''); ?>"
                                           placeholder="Scan or enter">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Category</label>
                                    <select name="category_id" class="form-select" id="categorySelect">
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['category_id']; ?>" 
                                                data-code="<?php echo $cat['category_code']; ?>"
                                                <?php echo ($_POST['category_id'] ?? '') == $cat['category_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['category_name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Supplier</label>
                                    <select name="supplier_id" class="form-select">
                                        <option value="">Select Supplier</option>
                                        <?php foreach ($suppliers as $sup): ?>
                                        <option value="<?php echo $sup['supplier_id']; ?>" 
                                                <?php echo ($_POST['supplier_id'] ?? '') == $sup['supplier_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($sup['supplier_name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" class="form-control" rows="3" 
                                              placeholder="Product description (optional)"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Pricing & Stock -->
                        <div class="form-card">
                            <h3 class="form-card-title">
                                <i data-lucide="banknote" style="width: 20px; height: 20px;"></i>
                                Pricing & Stock
                            </h3>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Unit of Measure</label>
                                    <select name="unit_of_measure" class="form-select">
                                        <option value="piece" <?php echo ($_POST['unit_of_measure'] ?? '') === 'piece' ? 'selected' : ''; ?>>Piece</option>
                                        <option value="pack" <?php echo ($_POST['unit_of_measure'] ?? '') === 'pack' ? 'selected' : ''; ?>>Pack</option>
                                        <option value="carton" <?php echo ($_POST['unit_of_measure'] ?? '') === 'carton' ? 'selected' : ''; ?>>Carton</option>
                                        <option value="kg" <?php echo ($_POST['unit_of_measure'] ?? '') === 'kg' ? 'selected' : ''; ?>>Kilogram</option>
                                        <option value="liter" <?php echo ($_POST['unit_of_measure'] ?? '') === 'liter' ? 'selected' : ''; ?>>Liter</option>
                                        <option value="bottle" <?php echo ($_POST['unit_of_measure'] ?? '') === 'bottle' ? 'selected' : ''; ?>>Bottle</option>
                                        <option value="can" <?php echo ($_POST['unit_of_measure'] ?? '') === 'can' ? 'selected' : ''; ?>>Can</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Cost Price (₦)</label>
                                    <input type="number" name="cost_price" class="form-control" step="0.01" min="0"
                                           value="<?php echo htmlspecialchars($_POST['cost_price'] ?? '0'); ?>"
                                           placeholder="0.00">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Selling Price (₦)</label>
                                    <input type="number" name="unit_price" class="form-control" step="0.01" min="0"
                                           value="<?php echo htmlspecialchars($_POST['unit_price'] ?? '0'); ?>"
                                           placeholder="0.00">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Initial Quantity</label>
                                    <input type="number" name="quantity" class="form-control" min="0"
                                           value="<?php echo htmlspecialchars($_POST['quantity'] ?? '0'); ?>"
                                           placeholder="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Reorder Level</label>
                                    <input type="number" name="reorder_level" class="form-control" min="0"
                                           value="<?php echo htmlspecialchars($_POST['reorder_level'] ?? '10'); ?>"
                                           placeholder="10">
                                    <small class="text-muted">Alert when stock falls below this</small>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">Storage Location</label>
                                    <input type="text" name="storage_location" class="form-control" 
                                           value="<?php echo htmlspecialchars($_POST['storage_location'] ?? ''); ?>"
                                           placeholder="e.g., Aisle 5, Shelf B">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <!-- Expiry Information -->
                        <div class="form-card">
                            <h3 class="form-card-title">
                                <i data-lucide="calendar-clock" style="width: 20px; height: 20px; color: #ef4444;"></i>
                                Expiry Information
                            </h3>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Manufacture Date</label>
                                    <input type="date" name="manufacture_date" class="form-control" 
                                           value="<?php echo htmlspecialchars($_POST['manufacture_date'] ?? ''); ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Expiry Date <span class="required-indicator">*</span></label>
                                    <input type="date" name="expiry_date" class="form-control" required
                                           value="<?php echo htmlspecialchars($_POST['expiry_date'] ?? ''); ?>"
                                           id="expiryDate">
                                    <div id="expiryStatus" class="mt-2"></div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Initial Batch Number</label>
                                    <input type="text" name="batch_number" class="form-control" 
                                           value="<?php echo htmlspecialchars($_POST['batch_number'] ?? ''); ?>"
                                           placeholder="e.g., BN-001 or Initial">
                                    <small class="text-muted">Tracking individual batches helps monitor multiple expiries.</small>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="form-card">
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i data-lucide="save" style="width: 20px; height: 20px; margin-right: 8px;"></i>
                                    Save Product
                                </button>
                                <button type="reset" class="btn btn-outline-secondary">
                                    <i data-lucide="rotate-ccw" style="width: 18px; height: 18px; margin-right: 6px;"></i>
                                    Reset Form
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </main>

        <?php include '../../includes/footer.php'; ?>
    </div>

    <script src="<?php echo asset_url('js/jquery.min.js'); ?>"></script>
    <script src="<?php echo asset_url('js/bootstrap.bundle.min.js'); ?>"></script>
    <script src="<?php echo asset_url('js/lucide.min.js'); ?>"></script>
    <script src="<?php echo asset_url('js/sidebar.js'); ?>"></script>
    <script src="<?php echo asset_url('js/main.js'); ?>"></script>
    <script>
        lucide.createIcons();
        
        // Calculate expiry status on date change
        document.getElementById('expiryDate').addEventListener('change', function() {
            const expiryDate = new Date(this.value);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            const diffTime = expiryDate - today;
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            
            const statusEl = document.getElementById('expiryStatus');
            
            if (diffDays <= 0) {
                statusEl.innerHTML = '<span class="badge bg-danger">Already Expired!</span>';
            } else if (diffDays <= 7) {
                statusEl.innerHTML = '<span class="badge bg-warning text-dark">Critical: ' + diffDays + ' day(s) until expiry</span>';
            } else if (diffDays <= 30) {
                statusEl.innerHTML = '<span class="badge bg-info">' + diffDays + ' days until expiry</span>';
            } else {
                statusEl.innerHTML = '<span class="badge bg-success">' + diffDays + ' days until expiry</span>';
            }
        });
        
        // Trigger on page load if value exists
        if (document.getElementById('expiryDate').value) {
            document.getElementById('expiryDate').dispatchEvent(new Event('change'));
        }
    </script>
</body>
</html>
