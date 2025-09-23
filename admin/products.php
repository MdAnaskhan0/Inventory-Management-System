<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Include database configuration
include '../db/config.php';

// Initialize variables
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Handle delete operation
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        $success_msg = "Product deleted successfully!";
    } else {
        $error_msg = "Error deleting product: " . $stmt->error;
    }
    $stmt->close();
}

// Build search query
$search_condition = "";
$params = [];
$types = "";

if (!empty($search)) {
    $search_condition = "WHERE (name LIKE ? OR description LIKE ? OR category LIKE ? OR sku LIKE ?)";
    $search_term = "%$search%";
    $params = array($search_term, $search_term, $search_term, $search_term);
    $types = "ssss";
}

// Get total number of products for pagination
$count_sql = "SELECT COUNT(*) as total FROM products $search_condition";
$count_stmt = $conn->prepare($count_sql);

if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}

$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_products = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_products / $limit);
$count_stmt->close();

// Get products for current page
$sql = "SELECT * FROM products $search_condition ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    $stmt->bind_param($types, ...$params);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}

$stmt->execute();
$result = $stmt->get_result();
$products = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Inventory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assect/css/add_product.css">
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

                <!-- Products Page Content -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Products Management</h2>
                    <a href="add_product.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Add New Product
                    </a>
                </div>

                <!-- Success/Error Messages -->
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

                <!-- Search Form -->
                <div class="table-container">
                    <form method="GET" action="products.php" class="mb-4">
                        <div class="input-group search-form">
                            <input type="text" class="form-control" placeholder="Search products..." name="search"
                                value="<?= htmlspecialchars($search) ?>">
                            <button class="btn btn-outline-primary" type="submit">
                                <i class="bi bi-search"></i> Search
                            </button>
                            <?php if (!empty($search)): ?>
                                <a href="products.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-circle"></i> Clear
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>

                    <!-- Products Table -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>SKU</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($products) > 0): ?>
                                    <?php foreach ($products as $product): ?>
                                        <tr>
                                            <td><?= $product['product_id'] ?></td>
                                            <td><?= htmlspecialchars($product['name']) ?></td>
                                            <td><?= htmlspecialchars($product['category']) ?></td>
                                            <td>$<?= number_format($product['price'], 2) ?></td>
                                            <td>
                                                <span
                                                    class="<?= $product['stock_quantity'] < $product['min_stock_level'] ? 'text-danger fw-bold' : '' ?>">
                                                    <?= $product['stock_quantity'] ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($product['sku']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= $product['is_active'] ? 'success' : 'secondary' ?>">
                                                    <?= $product['is_active'] ? 'Active' : 'Inactive' ?>
                                                </span>
                                            </td>
                                            <td class="action-buttons">
                                                <a href="view_product.php?id=<?= $product['product_id'] ?>"
                                                    class="btn btn-sm btn-info" title="View">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="edit_product.php?id=<?= $product['product_id'] ?>"
                                                    class="btn btn-sm btn-warning" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="products.php?delete_id=<?= $product['product_id'] ?>"
                                                    class="btn btn-sm btn-danger" title="Delete"
                                                    onclick="return confirm('Are you sure you want to delete this product?')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No products found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="pagination-container">
                        <div>
                            Showing <?= count($products) ?> of <?= $total_products ?> products
                        </div>
                        <div>
                            <nav>
                                <ul class="pagination">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link"
                                                href="products.php?page=<?= $page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">
                                                Previous
                                            </a>
                                        </li>
                                    <?php else: ?>
                                        <li class="page-item disabled">
                                            <span class="page-link">Previous</span>
                                        </li>
                                    <?php endif; ?>

                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link"
                                                href="products.php?page=<?= $page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">
                                                Next
                                            </a>
                                        </li>
                                    <?php else: ?>
                                        <li class="page-item disabled">
                                            <span class="page-link">Next</span>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
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