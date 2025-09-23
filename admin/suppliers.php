<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Include database configuration
include '../db/config.php';

// Handle supplier deletion
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM suppliers WHERE supplier_id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        $success_msg = "Supplier deleted successfully!";
    } else {
        $error_msg = "Error deleting supplier: " . $conn->error;
    }
    $stmt->close();
}

// Fetch all suppliers
$suppliers = [];
$result = $conn->query("SELECT * FROM suppliers ORDER BY created_at DESC");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $suppliers[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Suppliers - Inventory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assect/css/style.css">
    <!-- <style>
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

        .action-buttons .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
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

        @media screen and (min-width: 1200px) {
            .main-content {
                margin-left: 310px;
            }
        }
    </style> -->
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

                <!-- Suppliers Management -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <h2>Suppliers Management</h2>
                            <a href="add_supplier.php" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Add New Supplier
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Alert Messages -->
                <?php if (isset($success_msg)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= $success_msg ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_msg)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= $error_msg ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Suppliers Table -->
                <div class="card dashboard-card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Contact Person</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Created At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($suppliers) > 0): ?>
                                        <?php foreach ($suppliers as $supplier): ?>
                                            <tr>
                                                <td><?= $supplier['supplier_id'] ?></td>
                                                <td><?= htmlspecialchars($supplier['name']) ?></td>
                                                <td><?= htmlspecialchars($supplier['contact_person']) ?></td>
                                                <td><?= htmlspecialchars($supplier['email']) ?></td>
                                                <td><?= htmlspecialchars($supplier['phone']) ?></td>
                                                <td><?= date('M j, Y', strtotime($supplier['created_at'])) ?></td>
                                                <td class="action-buttons">
                                                    <a href="view_supplier.php?id=<?= $supplier['supplier_id'] ?>"
                                                        class="btn btn-sm btn-info" title="View">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="edit_supplier.php?id=<?= $supplier['supplier_id'] ?>"
                                                        class="btn btn-sm btn-warning" title="Edit">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <a href="suppliers.php?delete_id=<?= $supplier['supplier_id'] ?>"
                                                        class="btn btn-sm btn-danger" title="Delete"
                                                        onclick="return confirm('Are you sure you want to delete this supplier?')">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No suppliers found.</td>
                                        </tr>
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
        // Simple toggle functionality for sidebar on mobile
        document.querySelector('.navbar-toggler').addEventListener('click', function () {
            document.querySelector('.sidebar').classList.toggle('active');
        });
    </script>
</body>

</html>