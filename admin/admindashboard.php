<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Include database configuration
include '../db/config.php';

// Get stats for dashboard
$users_count = 0;
$products_count = 0;
$low_stock_count = 0;
$recent_orders = 0;

// Count users
$result = $conn->query("SELECT COUNT(*) as count FROM users");
if ($result) {
    $users_count = $result->fetch_assoc()['count'];
}

// Count products
$result = $conn->query("SELECT COUNT(*) as count FROM products");
if ($result) {
    $products_count = $result->fetch_assoc()['count'];
}

// Count low stock products (assuming less than 10 items is low stock)
$result = $conn->query("SELECT COUNT(*) as count FROM products WHERE stock_quantity < 10");
if ($result) {
    $low_stock_count = $result->fetch_assoc()['count'];
}

// Count recent orders (last 7 days)
$result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE order_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
if ($result) {
    $recent_orders = $result->fetch_assoc()['count'];
}
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Inventory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assect/css/dashboardstyle.css">
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

                <!-- Dashboard Stats -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card dashboard-card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">TOTAL USERS</h6>
                                        <h2 class="stats-number"><?= $users_count ?></h2>
                                    </div>
                                    <i class="bi bi-people card-icon"></i>
                                </div>
                                <a href="users.php" class="text-white small stretched-link">View Details</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card dashboard-card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">TOTAL PRODUCTS</h6>
                                        <h2 class="stats-number"><?= $products_count ?></h2>
                                    </div>
                                    <i class="bi bi-box-seam card-icon"></i>
                                </div>
                                <a href="products.php" class="text-white small stretched-link">View Details</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card dashboard-card bg-warning text-dark">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">LOW STOCK</h6>
                                        <h2 class="stats-number"><?= $low_stock_count ?></h2>
                                    </div>
                                    <i class="bi bi-exclamation-triangle card-icon"></i>
                                </div>
                                <a href="#" class="text-dark small stretched-link">View Details</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card dashboard-card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">RECENT ORDERS</h6>
                                        <h2 class="stats-number"><?= $recent_orders ?></h2>
                                    </div>
                                    <i class="bi bi-cart-check card-icon"></i>
                                </div>
                                <a href="orders.php" class="text-white small stretched-link">View Details</a>
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