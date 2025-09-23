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
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];

    // Validate inputs
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error_msg = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $error_msg = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error_msg = "Password must be at least 6 characters long.";
    } else {
        // Check if username or email already exists
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check_stmt->bind_param("ss", $username, $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error_msg = "Username or email already exists.";
        } else {
            // Hash password and insert user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert_stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            $insert_stmt->bind_param("ssss", $username, $email, $hashed_password, $role);

            if ($insert_stmt->execute()) {
                $success_msg = "User added successfully!";
                // Clear form fields
                $_POST = array();
            } else {
                $error_msg = "Error adding user: " . $conn->error;
            }

            $insert_stmt->close();
        }

        $check_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add User - Inventory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assect/css/style.css">
    <!-- <style>
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

                <!-- Add User Form -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <h2>Add New User</h2>
                            <a href="users.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Back to Users
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
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username"
                                        value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>"
                                        required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email"
                                        value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                                        required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password</label>
                                    <input type="password" class="form-control" id="confirm_password"
                                        name="confirm_password" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="role" class="form-label">Role</label>
                                    <select class="form-select" id="role" name="role" required>
                                        <option value="user" <?= (isset($_POST['role']) && $_POST['role'] === 'user') ? 'selected' : '' ?>>User</option>
                                        <option value="admin" <?= (isset($_POST['role']) && $_POST['role'] === 'admin') ? 'selected' : '' ?>>Admin</option>
                                    </select>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Add User</button>
                            <a href="users.php" class="btn btn-secondary">Cancel</a>
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