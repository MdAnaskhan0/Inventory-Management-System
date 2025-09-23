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
$error = '';
$success = '';

// Fetch customer details
$customer_stmt = $conn->prepare("SELECT * FROM customers WHERE customer_id = ?");
$customer_stmt->bind_param("i", $customer_id);
$customer_stmt->execute();
$customer = $customer_stmt->get_result()->fetch_assoc();

if (!$customer) {
    header("Location: customers.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    // Validate required fields
    if (empty($first_name) || empty($last_name)) {
        $error = "First name and last name are required!";
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format!";
    } else {
        try {
            // Check if email already exists (excluding current customer)
            if (!empty($email)) {
                $check_stmt = $conn->prepare("SELECT customer_id FROM customers WHERE email = ? AND customer_id != ?");
                $check_stmt->bind_param("si", $email, $customer_id);
                $check_stmt->execute();
                if ($check_stmt->get_result()->num_rows > 0) {
                    $error = "Email already exists!";
                }
            }

            if (empty($error)) {
                $stmt = $conn->prepare("UPDATE customers SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ? WHERE customer_id = ?");
                $stmt->bind_param("sssssi", $first_name, $last_name, $email, $phone, $address, $customer_id);

                if ($stmt->execute()) {
                    $_SESSION['success'] = "Customer updated successfully!";
                    header("Location: customers.php");
                    exit();
                } else {
                    $error = "Error updating customer!";
                }
            }
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Customer - Inventory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assect/css/style.css">
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

                <!-- Edit Customer Form -->
                <div class="card dashboard-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-pencil me-2"></i>Edit Customer -
                            <?= htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']) ?></h5>
                        <a href="view_customer.php?id=<?= $customer['customer_id'] ?>" class="btn btn-info btn-sm">
                            <i class="bi bi-eye me-1"></i> View Customer
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
                                            <h6 class="mb-0">Customer Information</h6>
                                        </div>
                                        <div class="card-body">
                                            <p><strong>Customer ID:</strong> #<?= $customer['customer_id'] ?></p>
                                            <p><strong>Joined:</strong>
                                                <?= date('F j, Y', strtotime($customer['created_at'])) ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="first_name" class="form-label">First Name *</label>
                                        <input type="text" class="form-control" id="first_name" name="first_name"
                                            value="<?= htmlspecialchars($customer['first_name']) ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="last_name" class="form-label">Last Name *</label>
                                        <input type="text" class="form-control" id="last_name" name="last_name"
                                            value="<?= htmlspecialchars($customer['last_name']) ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email Address</label>
                                        <input type="email" class="form-control" id="email" name="email"
                                            value="<?= htmlspecialchars($customer['email'] ?? '') ?>">
                                        <div class="form-text">Optional, but must be unique if provided</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone" name="phone"
                                            value="<?= htmlspecialchars($customer['phone'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address"
                                    rows="3"><?= htmlspecialchars($customer['address'] ?? '') ?></textarea>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle me-1"></i> Update Customer
                                </button>
                                <a href="customers.php" class="btn btn-secondary">Cancel</a>
                                <a href="customers.php?delete_id=<?= $customer['customer_id'] ?>"
                                    class="btn btn-danger ms-auto"
                                    onclick="return confirm('Are you sure you want to delete this customer? This action cannot be undone.')">
                                    <i class="bi bi-trash me-1"></i> Delete Customer
                                </a>
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