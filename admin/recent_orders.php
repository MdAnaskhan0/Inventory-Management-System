<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

include '../db/config.php';

// Fetch recent orders (last 7 days)
$recent_orders = [];
$result = $conn->query("
    SELECT o.*, first_name as customer_name 
    FROM orders o 
    LEFT JOIN customers c ON o.customer_id = c.customer_id 
    WHERE o.order_date >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
    ORDER BY o.order_date DESC 
    LIMIT 10
");
if ($result && $result->num_rows > 0) {
    $recent_orders = $result->fetch_all(MYSQLI_ASSOC);
}

// Get order statistics
$stats = [
    'today' => 0,
    'week' => 0,
    'month' => 0,
    'total' => 0
];

$result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE DATE(order_date) = CURDATE()");
if ($result)
    $stats['today'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE order_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
if ($result)
    $stats['week'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE order_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
if ($result)
    $stats['month'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM orders");
if ($result)
    $stats['total'] = $result->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recent Orders - Inventory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assect/css/dashboardstyle.css">
    <style>
        .stat-card {
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .recent-order {
            border-left: 4px solid #0d6efd;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar (same as orders.php) -->
            <?php include '../assect/sidebar/sidebar.php'; ?>


            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <!-- Top Navbar (same as orders.php) -->
                <?php include '../assect/navbar/navbar.php' ?>


                <!-- Recent Orders -->
                <div class="card dashboard-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Recent Orders</h5>
                        <a href="orders.php" class="btn btn-primary btn-sm">
                            <i class="bi bi-cart-check me-1"></i> View All Orders
                        </a>
                    </div>
                    <div class="card-body">
                        <!-- Statistics Cards -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card stat-card bg-primary text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h6 class="card-title">Today's Orders</h6>
                                                <h3><?= $stats['today'] ?></h3>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="bi bi-cart-plus fs-1 opacity-50"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card stat-card bg-success text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h6 class="card-title">This Week</h6>
                                                <h3><?= $stats['week'] ?></h3>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="bi bi-calendar-week fs-1 opacity-50"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card stat-card bg-warning text-dark">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h6 class="card-title">This Month</h6>
                                                <h3><?= $stats['month'] ?></h3>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="bi bi-calendar-month fs-1 opacity-50"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card stat-card bg-info text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h6 class="card-title">Total Orders</h6>
                                                <h3><?= $stats['total'] ?></h3>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="bi bi-cart-check fs-1 opacity-50"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Orders List -->
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Date & Time</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Payment</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recent_orders)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No recent orders found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recent_orders as $order): ?>
                                            <tr class="recent-order">
                                                <td>#<?= $order['order_id'] ?></td>
                                                <td><?= htmlspecialchars($order['customer_name'] ?? 'N/A') ?></td>
                                                <td><?= date('M j, Y g:i A', strtotime($order['order_date'])) ?></td>
                                                <td>$<?= number_format($order['total_amount'], 2) ?></td>
                                                <td>
                                                    <span class="badge bg-<?=
                                                        $order['status'] === 'delivered' ? 'success' :
                                                        ($order['status'] === 'processing' ? 'info' :
                                                            ($order['status'] === 'shipped' ? 'warning' :
                                                                ($order['status'] === 'cancelled' ? 'danger' : 'secondary')))
                                                        ?>">
                                                        <?= ucfirst($order['status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span
                                                        class="badge bg-<?= $order['payment_status'] === 'completed' ? 'success' : 'warning' ?>">
                                                        <?= ucfirst($order['payment_status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="view_order.php?id=<?= $order['order_id'] ?>"
                                                        class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-eye"></i> View
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>