<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Include database configuration
include '../db/config.php';

// Initialize variables
$name = $description = $category = $price = $cost_price = $stock_quantity = $min_stock_level = $sku = $barcode = $supplier_id = $image_url = $weight = $dimensions = "";
$is_active = 1;
$error_msg = "";
$success_msg = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $price = trim($_POST['price']);
    $cost_price = trim($_POST['cost_price']);
    $stock_quantity = trim($_POST['stock_quantity']);
    $min_stock_level = trim($_POST['min_stock_level']);
    $sku = trim($_POST['sku']);
    $barcode = trim($_POST['barcode']);
    $supplier_id = !empty($_POST['supplier_id']) ? $_POST['supplier_id'] : NULL;
    $image_url = trim($_POST['image_url']);
    $weight = !empty($_POST['weight']) ? $_POST['weight'] : NULL;
    $dimensions = trim($_POST['dimensions']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Validate required fields
    if (empty($name) || empty($price) || empty($stock_quantity)) {
        $error_msg = "Name, price, and stock quantity are required fields.";
    } else {
        // Prepare and execute insert statement
        $stmt = $conn->prepare("INSERT INTO products (name, description, category, price, cost_price, stock_quantity, min_stock_level, sku, barcode, supplier_id, image_url, weight, dimensions, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param("sssddiissisdsi", $name, $description, $category, $price, $cost_price, $stock_quantity, $min_stock_level, $sku, $barcode, $supplier_id, $image_url, $weight, $dimensions, $is_active);

        if ($stmt->execute()) {
            $success_msg = "Product added successfully!";
            // Clear form fields
            $name = $description = $category = $price = $cost_price = $stock_quantity = $min_stock_level = $sku = $barcode = $supplier_id = $image_url = $weight = $dimensions = "";
            $is_active = 1;
        } else {
            $error_msg = "Error adding product: " . $stmt->error;
        }

        $stmt->close();
    }
}

// Get suppliers for dropdown
$suppliers = [];
$supplier_result = $conn->query("SELECT supplier_id, name FROM suppliers ORDER BY name");
if ($supplier_result && $supplier_result->num_rows > 0) {
    while ($row = $supplier_result->fetch_assoc()) {
        $suppliers[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - Inventory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assect/css/add_product.css">
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar d-md-block">
                <div class="text-center mb-4">
                    <h4>Inventory System</h4>
                    <hr class="bg-light">
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="admindashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <!-- Product -->
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="collapse" href="#productsSubmenu" role="button"
                            aria-expanded="false">
                            <i class="bi bi-box-seam"></i> Products <i class="bi bi-chevron-down ms-auto"></i>
                        </a>
                        <div class="collapse" id="productsSubmenu">
                            <ul class="nav flex-column ms-3">
                                <li class="nav-item">
                                    <a class="nav-link" href="products.php">
                                        <i class="bi bi-list"></i> View Products
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link active" href="add_product.php">
                                        <i class="bi bi-plus-circle"></i> Add Product
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <!-- Users -->
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="collapse" href="#usersSubmenu" role="button"
                            aria-expanded="false">
                            <i class="bi bi-people"></i> Users <i class="bi bi-chevron-down ms-auto"></i>
                        </a>
                        <div class="collapse" id="usersSubmenu">
                            <ul class="nav flex-column ms-3">
                                <li class="nav-item">
                                    <a class="nav-link" href="users.php">
                                        <i class="bi bi-people-fill"></i> View Users
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link active" href="add_user.php">
                                        <i class="bi bi-person-fill-add"></i> Add Users
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <!-- Suppliers -->
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="collapse" href="#suppliersSubmenu" role="button"
                            aria-expanded="false">
                            <i class="bi bi-building"></i> Suppliers <i class="bi bi-chevron-down ms-auto"></i>
                        </a>
                        <div class="collapse" id="suppliersSubmenu">
                            <ul class="nav flex-column ms-3">
                                <li class="nav-item">
                                    <a class="nav-link" href="suppliers.php">
                                        <i class="bi bi-box-fill"></i> Suppliers
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link active" href="add_supplier.php">
                                        <i class="bi bi-plus-circle-fill"></i> Add Supplier
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <!-- Orders -->
                    <li class="nav-item">
                        <a class="nav-link" href="orders.php">
                            <i class="bi bi-cart-check"></i> Orders
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">
                            <i class="bi bi-bar-chart"></i> Reports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="settings.php">
                            <i class="bi bi-gear"></i> Settings
                        </a>
                    </li>
                    <li class="nav-item mt-4">
                        <a class="nav-link text-danger" href="../logout.php">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <!-- Top Navbar -->
                <nav class="navbar navbar-expand-lg navbar-custom rounded mb-4">
                    <div class="container-fluid">
                        <button class="navbar-toggler d-md-none" type="button">
                            <span class="navbar-toggler-icon"></span>
                        </button>
                        <div class="d-flex align-items-center">
                            <h5 class="welcome-text mb-0">Welcome Admin <?= $_SESSION['username'] ?> 👑</h5>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="dropdown">
                                <button class="btn btn-outline-primary dropdown-toggle" type="button" id="userDropdown"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-person-circle me-1"></i> My Account
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                    <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i>Profile</a>
                                    </li>
                                    <li><a class="dropdown-item" href="#"><i class="bi bi-gear me-2"></i>Settings</a>
                                    </li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li><a class="dropdown-item text-danger" href="../logout.php"><i
                                                class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </nav>

                <!-- Page Content -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Add New Product</h2>
                    <a href="products.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Products
                    </a>
                </div>

                <!-- Success/Error Messages -->
                <?php if (!empty($success_msg)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= $success_msg ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error_msg)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= $error_msg ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Product Form -->
                <div class="form-container">
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label required-field">Product Name</label>
                                    <input type="text" class="form-control" id="name" name="name"
                                        value="<?= htmlspecialchars($name) ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="category" class="form-label">Category</label>
                                    <input type="text" class="form-control" id="category" name="category"
                                        value="<?= htmlspecialchars($category) ?>">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description"
                                rows="3"><?= htmlspecialchars($description) ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="price" class="form-label required-field">Price ($)</label>
                                    <input type="number" class="form-control" id="price" name="price"
                                        value="<?= htmlspecialchars($price) ?>" step="0.01" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="cost_price" class="form-label">Cost Price ($)</label>
                                    <input type="number" class="form-control" id="cost_price" name="cost_price"
                                        value="<?= htmlspecialchars($cost_price) ?>" step="0.01" min="0">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="stock_quantity" class="form-label required-field">Stock Quantity</label>
                                    <input type="number" class="form-control" id="stock_quantity" name="stock_quantity"
                                        value="<?= htmlspecialchars($stock_quantity) ?>" min="0" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="min_stock_level" class="form-label">Min Stock Level</label>
                                    <input type="number" class="form-control" id="min_stock_level"
                                        name="min_stock_level" value="<?= htmlspecialchars($min_stock_level ?: '10') ?>"
                                        min="0">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="sku" class="form-label">SKU</label>
                                    <input type="text" class="form-control" id="sku" name="sku"
                                        value="<?= htmlspecialchars($sku) ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="barcode" class="form-label">Barcode</label>
                                    <input type="text" class="form-control" id="barcode" name="barcode"
                                        value="<?= htmlspecialchars($barcode) ?>">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="supplier_id" class="form-label">Supplier</label>
                                    <select class="form-select" id="supplier_id" name="supplier_id">
                                        <option value="">Select Supplier</option>
                                        <?php foreach ($suppliers as $supplier): ?>
                                            <option value="<?= $supplier['supplier_id'] ?>"
                                                <?= ($supplier_id == $supplier['supplier_id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($supplier['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="image_url" class="form-label">Image URL</label>
                                    <input type="url" class="form-control" id="image_url" name="image_url"
                                        value="<?= htmlspecialchars($image_url) ?>">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="weight" class="form-label">Weight (kg)</label>
                                    <input type="number" class="form-control" id="weight" name="weight"
                                        value="<?= htmlspecialchars($weight) ?>" step="0.01" min="0">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="dimensions" class="form-label">Dimensions (W×H×D)</label>
                                    <input type="text" class="form-control" id="dimensions" name="dimensions"
                                        value="<?= htmlspecialchars($dimensions) ?>" placeholder="e.g., 10×5×2">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active"
                                            <?= $is_active ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="is_active">Active</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="reset" class="btn btn-outline-secondary me-md-2">Reset</button>
                            <button type="submit" class="btn btn-primary">Add Product</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simple toggle functionality for sidebar on mobile
        document.querySelector('.navbar-toggler').addEventListener('click', function () {
            document.querySelector('.sidebar').classList.toggle('active');
        });

        // Basic form validation
        document.querySelector('form').addEventListener('submit', function (e) {
            let price = document.getElementById('price');
            let stock = document.getElementById('stock_quantity');
            let valid = true;

            if (price.value && parseFloat(price.value) <= 0) {
                alert('Price must be greater than 0');
                price.focus();
                valid = false;
            }

            if (stock.value && parseInt(stock.value) < 0) {
                alert('Stock quantity cannot be negative');
                stock.focus();
                valid = false;
            }

            if (!valid) {
                e.preventDefault();
            }
        });
    </script>
</body>

</html>