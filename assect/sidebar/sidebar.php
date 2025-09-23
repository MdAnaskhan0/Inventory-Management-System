<div class="col-md-3 col-lg-2 sidebar d-md-block">
    <div class="text-center mb-4">
        <h4>Inventory System</h4>
        <hr class="bg-light">
    </div>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link active" href="admindashboard.php">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        </li>

        <!-- Customers -->
        <li class="nav-item">
            <a class="nav-link active" href="customers.php">
                <i class="bi bi-speedometer2"></i> Customers
            </a>
        </li>

        <!-- Product -->
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#productsSubmenu" role="button" aria-expanded="false">
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
            <a class="nav-link" data-bs-toggle="collapse" href="#usersSubmenu" role="button" aria-expanded="false">
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
            <a class="nav-link" data-bs-toggle="collapse" href="#suppliersSubmenu" role="button" aria-expanded="false">
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