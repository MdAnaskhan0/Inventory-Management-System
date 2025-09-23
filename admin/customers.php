<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

include '../db/config.php';

// Handle customer deletion
if (isset($_GET['delete_id'])) {
    $customer_id = $_GET['delete_id'];

    // Check if customer has orders
    $check_stmt = $conn->prepare("SELECT COUNT(*) as order_count FROM orders WHERE customer_id = ?");
    $check_stmt->bind_param("i", $customer_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result()->fetch_assoc();

    if ($result['order_count'] > 0) {
        $_SESSION['error'] = "Cannot delete customer. There are orders associated with this customer.";
    } else {
        $stmt = $conn->prepare("DELETE FROM customers WHERE customer_id = ?");
        $stmt->bind_param("i", $customer_id);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Customer deleted successfully!";
        } else {
            $_SESSION['error'] = "Error deleting customer!";
        }
    }
    header("Location: customers.php");
    exit();
}

// Fetch all customers
$customers = [];
$search = isset($_GET['search']) ? $_GET['search'] : '';

if ($search) {
    $stmt = $conn->prepare("SELECT * FROM customers WHERE first_name LIKE ? OR last_name LIKE ? OR email LIKE ? ORDER BY created_at DESC");
    $search_term = "%$search%";
    $stmt->bind_param("sss", $search_term, $search_term, $search_term);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT * FROM customers ORDER BY created_at DESC");
}

if ($result && $result->num_rows > 0) {
    $customers = $result->fetch_all(MYSQLI_ASSOC);
}

// Get customer statistics
$total_customers = count($customers);
$recent_customers = $conn->query("SELECT COUNT(*) as count FROM customers WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Customers - Inventory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assect/css/style.css">
    <style>
        .table-actions {
            white-space: nowrap;
        }

        .customer-card {
            transition: transform 0.2s;
        }

        .customer-card:hover {
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

                <!-- Customers Management -->
                <div class="card dashboard-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-people me-2"></i>Manage Customers</h5>
                        <a href="add_customer.php" class="btn btn-primary">
                            <i class="bi bi-person-plus me-1"></i> Add New Customer
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

                        <!-- Statistics Cards -->
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="card customer-card bg-primary text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h6 class="card-title">Total Customers</h6>
                                                <h3><?= $total_customers ?></h3>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="bi bi-people fs-1 opacity-50"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card customer-card bg-success text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h6 class="card-title">New This Week</h6>
                                                <h3><?= $recent_customers ?></h3>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="bi bi-person-plus fs-1 opacity-50"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card customer-card bg-info text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h6 class="card-title">Active Customers</h6>
                                                <h3>
                                                    <?= $conn->query("SELECT COUNT(DISTINCT customer_id) as count FROM orders")->fetch_assoc()['count'] ?>
                                                </h3>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="bi bi-person-check fs-1 opacity-50"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Search Bar -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <form method="GET" class="d-flex">
                                    <input type="text" name="search" class="form-control me-2"
                                        placeholder="Search customers..." value="<?= htmlspecialchars($search) ?>">
                                    <button type="submit" class="btn btn-outline-primary">Search</button>
                                    <?php if ($search): ?>
                                        <a href="customers.php" class="btn btn-outline-secondary ms-2">Clear</a>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>

                        <!-- Customers Table -->
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Address</th>
                                        <th>Joined Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($customers)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No customers found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($customers as $index => $customer): ?>
                                            <tr>
                                                <td><?= $index + 1 ?></td>
                                                <td>
                                                    <strong><?= htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']) ?></strong>
                                                </td>
                                                <td>
                                                    <?php if ($customer['email']): ?>
                                                        <a href="mailto:<?= $customer['email'] ?>"><?= $customer['email'] ?></a>
                                                    <?php else: ?>
                                                        <span class="text-muted">N/A</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($customer['phone']): ?>
                                                        <a href="tel:<?= $customer['phone'] ?>"><?= $customer['phone'] ?></a>
                                                    <?php else: ?>
                                                        <span class="text-muted">N/A</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($customer['address']): ?>
                                                        <span title="<?= htmlspecialchars($customer['address']) ?>">
                                                            <?= strlen($customer['address']) > 30 ? substr($customer['address'], 0, 30) . '...' : $customer['address'] ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">N/A</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= date('M j, Y', strtotime($customer['created_at'])) ?></td>
                                                <td class="table-actions">
                                                    <a href="view_customer.php?id=<?= $customer['customer_id'] ?>"
                                                        class="btn btn-sm btn-info" title="View Customer">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="edit_customer.php?id=<?= $customer['customer_id'] ?>"
                                                        class="btn btn-sm btn-warning" title="Edit Customer">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <a href="customers.php?delete_id=<?= $customer['customer_id'] ?>"
                                                        class="btn btn-sm btn-danger"
                                                        onclick="return confirm('Are you sure you want to delete this customer? This action cannot be undone.')"
                                                        title="Delete Customer">
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