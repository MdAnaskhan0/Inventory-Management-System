<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Include database configuration
include '../db/config.php';

// Initialize variables
$product_id = $name = $description = $category = $price = $cost_price = $stock_quantity = $min_stock_level = $sku = $barcode = $supplier_id = $image_url = $weight = $dimensions = "";
$is_active = 1;
$error_msg = "";
$success_msg = "";

// Check if product ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: products.php");
    exit();
}

$product_id = intval($_GET['id']);

// Fetch product data
$stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: products.php");
    exit();
}

$product = $result->fetch_assoc();
$stmt->close();

// Populate form fields with existing data
$name = $product['name'];
$description = $product['description'];
$category = $product['category'];
$price = $product['price'];
$cost_price = $product['cost_price'];
$stock_quantity = $product['stock_quantity'];
$min_stock_level = $product['min_stock_level'];
$sku = $product['sku'];
$barcode = $product['barcode'];
$supplier_id = $product['supplier_id'];
$image_url = $product['image_url'];
$weight = $product['weight'];
$dimensions = $product['dimensions'];
$is_active = $product['is_active'];

// Get suppliers for dropdown
$suppliers = [];
$supplier_result = $conn->query("SELECT supplier_id, name FROM suppliers ORDER BY name");
if ($supplier_result && $supplier_result->num_rows > 0) {
    while ($row = $supplier_result->fetch_assoc()) {
        $suppliers[] = $row;
    }
}

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
        // Prepare and execute update statement
        $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, category = ?, price = ?, cost_price = ?, stock_quantity = ?, min_stock_level = ?, sku = ?, barcode = ?, supplier_id = ?, image_url = ?, weight = ?, dimensions = ?, is_active = ?, updated_at = NOW() WHERE product_id = ?");

        $stmt->bind_param("sssddiissisdsii", $name, $description, $category, $price, $cost_price, $stock_quantity, $min_stock_level, $sku, $barcode, $supplier_id, $image_url, $weight, $dimensions, $is_active, $product_id);

        if ($stmt->execute()) {
            $success_msg = "Product updated successfully!";

            // Refresh product data
            $stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();
            $stmt->close();
        } else {
            $error_msg = "Error updating product: " . $stmt->error;
        }

        // $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - Inventory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assect/css/style.css">
    <!-- <style>
        :root {
            --primary: #0d6efd;
            --secondary: #6c757d;
            --success: #198754;
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #0dcaf0;
            --light: #f8f9fa;
            --dark: #212529;
        }

        body {
            background-color: #f5f7fb;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .sidebar {
            background: var(--dark);
            color: white;
            height: 100vh;
            position: fixed;
            padding-top: 20px;
            transition: all 0.3s;
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 12px 20px;
            margin: 4px 0;
            border-radius: 5px;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .sidebar .nav-link i {
            margin-right: 10px;
            font-size: 18px;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s;
        }

        .dashboard-card {
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
        }

        .card-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }

        .navbar-custom {
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .welcome-text {
            font-weight: 500;
            color: var(--dark);
        }

        .stats-number {
            font-size: 1.8rem;
            font-weight: bold;
        }

        .form-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            padding: 25px;
            margin-bottom: 20px;
        }

        .form-label {
            font-weight: 500;
        }

        .required-field::after {
            content: " *";
            color: var(--danger);
        }

        .product-image {
            max-width: 200px;
            max-height: 200px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        @media (max-width: 768px) {
            .sidebar {
                margin-left: -250px;
            }

            .main-content {
                margin-left: 0;
            }

            .sidebar.active {
                margin-left: 0;
            }
        }

        @media screen and (min-width: 1200px) {
            .main-content {
                margin-left: 310px;
            }
        }
    </style> -->
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include '../assect/sidebar/sidebar.php' ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <!-- Top Navbar -->
                <?php include '../assect/navbar/navbar.php' ?>

                <!-- Page Content -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Edit Product</h2>
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
                    <form method="POST"
                        action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $product_id; ?>">
                        <div class="row">
                            <div class="col-md-8">
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
                                            <label for="stock_quantity" class="form-label required-field">Stock
                                                Quantity</label>
                                            <input type="number" class="form-control" id="stock_quantity"
                                                name="stock_quantity" value="<?= htmlspecialchars($stock_quantity) ?>"
                                                min="0" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="min_stock_level" class="form-label">Min Stock Level</label>
                                            <input type="number" class="form-control" id="min_stock_level"
                                                name="min_stock_level" value="<?= htmlspecialchars($min_stock_level) ?>"
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
                                                <input class="form-check-input" type="checkbox" id="is_active"
                                                    name="is_active" <?= $is_active ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="is_active">Active</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title">Product Image Preview</h5>
                                    </div>
                                    <div class="card-body text-center">
                                        <?php if (!empty($image_url)): ?>
                                            <img src="<?= htmlspecialchars($image_url) ?>" alt="Product Image"
                                                class="product-image img-fluid mb-3" id="imagePreview">
                                            <div class="form-text">Current product image</div>
                                        <?php else: ?>
                                            <div class="bg-light d-flex align-items-center justify-content-center"
                                                style="height: 200px; border-radius: 8px;">
                                                <div class="text-muted">No image available</div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="card mt-3">
                                    <div class="card-header">
                                        <h5 class="card-title">Product Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-2"><strong>Product ID:</strong> <?= $product_id ?></div>
                                        <div class="mb-2"><strong>Created:</strong>
                                            <?= date('M j, Y', strtotime($product['created_at'])) ?></div>
                                        <div class="mb-2"><strong>Last Updated:</strong>
                                            <?= date('M j, Y', strtotime($product['updated_at'])) ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                            <a href="products.php" class="btn btn-outline-secondary me-md-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Product</button>
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

        // Image preview update when URL changes
        document.getElementById('image_url').addEventListener('input', function () {
            const preview = document.getElementById('imagePreview');
            if (preview) {
                preview.src = this.value;
            }
        });
    </script>
</body>

</html>