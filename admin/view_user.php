<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Include database configuration
include '../db/config.php';

// Get user ID from URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: users.php");
    exit();
}

$user_id = $_GET['id'];

// Fetch user data
$user = null;
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: users.php");
    exit();
}

$user = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View User - Inventory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        /* Same styles as users.php */
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

        .user-detail {
            margin-bottom: 1rem;
        }

        .user-detail label {
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

                <!-- View User Details -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <h2>User Details</h2>
                            <a href="users.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Back to Users
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card dashboard-card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="user-detail">
                                    <label>ID:</label>
                                    <p><?= $user['id'] ?></p>
                                </div>
                                <div class="user-detail">
                                    <label>Username:</label>
                                    <p><?= htmlspecialchars($user['username']) ?></p>
                                </div>
                                <div class="user-detail">
                                    <label>Email:</label>
                                    <p><?= htmlspecialchars($user['email']) ?></p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="user-detail">
                                    <label>Role:</label>
                                    <p>
                                        <span
                                            class="badge bg-<?= $user['role'] === 'admin' ? 'primary' : 'secondary' ?>">
                                            <?= ucfirst($user['role']) ?>
                                        </span>
                                    </p>
                                </div>
                                <div class="user-detail">
                                    <label>Created At:</label>
                                    <p><?= date('M j, Y g:i A', strtotime($user['created_at'])) ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4">
                            <a href="edit_user.php?id=<?= $user['id'] ?>" class="btn btn-warning">
                                <i class="bi bi-pencil"></i> Edit User
                            </a>
                            <a href="users.php" class="btn btn-secondary">Back to List</a>
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