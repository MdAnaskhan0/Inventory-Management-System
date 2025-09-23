<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Include database configuration
include '../db/config.php';

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

// Format dates
$created_date = date('F j, Y, g:i a', strtotime($product['created_at']));
$updated_date = date('F j, Y, g:i a', strtotime($product['updated_at']));

// Get supplier name if available
$supplier_name = "Not specified";
if (!empty($product['supplier_id'])) {
    $stmt = $conn->prepare("SELECT name FROM suppliers WHERE supplier_id = ?");
    $stmt->bind_param("i", $product['supplier_id']);
    $stmt->execute();
    $supplier_result = $stmt->get_result();
    if ($supplier_result->num_rows > 0) {
        $supplier = $supplier_result->fetch_assoc();
        $supplier_name = htmlspecialchars($supplier['name']);
    }
    $stmt->close();
}

// Calculate profit margin if cost price is available
$profit_margin = "N/A";
if (!empty($product['cost_price']) && $product['cost_price'] > 0) {
    $profit = $product['price'] - $product['cost_price'];
    $margin = ($profit / $product['price']) * 100;
    $profit_margin = number_format($margin, 2) . '% ($' . number_format($profit, 2) . ')';
}

// Determine stock status
$stock_status = "";
$stock_class = "";
if ($product['stock_quantity'] == 0) {
    $stock_status = "Out of Stock";
    $stock_class = "danger";
} elseif ($product['stock_quantity'] <= $product['min_stock_level']) {
    $stock_status = "Low Stock";
    $stock_class = "warning";
} else {
    $stock_status = "In Stock";
    $stock_class = "success";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Product - Inventory Management System</title>
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

        .info-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            padding: 25px;
            margin-bottom: 20px;
        }

        .product-image {
            max-width: 100%;
            max-height: 300px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .detail-label {
            font-weight: 600;
            color: var(--secondary);
        }

        .status-badge {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
        }

        .action-buttons .btn {
            margin-right: 0.5rem;
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

            .action-buttons .btn {
                margin-bottom: 0.5rem;
                margin-right: 0;
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
            <?php include '../assect/sidebar/sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <!-- Top Navbar -->
                <?php include '../assect/navbar/navbar.php' ?>

                <!-- Page Content -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Product Details</h2>
                    <a href="products.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Products
                    </a>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex action-buttons mb-4">
                    <a href="edit_product.php?id=<?= $product_id ?>" class="btn btn-warning">
                        <i class="bi bi-pencil"></i> Edit Product
                    </a>
                    <a href="products.php?delete_id=<?= $product_id ?>" class="btn btn-danger"
                        onclick="return confirm('Are you sure you want to delete this product?')">
                        <i class="bi bi-trash"></i> Delete Product
                    </a>
                </div>

                <div class="row">
                    <!-- Left Column - Product Image and Basic Info -->
                    <div class="col-md-4">
                        <div class="info-card">
                            <div class="text-center mb-4">
                                <?php if (!empty($product['image_url'])): ?>
                                    <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="Product Image"
                                        class="product-image img-fluid">
                                <?php else: ?>
                                    <div class="bg-light d-flex align-items-center justify-content-center"
                                        style="height: 200px; border-radius: 8px;">
                                        <div class="text-muted">No image available</div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <h3 class="mb-3"><?= htmlspecialchars($product['name']) ?></h3>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="detail-label">Status:</span>
                                <span
                                    class="badge bg-<?= $product['is_active'] ? 'success' : 'secondary' ?> status-badge">
                                    <?= $product['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="detail-label">Stock Status:</span>
                                <span class="badge bg-<?= $stock_class ?> status-badge">
                                    <?= $stock_status ?>
                                </span>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="detail-label">Price:</span>
                                <span class="fw-bold">$<?= number_format($product['price'], 2) ?></span>
                            </div>

                            <?php if (!empty($product['cost_price'])): ?>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="detail-label">Cost Price:</span>
                                    <span>$<?= number_format($product['cost_price'], 2) ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if ($profit_margin !== "N/A"): ?>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="detail-label">Profit Margin:</span>
                                    <span class="fw-bold text-success"><?= $profit_margin ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Right Column - Product Details -->
                    <div class="col-md-8">
                        <div class="info-card">
                            <h4 class="mb-4">Product Information</h4>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <p class="detail-label">Product ID</p>
                                    <p><?= $product_id ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="detail-label">SKU</p>
                                    <p><?= !empty($product['sku']) ? htmlspecialchars($product['sku']) : 'N/A' ?></p>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <p class="detail-label">Barcode</p>
                                    <p><?= !empty($product['barcode']) ? htmlspecialchars($product['barcode']) : 'N/A' ?>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <p class="detail-label">Category</p>
                                    <p><?= !empty($product['category']) ? htmlspecialchars($product['category']) : 'N/A' ?>
                                    </p>
                                </div>
                            </div>

                            <div class="mb-3">
                                <p class="detail-label">Description</p>
                                <p><?= !empty($product['description']) ? nl2br(htmlspecialchars($product['description'])) : 'No description available' ?>
                                </p>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <p class="detail-label">Stock Quantity</p>
                                    <p><?= $product['stock_quantity'] ?></p>
                                </div>
                                <div class="col-md-4">
                                    <p class="detail-label">Minimum Stock Level</p>
                                    <p><?= $product['min_stock_level'] ?></p>
                                </div>
                                <div class="col-md-4">
                                    <p class="detail-label">Supplier</p>
                                    <p><?= $supplier_name ?></p>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <?php if (!empty($product['weight'])): ?>
                                    <div class="col-md-4">
                                        <p class="detail-label">Weight</p>
                                        <p><?= $product['weight'] ?> kg</p>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($product['dimensions'])): ?>
                                    <div class="col-md-4">
                                        <p class="detail-label">Dimensions</p>
                                        <p><?= htmlspecialchars($product['dimensions']) ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <p class="detail-label">Date Created</p>
                                    <p><?= $created_date ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="detail-label">Last Updated</p>
                                    <p><?= $updated_date ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
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
    </script>
</body>

</html>