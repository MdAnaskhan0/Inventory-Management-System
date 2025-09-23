<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

include '../db/config.php';

if (!isset($_GET['id'])) {
    header("Location: orders.php");
    exit();
}

$order_id = $_GET['id'];

// Fetch order details
$order_stmt = $conn->prepare("
    SELECT o.*, first_name as customer_name, email as customer_email, phone as customer_phone 
    FROM orders o 
    LEFT JOIN customers c ON o.customer_id = c.customer_id 
    WHERE o.order_id = ?
");
$order_stmt->bind_param("i", $order_id);
$order_stmt->execute();
$order = $order_stmt->get_result()->fetch_assoc();

if (!$order) {
    header("Location: orders.php");
    exit();
}

// Fetch order items
$items_stmt = $conn->prepare("
    SELECT oi.*, p.name, p.sku 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.product_id 
    WHERE oi.order_id = ?
");
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$order_items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Order - Inventory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assect/css/dashboardstyle.css">
    <style>
        .order-header {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-processing {
            background: #cff4fc;
            color: #055160;
        }

        .status-shipped {
            background: #fff3cd;
            color: #856404;
        }

        .status-delivered {
            background: #d1e7dd;
            color: #0f5132;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
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

                <!-- Order Details -->
                <div class="card dashboard-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-eye me-2"></i>Order Details - #<?= $order['order_id'] ?></h5>
                        <div>
                            <a href="orders.php" class="btn btn-secondary btn-sm">
                                <i class="bi bi-arrow-left me-1"></i> Back to Orders
                            </a>
                            <a href="edit_order.php?id=<?= $order['order_id'] ?>" class="btn btn-primary btn-sm">
                                <i class="bi bi-pencil me-1"></i> Edit Order
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Order Header -->
                        <div class="order-header">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Order Information</h6>
                                    <p class="mb-1"><strong>Order ID:</strong> #<?= $order['order_id'] ?></p>
                                    <p class="mb-1"><strong>Order Date:</strong>
                                        <?= date('F j, Y g:i A', strtotime($order['order_date'])) ?></p>
                                    <p class="mb-1"><strong>Status:</strong>
                                        <span class="status-badge status-<?= $order['status'] ?>">
                                            <?= ucfirst($order['status']) ?>
                                        </span>
                                 </p>
                                </div>
                                <div class="col-md-6">
                                    <h6>Customer Information</h6>
                                    <p class="mb-1"><strong>Name:</strong>
                                        <?= htmlspecialchars($order['customer_name'] ?? 'N/A') ?></p>
                                    <p class="mb-1"><strong>Email:</strong> <?= $order['customer_email'] ?? 'N/A' ?></p>
                                    <p class="mb-1"><strong>Phone:</strong> <?= $order['customer_phone'] ?? 'N/A' ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Order Items -->
                        <div class="table-responsive mb-4">
                            <table class="table table-bordered">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Product</th>
                                        <th>Code</th>
                                        <th>Quantity</th>
                                        <th>Unit Price</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($order_items as $item): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($item['name']) ?></td>
                                            <td><?= $item['sku'] ?></td>
                                            <td><?= $item['quantity'] ?></td>
                                            <td>$<?= number_format($item['unit_price'], 2) ?></td>
                                            <td>$<?= number_format($item['total_price'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="4" class="text-end"><strong>Subtotal:</strong></td>
                                        <td><strong>$<?= number_format($order['total_amount'] - $order['tax_amount'] - $order['shipping_amount'] + $order['discount_amount'], 2) ?></strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" class="text-end">Tax:</td>
                                        <td>$<?= number_format($order['tax_amount'], 2) ?></td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" class="text-end">Shipping:</td>
                                        <td>$<?= number_format($order['shipping_amount'], 2) ?></td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" class="text-end">Discount:</td>
                                        <td>-$<?= number_format($order['discount_amount'], 2) ?></td>
                                    </tr>
                                    <tr class="table-active">
                                        <td colspan="4" class="text-end"><strong>Grand Total:</strong></td>
                                        <td><strong>$<?= number_format($order['total_amount'], 2) ?></strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Payment and Shipping Information -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">Payment Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-1"><strong>Payment Method:</strong>
                                            <?= $order['payment_method'] ?? 'N/A' ?></p>
                                        <p class="mb-1"><strong>Payment Status:</strong>
                                            <span
                                                class="badge bg-<?= $order['payment_status'] === 'completed' ? 'success' : 'warning' ?>">
                                                <?= ucfirst($order['payment_status']) ?>
                                            </span>
                                        </p>
                                        <p class="mb-0"><strong>Total Paid:</strong>
                                            $<?= number_format($order['total_amount'], 2) ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">Shipping Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-0"><strong>Shipping Address:</strong></p>
                                        <p class="mb-0">
                                            <?= nl2br(htmlspecialchars($order['shipping_address'] ?? 'Not specified')) ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($order['notes'])): ?>
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h6 class="mb-0">Order Notes</h6>
                                </div>
                                <div class="card-body">
                                    <p class="mb-0"><?= nl2br(htmlspecialchars($order['notes'])) ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>