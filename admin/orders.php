<?php
// admin/orders.php
try {
    require_once '../settings/core.php';

    // Check if user is logged in and admin
    if (!is_logged_in() || !is_admin()) {
        header('Location: ../login/login.php');
        exit;
    }

    // safe DB include
    $db_paths = [
        __DIR__ . '/../settings/db_class.php',
        dirname(__DIR__) . '/settings/db_class.php',
        __DIR__ . '/../../settings/db_class.php'
    ];
    $db_included = false;
    foreach ($db_paths as $p) { if (file_exists($p)) { require_once $p; $db_included = true; break; } }
    if (!$db_included) throw new Exception('Database class not found');

    $dbc = new db_connection();
    $conn = $dbc->db_conn();
    if ($conn === false) { $dbc->db_connect(); $conn = $dbc->db; }

    // Try to load recent orders
    $orders = [];
    $sql = "SELECT o.* , c.customer_name, c.customer_email
            FROM orders o
            LEFT JOIN customer c ON o.customer_id = c.customer_id
            ORDER BY o.created_at DESC
            LIMIT 200";
    $res = $conn->query($sql);
    if ($res) {
        while ($r = $res->fetch_assoc()) $orders[] = $r;
    }

} catch (Throwable $ex) {
    error_log('admin/orders.php exception: ' . $ex->getMessage());
    http_response_code(500);
    echo '<h1>Server error</h1><p>Unable to load orders page. Check server logs.</p>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; padding-top: 20px; }
        .btn-custom { background-color: #D19C97; border-color: #D19C97; color: #fff; }
        .btn-custom:hover { background-color: #b77a7a; border-color: #b77a7a; color: #fff; }
        .card { border: none; border-radius: 12px; box-shadow: 0 4px 8px rgba(0,0,0,0.06); }
        .card-header { background-color: #D19C97; color: #fff; border-radius: 12px 12px 0 0 !important; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row mb-4">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <h2><i class="fas fa-receipt me-2"></i>Orders</h2>
                <a href="../index.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i>Back to Site</a>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Recent Orders</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($orders)): ?>
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <p>No orders found.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Order Ref</th>
                                            <th>Customer</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($orders as $i => $o): ?>
                                        <tr>
                                            <td><?php echo $i+1; ?></td>
                                            <td><?php echo htmlspecialchars($o['order_ref'] ?? ($o['invoice_no'] ?? '')); ?></td>
                                            <td><?php echo htmlspecialchars(($o['customer_name'] ?? '') . ' (' . ($o['customer_email'] ?? '') . ')'); ?></td>
                                            <td><?php echo isset($o['total_amount']) ? number_format($o['total_amount'],2) : (isset($o['amt']) ? number_format($o['amt'],2) : '-'); ?></td>
                                            <td><?php echo htmlspecialchars($o['status'] ?? $o['order_status'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($o['created_at'] ?? $o['order_date'] ?? ''); ?></td>
                                            <td>
                                                <a href="order_view.php?order_id=<?php echo urlencode($o['order_id'] ?? ''); ?>" class="btn btn-sm btn-outline-primary">View</a>
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
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
