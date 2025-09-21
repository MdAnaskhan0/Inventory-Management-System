<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Include database configuration
include '../db/config.php';

$error_msg = '';
$success_msg = '';

// Get supplier ID from URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: suppliers.php");
    exit();
}

$supplier_id = $_GET['id'];

// Fetch supplier data
$supplier = null;
$stmt = $conn->prepare("SELECT * FROM suppliers WHERE supplier_id = ?");
$stmt->bind_param("i", $supplier_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: suppliers.php");
    exit();
}

$supplier = $result->fetch_assoc();
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $contact_person = trim($_POST['contact_person']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    // Validate inputs
    if (empty($name)) {
        $error_msg = "Supplier name is required.";
    } else {
        // Update supplier
        $update_stmt = $conn->prepare("UPDATE suppliers SET name = ?, contact_person = ?, email = ?, phone = ?, address = ? WHERE supplier_id = ?");
        $update_stmt->bind_param("sssssi", $name, $contact_person, $email, $phone, $address, $supplier_id);

        if ($update_stmt->execute()) {
            $success_msg = "Supplier updated successfully!";
            // Refresh supplier data
            $stmt = $conn->prepare("SELECT * FROM suppliers WHERE supplier_id = ?");
            $stmt->bind_param("i", $supplier_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $supplier = $result->fetch_assoc();
            $stmt->close();
        } else {
            $error_msg = "Error updating supplier: " . $conn->error;
        }

        $update_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Supplier - Inventory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        /* Same styles as suppliers.php */
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

        .navbar-custom {
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .welcome-text {
            font-weight: 500;
            color: var(--dark);
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
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar d-md-block">
                <div class="text-center mb-4">
                    <h4>Inventory System</h4>
                    <hr class="bg-light">
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="admindashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>

                    <!-- Product -->
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="collapse" href="#productsSubmenu" role="button"
                            aria-expanded="false">
                            <i class="bi bi-box-seam"></i> Products <i class="bi bi-chevron-down ms-auto"></i>
                        </a>
                        <div class="collapse" id="productsSubmenu">
                            <ul class="nav flex-column ms-3">
                                <li class="nav-item">
                                    <a class="nav-link" href="products.php">
                                        <i class="bi bi-list"></i> View Products
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link active" href="add_product.php">
                                        <i class="bi bi-plus-circle"></i> Add Product
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <!-- Users -->
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="collapse" href="#usersSubmenu" role="button"
                            aria-expanded="false">
                            <i class="bi bi-people"></i> Users <i class="bi bi-chevron-down ms-auto"></i>
                        </a>
                        <div class="collapse" id="usersSubmenu">
                            <ul class="nav flex-column ms-3">
                                <li class="nav-item">
                                    <a class="nav-link" href="users.php">
                                        <i class="bi bi-people-fill"></i> View Users
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link active" href="add_user.php">
                                        <i class="bi bi-person-fill-add"></i> Add Users
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <!-- Suppliers -->
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="collapse" href="#suppliersSubmenu" role="button"
                            aria-expanded="false">
                            <i class="bi bi-building"></i> Suppliers <i class="bi bi-chevron-down ms-auto"></i>
                        </a>
                        <div class="collapse" id="suppliersSubmenu">
                            <ul class="nav flex-column ms-3">
                                <li class="nav-item">
                                    <a class="nav-link" href="suppliers.php">
                                        <i class="bi bi-box-fill"></i> Suppliers
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link active" href="add_supplier.php">
                                        <i class="bi bi-plus-circle-fill"></i> Add Supplier
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>


                    <!-- Orders -->
                    <li class="nav-item">
                        <a class="nav-link" href="orders.php">
                            <i class="bi bi-cart-check"></i> Orders
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">
                            <i class="bi bi-bar-chart"></i> Reports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="settings.php">
                            <i class="bi bi-gear"></i> Settings
                        </a>
                    </li>
                    <li class="nav-item mt-4">
                        <a class="nav-link text-danger" href="../logout.php">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <!-- Top Navbar -->
                <nav class="navbar navbar-expand-lg navbar-custom rounded mb-4">
                    <div class="container-fluid">
                        <button class="navbar-toggler d-md-none" type="button">
                            <span class="navbar-toggler-icon"></span>
                        </button>
                        <div class="d-flex align-items-center">
                            <h5 class="welcome-text mb-0">Welcome Admin <?= $_SESSION['username'] ?> 👑</h5>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="dropdown">
                                <button class="btn btn-outline-primary dropdown-toggle" type="button" id="userDropdown"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-person-circle me-1"></i> My Account
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                    <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i>Profile</a>
                                    </li>
                                    <li><a class="dropdown-item" href="#"><i class="bi bi-gear me-2"></i>Settings</a>
                                    </li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li><a class="dropdown-item text-danger" href="../logout.php"><i
                                                class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </nav>

                <!-- Edit Supplier Form -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <h2>Edit Supplier</h2>
                            <a href="suppliers.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Back to Suppliers
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Alert Messages -->
                <?php if (!empty($error_msg)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= $error_msg ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success_msg)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= $success_msg ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card dashboard-card">
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Supplier Name *</label>
                                    <input type="text" class="form-control" id="name" name="name"
                                        value="<?= htmlspecialchars($supplier['name']) ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="contact_person" class="form-label">Contact Person</label>
                                    <input type="text" class="form-control" id="contact_person" name="contact_person"
                                        value="<?= htmlspecialchars($supplier['contact_person']) ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email"
                                        value="<?= htmlspecialchars($supplier['email']) ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Phone</label>
                                    <input type="tel" class="form-control" id="phone" name="phone"
                                        value="<?= htmlspecialchars($supplier['phone']) ?>">
                                </div>
                                <div class="col-12 mb-3">
                                    <label for="address" class="form-label">Address</label>
                                    <textarea class="form-control" id="address" name="address"
                                        rows="3"><?= htmlspecialchars($supplier['address']) ?></textarea>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Created At</label>
                                    <p class="form-control-plaintext">
                                        <?= date('M j, Y g:i A', strtotime($supplier['created_at'])) ?>
                                    </p>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Update Supplier</button>
                            <a href="suppliers.php" class="btn btn-secondary">Cancel</a>
                        </form>
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