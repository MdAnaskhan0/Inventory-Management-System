<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

include '../db/config.php';

// Handle report generation
if (isset($_POST['generate_report'])) {
    $report_type = $_POST['report_type'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $format = $_POST['format'];

    // Generate report based on type
    switch ($report_type) {
        case 'sales':
            generateSalesReport($conn, $start_date, $end_date, $format);
            break;
        case 'products':
            generateProductsReport($conn, $format);
            break;
        case 'customers':
            generateCustomersReport($conn, $format);
            break;
        case 'inventory':
            generateInventoryReport($conn, $format);
            break;
        case 'orders':
            generateOrdersReport($conn, $start_date, $end_date, $format);
            break;
        default:
            $_SESSION['error'] = "Invalid report type selected!";
            header("Location: reports.php");
            exit();
    }
}

// Get statistics for dashboard
$stats = [
    'total_sales' => 0,
    'total_orders' => 0,
    'total_customers' => 0,
    'total_products' => 0,
    'low_stock_items' => 0
];

$result = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE payment_status = 'completed'");
if ($result)
    $stats['total_sales'] = $result->fetch_assoc()['total'] ?? 0;

$result = $conn->query("SELECT COUNT(*) as count FROM orders");
if ($result)
    $stats['total_orders'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM customers");
if ($result)
    $stats['total_customers'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM products");
if ($result)
    $stats['total_products'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM products WHERE stock_quantity < 10");
if ($result)
    $stats['low_stock_items'] = $result->fetch_assoc()['count'];

// Recent activities
$recent_orders = $conn->query("
    SELECT o.*, c.first_name, c.last_name 
    FROM orders o 
    LEFT JOIN customers c ON o.customer_id = c.customer_id 
    ORDER BY o.order_date DESC 
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

$low_stock_products = $conn->query("
    SELECT * FROM products 
    WHERE stock_quantity < 10 
    ORDER BY stock_quantity ASC 
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Report generation functions
function generateSalesReport($conn, $start_date, $end_date, $format)
{
    $query = "SELECT 
                DATE(o.order_date) as order_date,
                COUNT(o.order_id) as total_orders,
                SUM(o.total_amount) as total_sales,
                AVG(o.total_amount) as avg_order_value,
                COUNT(DISTINCT o.customer_id) as unique_customers
              FROM orders o 
              WHERE o.order_date BETWEEN ? AND ? 
              GROUP BY DATE(o.order_date) 
              ORDER BY order_date";

    $stmt = $conn->prepare($query);
    $end_date = $end_date . ' 23:59:59';
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);

    $filename = "sales_report_" . date('Y-m-d') . ($format === 'csv' ? '.csv' : '.xlsx');
    exportReport($data, $filename, $format, 'Sales Report');
}

function generateProductsReport($conn, $format)
{
    $query = "SELECT 
                p.product_id,
                p.product_name,
                p.product_code,
                p.category,
                p.price,
                p.stock_quantity,
                p.min_stock_level,
                p.created_at,
                COUNT(oi.product_id) as times_ordered,
                COALESCE(SUM(oi.quantity), 0) as total_sold
              FROM products p 
              LEFT JOIN order_items oi ON p.product_id = oi.product_id 
              GROUP BY p.product_id 
              ORDER BY total_sold DESC";

    $result = $conn->query($query);
    $data = $result->fetch_all(MYSQLI_ASSOC);

    $filename = "products_report_" . date('Y-m-d') . ($format === 'csv' ? '.csv' : '.xlsx');
    exportReport($data, $filename, $format, 'Products Report');
}

function generateCustomersReport($conn, $format)
{
    $query = "SELECT 
                c.customer_id,
                c.first_name,
                c.last_name,
                c.email,
                c.phone,
                c.address,
                c.created_at,
                COUNT(o.order_id) as total_orders,
                COALESCE(SUM(o.total_amount), 0) as total_spent,
                AVG(o.total_amount) as avg_order_value
              FROM customers c 
              LEFT JOIN orders o ON c.customer_id = o.customer_id 
              GROUP BY c.customer_id 
              ORDER BY total_spent DESC";

    $result = $conn->query($query);
    $data = $result->fetch_all(MYSQLI_ASSOC);

    $filename = "customers_report_" . date('Y-m-d') . ($format === 'csv' ? '.csv' : '.xlsx');
    exportReport($data, $filename, $format, 'Customers Report');
}

function generateInventoryReport($conn, $format)
{
    $query = "SELECT 
                product_id,
                product_name,
                product_code,
                category,
                price,
                stock_quantity,
                min_stock_level,
                (stock_quantity < min_stock_level) as low_stock,
                created_at
              FROM products 
              ORDER BY low_stock DESC, stock_quantity ASC";

    $result = $conn->query($query);
    $data = $result->fetch_all(MYSQLI_ASSOC);

    $filename = "inventory_report_" . date('Y-m-d') . ($format === 'csv' ? '.csv' : '.xlsx');
    exportReport($data, $filename, $format, 'Inventory Report');
}

function generateOrdersReport($conn, $start_date, $end_date, $format)
{
    $query = "SELECT 
                o.order_id,
                CONCAT(c.first_name, ' ', c.last_name) as customer_name,
                o.order_date,
                o.total_amount,
                o.status,
                o.payment_status,
                o.payment_method,
                COUNT(oi.order_item_id) as items_count
              FROM orders o 
              LEFT JOIN customers c ON o.customer_id = c.customer_id 
              LEFT JOIN order_items oi ON o.order_id = oi.order_id 
              WHERE o.order_date BETWEEN ? AND ? 
              GROUP BY o.order_id 
              ORDER BY o.order_date DESC";

    $stmt = $conn->prepare($query);
    $end_date = $end_date . ' 23:59:59';
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);

    $filename = "orders_report_" . date('Y-m-d') . ($format === 'csv' ? '.csv' : '.xlsx');
    exportReport($data, $filename, $format, 'Orders Report');
}

function exportReport($data, $filename, $format, $title)
{
    if ($format === 'csv') {
        exportToCSV($data, $filename, $title);
    } else {
        exportToExcel($data, $filename, $title);
    }
}

function exportToCSV($data, $filename, $title)
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    // Add title
    fputcsv($output, [$title]);
    fputcsv($output, ['Generated on: ' . date('Y-m-d H:i:s')]);
    fputcsv($output, []); // Empty row

    // Add headers
    if (!empty($data)) {
        fputcsv($output, array_keys($data[0]));
    }

    // Add data
    foreach ($data as $row) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit();
}

function exportToExcel($data, $filename, $title)
{
    require_once '../vendor/autoload.php'; // Require PhpSpreadsheet

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set title
    $sheet->setCellValue('A1', $title);
    $sheet->setCellValue('A2', 'Generated on: ' . date('Y-m-d H:i:s'));

    // Set headers
    if (!empty($data)) {
        $headers = array_keys($data[0]);
        $sheet->fromArray($headers, NULL, 'A4');

        // Set data
        $row = 5;
        foreach ($data as $item) {
            $sheet->fromArray(array_values($item), NULL, 'A' . $row);
            $row++;
        }

        // Auto size columns
        foreach (range('A', $sheet->getHighestDataColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    // Style title
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A4:' . $sheet->getHighestDataColumn() . '4')
        ->getFont()->setBold(true);

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save('php://output');
    exit();
}

// Handle quick exports
if (isset($_GET['quick_report'])) {
    switch ($_GET['quick_report']) {
        case 'recent_orders':
            $result = $conn->query("
                SELECT o.*, c.first_name, c.last_name 
                FROM orders o 
                LEFT JOIN customers c ON o.customer_id = c.customer_id 
                ORDER BY o.order_date DESC 
                LIMIT 100
            ");
            $data = $result->fetch_all(MYSQLI_ASSOC);
            exportToCSV($data, "recent_orders_" . date('Y-m-d') . ".csv", 'Recent Orders Report');
            break;

        case 'low_stock':
            $result = $conn->query("
                SELECT * FROM products 
                WHERE stock_quantity < min_stock_level 
                ORDER BY stock_quantity ASC
            ");
            $data = $result->fetch_all(MYSQLI_ASSOC);
            exportToCSV($data, "low_stock_alert_" . date('Y-m-d') . ".csv", 'Low Stock Alert Report');
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Inventory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assect/css/style.css">
    <style>
        .stat-card {
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .report-card {
            border-left: 4px solid #0d6efd;
        }

        .low-stock {
            color: #dc3545;
            font-weight: bold;
        }

        .medium-stock {
            color: #ffc107;
            font-weight: bold;
        }

        .good-stock {
            color: #198754;
            font-weight: bold;
        }
    </style>
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

                <!-- Reports Dashboard -->
                <div class="card dashboard-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Reports & Analytics</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?= $_SESSION['error'] ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php unset($_SESSION['error']); ?>
                        <?php endif; ?>

                        <!-- Statistics Cards -->
                        <div class="row mb-4">
                            <div class="col-md-2">
                                <div class="card stat-card bg-primary text-white">
                                    <div class="card-body text-center">
                                        <h6>Total Sales</h6>
                                        <h4>$<?= number_format($stats['total_sales'], 2) ?></h4>
                                        <small>All Time</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="card stat-card bg-success text-white">
                                    <div class="card-body text-center">
                                        <h6>Total Orders</h6>
                                        <h4><?= $stats['total_orders'] ?></h4>
                                        <small>All Time</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="card stat-card bg-info text-white">
                                    <div class="card-body text-center">
                                        <h6>Customers</h6>
                                        <h4><?= $stats['total_customers'] ?></h4>
                                        <small>Registered</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="card stat-card bg-warning text-dark">
                                    <div class="card-body text-center">
                                        <h6>Products</h6>
                                        <h4><?= $stats['total_products'] ?></h4>
                                        <small>In Inventory</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="card stat-card bg-danger text-white">
                                    <div class="card-body text-center">
                                        <h6>Low Stock</h6>
                                        <h4><?= $stats['low_stock_items'] ?></h4>
                                        <small>Need Attention</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="card stat-card bg-secondary text-white">
                                    <div class="card-body text-center">
                                        <h6>Report Date</h6>
                                        <h6><?= date('M j, Y') ?></h6>
                                        <small><?= date('g:i A') ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Report Generator -->
                        <div class="card report-card mb-4">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="bi bi-download me-2"></i>Generate Report</h6>
                            </div>
                            <div class="card-body">
                                <form method="POST" class="row g-3">
                                    <div class="col-md-3">
                                        <label for="report_type" class="form-label">Report Type</label>
                                        <select class="form-select" id="report_type" name="report_type" required>
                                            <option value="">Select Report Type</option>
                                            <option value="sales">Sales Report</option>
                                            <option value="products">Products Report</option>
                                            <option value="customers">Customers Report</option>
                                            <option value="inventory">Inventory Report</option>
                                            <option value="orders">Orders Report</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="start_date" class="form-label">Start Date</label>
                                        <input type="date" class="form-control" id="start_date" name="start_date"
                                            value="<?= date('Y-m-01') ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="end_date" class="form-label">End Date</label>
                                        <input type="date" class="form-control" id="end_date" name="end_date"
                                            value="<?= date('Y-m-d') ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="format" class="form-label">Format</label>
                                        <select class="form-select" id="format" name="format" required>
                                            <option value="csv">CSV</option>
                                            <option value="excel">Excel</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" name="generate_report" class="btn btn-primary">
                                            <i class="bi bi-download me-1"></i> Generate Report
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">
                                            <i class="bi bi-arrow-clockwise me-1"></i> Reset
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Quick Reports -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card report-card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0"><i class="bi bi-cart me-2"></i>Recent Orders</h6>
                                        <a href="reports.php?quick_report=recent_orders"
                                            class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-download me-1"></i> Export
                                        </a>
                                    </div>
                                    <div class="card-body">
                                        <?php if (empty($recent_orders)): ?>
                                            <p class="text-muted">No recent orders found.</p>
                                        <?php else: ?>
                                            <div class="table-responsive">
                                                <table class="table table-sm">
                                                    <thead>
                                                        <tr>
                                                            <th>Order ID</th>
                                                            <th>Customer</th>
                                                            <th>Date</th>
                                                            <th>Amount</th>
                                                            <th>Status</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($recent_orders as $order): ?>
                                                            <tr>
                                                                <td>#<?= $order['order_id'] ?></td>
                                                                <td><?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?>
                                                                </td>
                                                                <td><?= date('M j', strtotime($order['order_date'])) ?></td>
                                                                <td>$<?= number_format($order['total_amount'], 2) ?></td>
                                                                <td>
                                                                    <span class="badge bg-<?=
                                                                        $order['status'] === 'delivered' ? 'success' :
                                                                        ($order['status'] === 'processing' ? 'info' :
                                                                            ($order['status'] === 'shipped' ? 'warning' :
                                                                                ($order['status'] === 'cancelled' ? 'danger' : 'secondary')))
                                                                        ?>">
                                                                        <?= ucfirst($order['status']) ?>
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card report-card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Low Stock Alert
                                        </h6>
                                        <a href="reports.php?quick_report=low_stock"
                                            class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-download me-1"></i> Export
                                        </a>
                                    </div>
                                    <div class="card-body">
                                        <?php if (empty($low_stock_products)): ?>
                                            <p class="text-muted">No low stock items.</p>
                                        <?php else: ?>
                                            <div class="table-responsive">
                                                <table class="table table-sm">
                                                    <thead>
                                                        <tr>
                                                            <th>Product</th>
                                                            <th>Stock</th>
                                                            <th>Min Level</th>
                                                            <th>Status</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($low_stock_products as $product): ?>
                                                            <tr>
                                                                <td><?= htmlspecialchars($product['product_name']) ?></td>
                                                                <td>
                                                                    <span class="<?=
                                                                        $product['stock_quantity'] == 0 ? 'low-stock' :
                                                                        ($product['stock_quantity'] < 5 ? 'medium-stock' : 'good-stock')
                                                                        ?>">
                                                                        <?= $product['stock_quantity'] ?>
                                                                    </span>
                                                                </td>
                                                                <td><?= $product['min_stock_level'] ?></td>
                                                                <td>
                                                                    <?php if ($product['stock_quantity'] == 0): ?>
                                                                        <span class="badge bg-danger">Out of Stock</span>
                                                                    <?php elseif ($product['stock_quantity'] < $product['min_stock_level']): ?>
                                                                        <span class="badge bg-warning">Low Stock</span>
                                                                    <?php else: ?>
                                                                        <span class="badge bg-success">Adequate</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Monthly Summary -->
                        <div class="card report-card mt-4">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="bi bi-calendar me-2"></i>This Month's Summary</h6>
                            </div>
                            <div class="card-body">
                                <?php
                                $month_start = date('Y-m-01');
                                $month_end = date('Y-m-t');

                                $monthly_stats = $conn->query("
                                    SELECT 
                                        COUNT(*) as orders_count,
                                        SUM(total_amount) as total_sales,
                                        AVG(total_amount) as avg_order,
                                        COUNT(DISTINCT customer_id) as unique_customers
                                    FROM orders 
                                    WHERE order_date BETWEEN '$month_start' AND '$month_end 23:59:59'
                                ")->fetch_assoc();
                                ?>

                                <div class="row text-center">
                                    <div class="col-md-3">
                                        <h4><?= $monthly_stats['orders_count'] ?? 0 ?></h4>
                                        <small class="text-muted">Orders This Month</small>
                                    </div>
                                    <div class="col-md-3">
                                        <h4>$<?= number_format($monthly_stats['total_sales'] ?? 0, 2) ?></h4>
                                        <small class="text-muted">Monthly Revenue</small>
                                    </div>
                                    <div class="col-md-3">
                                        <h4>$<?= number_format($monthly_stats['avg_order'] ?? 0, 2) ?></h4>
                                        <small class="text-muted">Average Order Value</small>
                                    </div>
                                    <div class="col-md-3">
                                        <h4><?= $monthly_stats['unique_customers'] ?? 0 ?></h4>
                                        <small class="text-muted">Active Customers</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function resetForm() {
            document.getElementById('report_type').selectedIndex = 0;
            document.getElementById('start_date').value = '<?= date('Y-m-01') ?>';
            document.getElementById('end_date').value = '<?= date('Y-m-d') ?>';
            document.getElementById('format').selectedIndex = 0;
        }

        // Set default dates
        document.addEventListener('DOMContentLoaded', function () {
            const today = new Date().toISOString().split('T')[0];
            const firstDay = today.substring(0, 8) + '01';

            if (!document.getElementById('start_date').value) {
                document.getElementById('start_date').value = firstDay;
            }
            if (!document.getElementById('end_date').value) {
                document.getElementById('end_date').value = today;
            }
        });
    </script>
</body>

</html>