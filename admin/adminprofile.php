<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Include database configuration
include '../db/config.php';

$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Change Password
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Get current user's password hash
        $username = $_SESSION['username'];
        $stmt = $conn->prepare("SELECT password FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        // Verify current password (check both md5 and bcrypt formats from your sample data)
        if (
            md5($current_password) === $user['password'] ||
            password_verify($current_password, $user['password'])
        ) {

            if ($new_password === $confirm_password) {
                if (strlen($new_password) >= 6) {
                    // Hash new password using bcrypt
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                    $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
                    $update_stmt->bind_param("ss", $hashed_password, $username);

                    if ($update_stmt->execute()) {
                        $success = "Password updated successfully!";
                    } else {
                        $error = "Error updating password: " . $conn->error;
                    }
                } else {
                    $error = "New password must be at least 6 characters long!";
                }
            } else {
                $error = "New passwords do not match!";
            }
        } else {
            $error = "Current password is incorrect!";
        }
    }

    // Update Profile Information
    if (isset($_POST['update_profile'])) {
        $username = $_POST['username'];
        $email = $_POST['email'];
        $current_username = $_SESSION['username'];

        // Check if new username or email already exists (excluding current user)
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND username != ?");
        $check_stmt->bind_param("sss", $username, $email, $current_username);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "Username or email already exists!";
        } else {
            $update_stmt = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE username = ?");
            $update_stmt->bind_param("sss", $username, $email, $current_username);

            if ($update_stmt->execute()) {
                $_SESSION['username'] = $username;
                $success = "Profile updated successfully!";
            } else {
                $error = "Error updating profile: " . $conn->error;
            }
        }
    }
}

// Get current user data
$username = $_SESSION['username'];
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Inventory Management System</title>
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

                <!-- Admin Profile -->
                <div class="container-fluid mt-4" style="min-height: 85vh">
                    <h2 class="mb-4">Admin Profile</h2>

                    <!-- Alert Messages -->
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <!-- Profile Information -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="card-title mb-0"><i class="bi bi-person-gear"></i> Profile Information
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="">
                                        <div class="mb-3">
                                            <label for="username" class="form-label">Username</label>
                                            <input type="text" class="form-control" id="username" name="username"
                                                value="<?php echo htmlspecialchars($user_data['username']); ?>"
                                                required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="email" name="email"
                                                value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Role</label>
                                            <input type="text" class="form-control"
                                                value="<?php echo htmlspecialchars($user_data['role']); ?>" readonly>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Member Since</label>
                                            <input type="text" class="form-control"
                                                value="<?php echo date('F j, Y', strtotime($user_data['created_at'])); ?>"
                                                readonly>
                                        </div>
                                        <button type="submit" name="update_profile" class="btn btn-primary">
                                            <i class="bi bi-check-circle"></i> Update Profile
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Change Password -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-warning text-dark">
                                    <h5 class="card-title mb-0"><i class="bi bi-shield-lock"></i> Change Password</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="">
                                        <div class="mb-3">
                                            <label for="current_password" class="form-label">Current Password</label>
                                            <input type="password" class="form-control" id="current_password"
                                                name="current_password" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="new_password" class="form-label">New Password</label>
                                            <input type="password" class="form-control" id="new_password"
                                                name="new_password" required>
                                            <div class="form-text">Password must be at least 6 characters long.</div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="confirm_password" class="form-label">Confirm New
                                                Password</label>
                                            <input type="password" class="form-control" id="confirm_password"
                                                name="confirm_password" required>
                                        </div>
                                        <button type="submit" name="change_password" class="btn btn-warning">
                                            <i class="bi bi-key"></i> Change Password
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Include Footer -->
            <?php include '../assect/footer/footer.php'; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simple toggle functionality for sidebar on mobile
        document.querySelector('.navbar-toggler').addEventListener('click', function () {
            document.querySelector('.sidebar').classList.toggle('active');
        });

        // Password confirmation validation
        document.querySelector('form').addEventListener('submit', function (e) {
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');

            if (newPassword && confirmPassword && newPassword.value !== confirmPassword.value) {
                e.preventDefault();
                alert('New passwords do not match!');
                confirmPassword.focus();
            }
        });
    </script>
</body>

</html>
</body>

</html>