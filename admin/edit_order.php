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
$error = '';
$success = '';

// Fetch order details
$order_stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = ?");
$order_stmt->bind_param("i", $order_id);
$order_stmt->execute();
$order = $order_stmt->get_result()->fetch_assoc();

if (!$order) {
    header("Location: orders.php");
    exit();
}

// Fetch order items
$items_stmt = $conn->prepare("
    SELECT oi.*, p.name, p.price, p.stock_quantity 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.product_id 
    WHERE oi.order_id = ?
");
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$order_items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch customers
$customers = $conn->query("SELECT customer_id, first_name, email FROM customers ORDER BY first_name")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'];
    $payment_status = $_POST['payment_status'];
    $shipping_address = $_POST['shipping_address'];
    $notes = $_POST['notes'];

    try {
        $stmt = $conn->prepare("UPDATE orders SET status = ?, payment_status = ?, shipping_address = ?, notes = ?, updated_at = NOW() WHERE order_id = ?");
        $stmt->bind_param("ssssi", $status, $payment_status, $shipping_address, $notes, $order_id);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Order updated successfully!";
            header("Location: orders.php");
            exit();
        } else {
            $error = "Error updating order!";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Order - Inventory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assect/css/dashboardstyle.css">
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


                <!-- Edit Order Form -->
                <div class="card dashboard-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-pencil me-2"></i>Edit Order - #<?= $order['order_id'] ?></h5>
                        <a href="view_order.php?id=<?= $order['order_id'] ?>" class="btn btn-info btn-sm">
                            <i class="bi bi-eye me-1"></i> View Order
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0">Order Information</h6>
                                        </div>
                                        <div class="card-body">
                                            <p><strong>Order ID:</strong> #<?= $order['order_id'] ?></p>
                                            <p><strong>Order Date:</strong>
                                                <?= date('F j, Y g:i A', strtotime($order['order_date'])) ?></p>
                                            <p><strong>Customer:</strong>
                                                <?php
                                                $customer_name = 'N/A';
                                                foreach ($customers as $customer) {
                                                    if ($customer['customer_id'] == $order['customer_id']) {
                                                        $customer_name = $customer['first_name'] . ' (' . $customer['email'] . ')';
                                                        break;
                                                    }
                                                }
                                                echo $customer_name;
                                                ?>
                                            </p>
                                            <p><strong>Total Amount:</strong>
                                                $<?= number_format($order['total_amount'], 2) ?></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0">Order Status</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label for="status" class="form-label">Order Status</label>
                                                <select class="form-select" id="status" name="status" required>
                                                    <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                    <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
                                                    <option value="shipped" <?= $order['status'] === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                                    <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                                    <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label for="payment_status" class="form-label">Payment Status</label>
                                                <select class="form-select" id="payment_status" name="payment_status"
                                                    required>
                                                    <option value="pending" <?= $order['payment_status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                    <option value="completed" <?= $order['payment_status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                                    <option value="failed" <?= $order['payment_status'] === 'failed' ? 'selected' : '' ?>>Failed</option>
                                                    <option value="refunded" <?= $order['payment_status'] === 'refunded' ? 'selected' : '' ?>>Refunded</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Order Items (Read-only) -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">Order Items</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Product</th>
                                                    <th>Quantity</th>
                                                    <th>Unit Price</th>
                                                    <th>Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($order_items as $item): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($item['name']) ?></td>
                                                        <td><?= $item['quantity'] ?></td>
                                                        <td>$<?= number_format($item['unit_price'], 2) ?></td>
                                                        <td>$<?= number_format($item['total_price'], 2) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="shipping_address" class="form-label">Shipping Address</label>
                                <textarea class="form-control" id="shipping_address" name="shipping_address"
                                    rows="3"><?= htmlspecialchars($order['shipping_address'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes"
                                    rows="2"><?= htmlspecialchars($order['notes'] ?? '') ?></textarea>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">Update Order</button>
                                <a href="orders.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>