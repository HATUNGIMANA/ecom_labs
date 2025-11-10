<?php
// actions/check_schema.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../settings/db_cred.php';

$conn = @mysqli_connect(SERVER, USERNAME, PASSWD, DATABASE);
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'DB connect failed: ' . mysqli_connect_error()]);
    exit;
}

$tables = [
    'categories' => ['customer_id', 'cat_name', 'cat_id'],
    'brands'     => ['cat_id', 'brand_name', 'brand_id', 'created_by'],
    'products'   => ['product_cat', 'product_brand', 'product_title', 'product_price']
];

$out = ['success' => true, 'details' => []];
foreach ($tables as $table => $cols) {
    $present = [];
    $missing = [];
    foreach ($cols as $c) {
        $res = mysqli_query($conn, "SHOW COLUMNS FROM `" . $table . "` LIKE '" . $c . "'");
        if ($res && mysqli_num_rows($res) > 0) $present[] = $c; else $missing[] = $c;
    }
    $out['details'][$table] = ['present' => $present, 'missing' => $missing];
}

mysqli_close($conn);
echo json_encode($out, JSON_PRETTY_PRINT);
