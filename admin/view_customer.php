<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

include '../db/config.php';

if (!isset($_GET['id'])) {
    header("Location: customers.php");
    exit();
}

$customer_id = $_GET['id'];

// Fetch customer details
$customer_stmt = $conn->prepare("SELECT * FROM customers WHERE customer_id = ?");
$customer_stmt->bind_param("i", $customer_id);
$customer_stmt->execute();
$customer = $customer_stmt->get_result()->fetch_assoc();

if (!$customer) {
    header("Location: customers.php");
    exit();
}

// Fetch customer's orders
$orders_stmt = $conn->prepare("
    SELECT o.*, COUNT(oi.order_item_id) as item_count 
    FROM orders o 
    LEFT JOIN order_items oi ON o.order_id = oi.order_id 
    WHERE o.customer_id = ? 
    GROUP BY o.order_id 
    ORDER BY o.order_date DESC
");
$orders_stmt->bind_param("i", $customer_id);
$orders_stmt->execute();
$orders = $orders_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get order statistics for this customer
$order_stats = $conn->query("
    SELECT 
        COUNT(*) as total_orders,
        SUM(total_amount) as total_spent,
        AVG(total_amount) as avg_order_value
    FROM orders 
    WHERE customer_id = $customer_id
")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Customer - Inventory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assect/css/style.css">
    <style>
        .customer-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .stat-card {
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
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
                <?php include '../assect/navbar/navbar.php'; ?>

                <!-- Customer Details -->
                <div class="card dashboard-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-person me-2"></i>Customer Details</h5>
                        <div>
                            <a href="customers.php" class="btn btn-secondary btn-sm">
                                <i class="bi bi-arrow-left me-1"></i> Back to Customers
                            </a>
                            <a href="edit_customer.php?id=<?= $customer['customer_id'] ?>"
                                class="btn btn-primary btn-sm">
                                <i class="bi bi-pencil me-1"></i> Edit Customer
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Customer Header -->
                        <div class="customer-header">
                            <div class="row">
                                <div class="col-md-8">
                                    <h2><?= htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']) ?>
                                    </h2>
                                    <p class="mb-1">
                                        <i class="bi bi-person-circle me-2"></i>Customer ID:
                                        #<?= $customer['customer_id'] ?>
                                    </p>
                                    <p class="mb-1">
                                        <i class="bi bi-calendar me-2"></i>Member since:
                                        <?= date('F j, Y', strtotime($customer['created_at'])) ?>
                                    </p>
                                </div>
                                <div class="col-md-4 text-end">
                                    <div class="btn-group-vertical">
                                        <?php if ($customer['email']): ?>
                                            <a href="mailto:<?= $customer['email'] ?>" class="btn btn-light btn-sm mb-2">
                                                <i class="bi bi-envelope me-1"></i> Email
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($customer['phone']): ?>
                                            <a href="tel:<?= $customer['phone'] ?>" class="btn btn-light btn-sm">
                                                <i class="bi bi-telephone me-1"></i> Call
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Statistics -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card stat-card bg-primary text-white">
                                    <div class="card-body text-center">
                                        <h6>Total Orders</h6>
                                        <h3><?= $order_stats['total_orders'] ?? 0 ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card stat-card bg-success text-white">
                                    <div class="card-body text-center">
                                        <h6>Total Spent</h6>
                                        <h3>$<?= number_format($order_stats['total_spent'] ?? 0, 2) ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card stat-card bg-info text-white">
                                    <div class="card-body text-center">
                                        <h6>Avg. Order Value</h6>
                                        <h3>$<?= number_format($order_stats['avg_order_value'] ?? 0, 2) ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card stat-card bg-warning text-dark">
                                    <div class="card-body text-center">
                                        <h6>Customer Since</h6>
                                        <h6><?= date('M Y', strtotime($customer['created_at'])) ?></h6>
                                        <small><?= round((time() - strtotime($customer['created_at'])) / (60 * 60 * 24)) ?>
                                            days</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Contact Information -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0"><i class="bi bi-telephone me-2"></i>Contact Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-2">
                                            <strong>Email:</strong>
                                            <?php if ($customer['email']): ?>
                                                <a href="mailto:<?= $customer['email'] ?>"><?= $customer['email'] ?></a>
                                            <?php else: ?>
                                                <span class="text-muted">Not provided</span>
                                            <?php endif; ?>
                                        </p>
                                        <p class="mb-2">
                                            <strong>Phone:</strong>
                                            <?php if ($customer['phone']): ?>
                                                <a href="tel:<?= $customer['phone'] ?>"><?= $customer['phone'] ?></a>
                                            <?php else: ?>
                                                <span class="text-muted">Not provided</span>
                                            <?php endif; ?>
                                        </p>
                                        <p class="mb-0">
                                            <strong>Address:</strong><br>
                                            <?php if ($customer['address']): ?>
                                                <?= nl2br(htmlspecialchars($customer['address'])) ?>
                                            <?php else: ?>
                                                <span class="text-muted">Not provided</span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Additional Information
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-2"><strong>Customer ID:</strong> #<?= $customer['customer_id'] ?>
                                        </p>
                                        <p class="mb-2"><strong>Registration Date:</strong>
                                            <?= date('F j, Y g:i A', strtotime($customer['created_at'])) ?></p>
                                        <p class="mb-0"><strong>Full Name:</strong>
                                            <?= htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']) ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Order History -->
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="bi bi-cart me-2"></i>Order History</h6>
                            </div>
                            <div class="card-body">
                                <?php if (empty($orders)): ?>
                                    <p class="text-muted">No orders found for this customer.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Order ID</th>
                                                    <th>Date</th>
                                                    <th>Items</th>
                                                    <th>Total Amount</th>
                                                    <th>Status</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($orders as $order): ?>
                                                    <tr>
                                                        <td>#<?= $order['order_id'] ?></td>
                                                        <td><?= date('M j, Y', strtotime($order['order_date'])) ?></td>
                                                        <td><?= $order['item_count'] ?> items</td>
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
                                                            <a href="view_order.php?id=<?= $order['order_id'] ?>"
                                                                class="btn btn-sm btn-outline-primary">
                                                                View
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>