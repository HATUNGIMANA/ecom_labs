<?php
// actions/product_action.php
// Suppress error display to prevent HTML in JSON response
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Start output buffering to catch any unwanted output
ob_start();

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) session_start();

function json_response(bool $ok, string $msg, array $extra = []) {
    // Clean any output buffer before sending JSON
    while (ob_get_level()) {
        ob_end_clean();
    }
    $payload = array_merge(['success' => $ok, 'message' => $msg], $extra);
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(false, 'Invalid request method. Use POST.');
$action = $_POST['action'] ?? null;
if (!$action) json_response(false, 'Missing action parameter.');

// include core
$core_paths = [
    __DIR__ . '/../settings/core.php',
    __DIR__ . '/../../settings/core.php',
    __DIR__ . '/settings/core.php'
];
$foundCore = false;
foreach ($core_paths as $p) {
    if (file_exists($p)) {
        require_once $p;
        $foundCore = true;
        break;
    }
}
if (!$foundCore) json_response(false, 'Server error: core settings not found.');
if (!function_exists('is_logged_in') || !function_exists('is_admin')) json_response(false, 'Server misconfiguration: auth helpers missing.');
if (!is_logged_in()) json_response(false, 'Not authenticated. Please login.');
if (!is_admin()) json_response(false, 'Unauthorized. Admin access required.');

// include controller
$ctrl_paths = [
    __DIR__ . '/../controllers/product_controller.php',
    __DIR__ . '/../../controllers/product_controller.php',
    __DIR__ . '/controllers/product_controller.php'
];
$found = false;
foreach ($ctrl_paths as $p) {
    if (file_exists($p)) { require_once $p; $found = true; break; }
}
if (!$found || !class_exists('ProductController')) json_response(false, 'Server error: product controller not found.');

try {
    $controller = new ProductController();
} catch (Exception $e) {
    json_response(false, 'Failed to initialize controller: ' . $e->getMessage());
}

try {
    switch (strtolower($action)) {
        case 'fetch':
            $userId = $_SESSION['customer_id'] ?? null;
            $rows = $controller->fetch_products_ctr($userId);
            json_response(true, 'Products fetched', ['data' => $rows]);
            break;

        case 'add':
            // required fields
            $cat = intval($_POST['product_cat'] ?? 0);
            $brand = intval($_POST['product_brand'] ?? 0);
            $title = trim($_POST['product_title'] ?? '');
            $price = trim($_POST['product_price'] ?? '');
            $desc = trim($_POST['product_desc'] ?? '');
            $keywords = trim($_POST['product_keywords'] ?? '');
            if ($cat <= 0 || $brand <= 0 || $title === '' || $price === '') {
                json_response(false, 'product_cat, product_brand, product_title and product_price are required');
            }
            if (!is_numeric($price)) json_response(false, 'product_price must be numeric');

            $payload = [
                'product_cat' => $cat,
                'product_brand' => $brand,
                'product_title' => $title,
                'product_price' => (float)$price,
                'product_desc' => $desc,
                'product_keywords' => $keywords,
                'created_by' => $_SESSION['customer_id'] ?? null
            ];
            $res = $controller->add_product_ctr($payload);
            if ($res['success']) json_response(true, $res['message'], ['product_id' => $res['product_id'] ?? null]);
            json_response(false, $res['message'] ?? 'Failed to add product');
            break;

        case 'update':
            $id = intval($_POST['product_id'] ?? 0);
            $cat = intval($_POST['product_cat'] ?? 0);
            $brand = intval($_POST['product_brand'] ?? 0);
            $title = trim($_POST['product_title'] ?? '');
            $price = trim($_POST['product_price'] ?? '');
            $desc = trim($_POST['product_desc'] ?? '');
            $keywords = trim($_POST['product_keywords'] ?? '');
            if ($id <= 0 || $cat <= 0 || $brand <= 0 || $title === '' || $price === '') json_response(false, 'product_id, product_cat, product_brand, product_title and product_price are required');
            if (!is_numeric($price)) json_response(false, 'product_price must be numeric');

            $payload = [
                'product_id' => $id,
                'product_cat' => $cat,
                'product_brand' => $brand,
                'product_title' => $title,
                'product_price' => (float)$price,
                'product_desc' => $desc,
                'product_keywords' => $keywords,
                'updated_by' => $_SESSION['customer_id'] ?? null
            ];
            $res = $controller->update_product_ctr($payload);
            if ($res['success']) json_response(true, $res['message']);
            json_response(false, $res['message'] ?? 'Failed to update product');
            break;

        case 'delete':
            $id = intval($_POST['product_id'] ?? 0);
            if ($id <= 0) json_response(false, 'product_id is required');
            $res = $controller->delete_product_ctr($id, $_SESSION['customer_id'] ?? null);
            if ($res['success']) json_response(true, $res['message']);
            json_response(false, $res['message'] ?? 'Failed to delete product');
            break;

        default:
            json_response(false, 'Unknown action: ' . htmlspecialchars($action));
    }
} catch (Exception $ex) {
    error_log("product_action.php Exception: " . $ex->getMessage() . "\n" . $ex->getTraceAsString());
    json_response(false, 'Server error. Check logs.');
}
