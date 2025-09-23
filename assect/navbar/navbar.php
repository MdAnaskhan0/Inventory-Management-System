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