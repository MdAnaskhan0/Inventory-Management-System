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
        // Insert supplier
        $insert_stmt = $conn->prepare("INSERT INTO suppliers (name, contact_person, email, phone, address) VALUES (?, ?, ?, ?, ?)");
        $insert_stmt->bind_param("sssss", $name, $contact_person, $email, $phone, $address);

        if ($insert_stmt->execute()) {
            $success_msg = "Supplier added successfully!";
            // Clear form fields
            $_POST = array();
        } else {
            $error_msg = "Error adding supplier: " . $conn->error;
        }

        $insert_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Supplier - Inventory Management System</title>
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

                <!-- Add Supplier Form -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <h2>Add New Supplier</h2>
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
                                        value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>"
                                        required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="contact_person" class="form-label">Contact Person</label>
                                    <input type="text" class="form-control" id="contact_person" name="contact_person"
                                        value="<?= isset($_POST['contact_person']) ? htmlspecialchars($_POST['contact_person']) : '' ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email"
                                        value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Phone</label>
                                    <input type="tel" class="form-control" id="phone" name="phone"
                                        value="<?= isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '' ?>">
                                </div>
                                <div class="col-12 mb-3">
                                    <label for="address" class="form-label">Address</label>
                                    <textarea class="form-control" id="address" name="address"
                                        rows="3"><?= isset($_POST['address']) ? htmlspecialchars($_POST['address']) : '' ?></textarea>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Add Supplier</button>
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