<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Include database configuration
include '../db/config.php';

// Handle order deletion
if (isset($_GET['delete_id'])) {
    $order_id = $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM orders WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Order deleted successfully!";
    } else {
        $_SESSION['error'] = "Error deleting order!";
    }
    header("Location: orders.php");
    exit();
}

// Fetch all orders with customer information
$orders = [];
$result = $conn->query("
    SELECT o.*, first_name as customer_name, email as customer_email 
    FROM orders o 
    LEFT JOIN customers c ON o.customer_id = c.customer_id 
    ORDER BY o.order_date DESC
");
if ($result && $result->num_rows > 0) {
    $orders = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Inventory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assect/css/style.css">
    <style>
        .status-pending {
            color: #ffc107;
            font-weight: bold;
        }

        .status-processing {
            color: #0dcaf0;
            font-weight: bold;
        }

        .status-shipped {
            color: #fd7e14;
            font-weight: bold;
        }

        .status-delivered {
            color: #198754;
            font-weight: bold;
        }

        .status-cancelled {
            color: #dc3545;
            font-weight: bold;
        }

        .table-actions {
            white-space: nowrap;
        }
    </style>
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

                <!-- Orders Management -->
                <div class="card dashboard-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-cart-check me-2"></i>Manage Orders</h5>
                        <a href="add_order.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-1"></i> Add New Order
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?= $_SESSION['success'] ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php unset($_SESSION['success']); ?>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?= $_SESSION['error'] ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php unset($_SESSION['error']); ?>
                        <?php endif; ?>

                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Order Date</th>
                                        <th>Total Amount</th>
                                        <th>Status</th>
                                        <th>Payment Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($orders)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No orders found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($orders as $order): ?>
                                            <tr>
                                                <td>#<?= $order['order_id'] ?></td>
                                                <td>
                                                    <?= htmlspecialchars($order['customer_name'] ?? 'N/A') ?><br>
                                                    <small class="text-muted"><?= $order['customer_email'] ?? '' ?></small>
                                                </td>
                                                <td><?= date('M j, Y g:i A', strtotime($order['order_date'])) ?></td>
                                                <td>$<?= number_format($order['total_amount'], 2) ?></td>
                                                <td>
                                                    <span class="status-<?= $order['status'] ?>">
                                                        <?= ucfirst($order['status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?= ucfirst($order['payment_status']) ?>
                                                </td>
                                                <td class="table-actions">
                                                    <a href="view_order.php?id=<?= $order['order_id'] ?>"
                                                        class="btn btn-sm btn-info" title="View Order">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="edit_order.php?id=<?= $order['order_id'] ?>"
                                                        class="btn btn-sm btn-warning" title="Edit Order">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <a href="orders.php?delete_id=<?= $order['order_id'] ?>"
                                                        class="btn btn-sm btn-danger"
                                                        onclick="return confirm('Are you sure you want to delete this order?')"
                                                        title="Delete Order">
                                                        <i class="bi bi-trash"></i>
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
    <script>
        document.querySelector('.navbar-toggler').addEventListener('click', function () {
            document.querySelector('.sidebar').classList.toggle('active');
        });
    </script>
</body>

</html>