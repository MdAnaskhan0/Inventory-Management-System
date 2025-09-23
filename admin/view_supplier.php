<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Include database configuration
include '../db/config.php';

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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Supplier - Inventory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assect/css/style.css">
    <!-- <style>
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

        .supplier-detail {
            margin-bottom: 1rem;
        }

        .supplier-detail label {
            font-weight: 500;
            color: var(--secondary);
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

                <!-- View Supplier Details -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <h2>Supplier Details</h2>
                            <a href="suppliers.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Back to Suppliers
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card dashboard-card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="supplier-detail">
                                    <label>ID:</label>
                                    <p><?= $supplier['supplier_id'] ?></p>
                                </div>
                                <div class="supplier-detail">
                                    <label>Name:</label>
                                    <p><?= htmlspecialchars($supplier['name']) ?></p>
                                </div>
                                <div class="supplier-detail">
                                    <label>Contact Person:</label>
                                    <p><?= htmlspecialchars($supplier['contact_person']) ?></p>
                                </div>
                                <div class="supplier-detail">
                                    <label>Email:</label>
                                    <p><?= htmlspecialchars($supplier['email']) ?></p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="supplier-detail">
                                    <label>Phone:</label>
                                    <p><?= htmlspecialchars($supplier['phone']) ?></p>
                                </div>
                                <div class="supplier-detail">
                                    <label>Address:</label>
                                    <p><?= nl2br(htmlspecialchars($supplier['address'])) ?></p>
                                </div>
                                <div class="supplier-detail">
                                    <label>Created At:</label>
                                    <p><?= date('M j, Y g:i A', strtotime($supplier['created_at'])) ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4">
                            <a href="edit_supplier.php?id=<?= $supplier['supplier_id'] ?>" class="btn btn-warning">
                                <i class="bi bi-pencil"></i> Edit Supplier
                            </a>
                            <a href="suppliers.php" class="btn btn-secondary">Back to List</a>
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