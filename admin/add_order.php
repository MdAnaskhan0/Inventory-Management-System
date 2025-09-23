<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

include '../db/config.php';

$customers = [];
$products = [];
$error = '';
$success = '';

// Fetch customers and products
$customers_result = $conn->query("SELECT customer_id, first_name, email FROM customers ORDER BY first_name");
if ($customers_result) {
    $customers = $customers_result->fetch_all(MYSQLI_ASSOC);
}

$products_result = $conn->query("SELECT product_id, name, price, stock_quantity FROM products WHERE stock_quantity > 0 ORDER BY name");
if ($products_result) {
    $products = $products_result->fetch_all(MYSQLI_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = $_POST['customer_id'];
    $status = $_POST['status'];
    $payment_method = $_POST['payment_method'];
    $payment_status = $_POST['payment_status'];
    $shipping_address = $_POST['shipping_address'];
    $notes = $_POST['notes'];

    // Calculate totals from order items
    $total_amount = 0;
    $tax_amount = $_POST['tax_amount'] ?? 0;
    $shipping_amount = $_POST['shipping_amount'] ?? 0;
    $discount_amount = $_POST['discount_amount'] ?? 0;

    $order_items = [];
    if (isset($_POST['product_id'])) {
        foreach ($_POST['product_id'] as $index => $product_id) {
            $quantity = $_POST['quantity'][$index];
            $unit_price = $_POST['unit_price'][$index];
            $item_total = $quantity * $unit_price;
            $total_amount += $item_total;

            $order_items[] = [
                'product_id' => $product_id,
                'quantity' => $quantity,
                'unit_price' => $unit_price,
                'total_price' => $item_total
            ];
        }
    }

    $total_amount += $tax_amount + $shipping_amount - $discount_amount;

    try {
        $conn->begin_transaction();

        // Insert order
        $stmt = $conn->prepare("INSERT INTO orders (customer_id, status, total_amount, tax_amount, shipping_amount, discount_amount, payment_method, payment_status, shipping_address, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isddddssss", $customer_id, $status, $total_amount, $tax_amount, $shipping_amount, $discount_amount, $payment_method, $payment_status, $shipping_address, $notes);
        $stmt->execute();
        $order_id = $conn->insert_id;

        // Insert order items and update product stock
        foreach ($order_items as $item) {
            $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiidd", $order_id, $item['product_id'], $item['quantity'], $item['unit_price'], $item['total_price']);
            $stmt->execute();

            // Update product stock
            $update_stmt = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ?");
            $update_stmt->bind_param("ii", $item['quantity'], $item['product_id']);
            $update_stmt->execute();
        }

        $conn->commit();
        $_SESSION['success'] = "Order created successfully!";
        header("Location: orders.php");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error creating order: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Order - Inventory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assect/css/dashboardstyle.css">
    <style>
        .product-row {
            margin-bottom: 10px;
        }

        .remove-product {
            color: #dc3545;
            cursor: pointer;
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

                <!-- Add Order Form -->
                <div class="card dashboard-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Add New Order</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>

                        <form method="POST" id="orderForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="customer_id" class="form-label">Customer *</label>
                                        <select class="form-select" id="customer_id" name="customer_id" required>
                                            <option value="">Select Customer</option>
                                            <?php foreach ($customers as $customer): ?>
                                                <option value="<?= $customer['customer_id'] ?>">
                                                    <?= htmlspecialchars($customer['first_name']) ?>
                                                    (<?= $customer['email'] ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Order Status</label>
                                        <select class="form-select" id="status" name="status">
                                            <option value="pending">Pending</option>
                                            <option value="processing">Processing</option>
                                            <option value="shipped">Shipped</option>
                                            <option value="delivered">Delivered</option>
                                            <option value="cancelled">Cancelled</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="payment_method" class="form-label">Payment Method</label>
                                        <select class="form-select" id="payment_method" name="payment_method">
                                            <option value="Credit Card">Credit Card</option>
                                            <option value="PayPal">PayPal</option>
                                            <option value="Bank Transfer">Bank Transfer</option>
                                            <option value="Cash">Cash</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="payment_status" class="form-label">Payment Status</label>
                                        <select class="form-select" id="payment_status" name="payment_status">
                                            <option value="pending">Pending</option>
                                            <option value="completed">Completed</option>
                                            <option value="failed">Failed</option>
                                            <option value="refunded">Refunded</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Order Items -->
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6>Order Items</h6>
                                    <button type="button" class="btn btn-sm btn-success" id="addProduct">
                                        <i class="bi bi-plus"></i> Add Product
                                    </button>
                                </div>
                                <div id="productItems">
                                    <div class="product-row row align-items-center">
                                        <div class="col-md-5">
                                            <select class="form-select product-select" name="product_id[]" required>
                                                <option value="">Select Product</option>
                                                <?php foreach ($products as $product): ?>
                                                    <option value="<?= $product['product_id'] ?>"
                                                        data-price="<?= $product['price'] ?>"
                                                        data-stock="<?= $product['stock_quantity'] ?>">
                                                        <?= htmlspecialchars($product['name']) ?> -
                                                        $<?= $product['price'] ?> (Stock: <?= $product['stock_quantity'] ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <input type="number" class="form-control quantity" name="quantity[]" min="1"
                                                value="1" required>
                                        </div>
                                        <div class="col-md-2">
                                            <input type="number" class="form-control unit-price" name="unit_price[]"
                                                step="0.01" min="0" required readonly>
                                        </div>
                                        <div class="col-md-2">
                                            <span class="item-total">$0.00</span>
                                        </div>
                                        <div class="col-md-1">
                                            <span class="remove-product text-danger" style="cursor: pointer;">
                                                <i class="bi bi-trash"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="tax_amount" class="form-label">Tax Amount</label>
                                        <input type="number" class="form-control" id="tax_amount" name="tax_amount"
                                            step="0.01" min="0" value="0">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="shipping_amount" class="form-label">Shipping Amount</label>
                                        <input type="number" class="form-control" id="shipping_amount"
                                            name="shipping_amount" step="0.01" min="0" value="0">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="discount_amount" class="form-label">Discount Amount</label>
                                        <input type="number" class="form-control" id="discount_amount"
                                            name="discount_amount" step="0.01" min="0" value="0">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Total Amount</label>
                                        <div class="form-control-plaintext fw-bold" id="totalAmount">$0.00</div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="shipping_address" class="form-label">Shipping Address</label>
                                <textarea class="form-control" id="shipping_address" name="shipping_address"
                                    rows="3"></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">Create Order</button>
                                <a href="orders.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add product row
        document.getElementById('addProduct').addEventListener('click', function () {
            const productItems = document.getElementById('productItems');
            const newRow = productItems.firstElementChild.cloneNode(true);

            // Clear values
            newRow.querySelector('.product-select').selectedIndex = 0;
            newRow.querySelector('.quantity').value = 1;
            newRow.querySelector('.unit-price').value = '';
            newRow.querySelector('.item-total').textContent = '$0.00';

            productItems.appendChild(newRow);
            attachEventListeners(newRow);
            calculateTotal();
        });

        // Attach event listeners to a row
        function attachEventListeners(row) {
            const productSelect = row.querySelector('.product-select');
            const quantityInput = row.querySelector('.quantity');
            const unitPriceInput = row.querySelector('.unit-price');
            const removeBtn = row.querySelector('.remove-product');

            productSelect.addEventListener('change', function () {
                const selectedOption = this.options[this.selectedIndex];
                const price = selectedOption.getAttribute('data-price');
                const stock = selectedOption.getAttribute('data-stock');

                if (price) {
                    unitPriceInput.value = price;
                    calculateItemTotal(row);
                    calculateTotal();
                }

                if (stock) {
                    quantityInput.max = stock;
                }
            });

            quantityInput.addEventListener('input', function () {
                calculateItemTotal(row);
                calculateTotal();
            });

            removeBtn.addEventListener('click', function () {
                if (document.querySelectorAll('.product-row').length > 1) {
                    row.remove();
                    calculateTotal();
                }
            });
        }

        // Calculate item total
        function calculateItemTotal(row) {
            const quantity = parseFloat(row.querySelector('.quantity').value) || 0;
            const unitPrice = parseFloat(row.querySelector('.unit-price').value) || 0;
            const total = quantity * unitPrice;
            row.querySelector('.item-total').textContent = '$' + total.toFixed(2);
            return total;
        }

        // Calculate grand total
        function calculateTotal() {
            let subtotal = 0;
            document.querySelectorAll('.product-row').forEach(row => {
                subtotal += calculateItemTotal(row);
            });

            const tax = parseFloat(document.getElementById('tax_amount').value) || 0;
            const shipping = parseFloat(document.getElementById('shipping_amount').value) || 0;
            const discount = parseFloat(document.getElementById('discount_amount').value) || 0;

            const total = subtotal + tax + shipping - discount;
            document.getElementById('totalAmount').textContent = '$' + total.toFixed(2);
        }

        // Attach event listeners to existing rows
        document.querySelectorAll('.product-row').forEach(row => {
            attachEventListeners(row);
        });

        // Attach event listeners to tax, shipping, discount inputs
        ['tax_amount', 'shipping_amount', 'discount_amount'].forEach(id => {
            document.getElementById(id).addEventListener('input', calculateTotal);
        });

        // Initial calculation
        calculateTotal();
    </script>
</body>

</html>