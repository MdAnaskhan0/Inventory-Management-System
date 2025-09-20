<?php
session_start();
include 'config.php';

$msg = "";

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = md5($_POST['password']); // MD5 hash

    $sql = "SELECT * FROM users WHERE email = ? AND password = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $email, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $_SESSION['username'] = $row['username'];
        $_SESSION['role'] = $row['role'];

        if ($row['role'] == 'admin') {
            header("Location: admin/admindashboard.php");
        } else {
            header("Location: Client/clientdashboard.php");
        }
        exit();
    } else {
        $msg = "❌ Invalid email or password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | System Access</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            max-width: 1000px;
            width: 100%;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            background: white;
            position: relative;
        }

        .login-image {
            background: #ffffff;
            color: #000000;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
        }

        .login-form {
            background: white;
            padding: 40px;
        }

        .form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }

        .btn-primary {
            background-color: #0d6efd;
            border: none;
            padding: 10px;
        }

        .btn-primary:hover {
            background-color: #0b5ed7;
        }

        .logo {
            width: 50%;
            display: block;
            margin: 0 auto 30px auto;
        }
        
        .divider {
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            height: 70%;
            width: 1px;
            background-color: #dee2e6;
        }
        
        .text-left {
            text-align: left !important;
        }
    </style>
</head>

<body>
    <div class="container login-container">
        <div class="row no-gutters">
            <!-- Left side with image and welcome text -->
            <div class="col-md-6 login-image d-none d-md-block position-relative">
                <div class="divider d-none d-md-block"></div>
                <img src="assect\image\logo.png" alt="Logo" class="logo">
                <h2 class="fw-bold mb-4 text-left">Welcome Back!</h2>
                <p class="text-left">Access your account to manage your dashboard, update settings, and more.</p>
                <div class="mt-5 text-left">
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <span>Secure authentication</span>
                    </div>
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <span>Role-based access control</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <span>24/7 system availability</span>
                    </div>
                </div>
            </div>

            <!-- Right side with login form -->
            <div class="col-md-6 login-form">
                <div class="text-center mb-4">
                    <h2 class="fw-bold">Sign In</h2>
                    <p class="text-muted">Enter your credentials to access your account</p>
                </div>

                <?php if ($msg): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $msg; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email"
                                placeholder="Enter your email" required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password"
                                placeholder="Enter your password" required>
                        </div>
                        <div class="form-text text-end">
                            <a href="#" class="text-decoration-none">Forgot password?</a>
                        </div>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" name="login" class="btn btn-primary btn-lg">Sign In</button>
                    </div>
                </form>

                <div class="text-center mt-4">
                    <p class="text-muted">Don't have an account? <a href="#" class="text-decoration-none">Contact
                            administrator</a></p>
                </div>

                <div class="mt-4 pt-3 border-top text-center">
                    <small class="text-muted">© 2023 Company Name. All rights reserved.</small>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>