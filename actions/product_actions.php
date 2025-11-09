<?php
// actions/product_actions.php
// Customer-facing product operations (display, search, filter)

header('Content-Type: application/json; charset=utf-8');

// Suppress error display to prevent HTML in JSON response
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Start output buffering
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// JSON response helper
function json_response(bool $ok, string $msg, array $extra = []) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    $payload = array_merge(['success' => $ok, 'message' => $msg], $extra);
    echo json_encode($payload);
    exit;
}

// Only accept POST for AJAX requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Invalid request method. Use POST.');
}

$action = $_POST['action'] ?? null;
if (!$action) {
    json_response(false, 'Missing action parameter.');
}

// Include controller
$ctrl_paths = [
    __DIR__ . '/../controllers/product_controller.php',
    __DIR__ . '/../../controllers/product_controller.php',
    __DIR__ . '/controllers/product_controller.php'
];
$found = false;
foreach ($ctrl_paths as $p) {
    if (file_exists($p)) {
        require_once $p;
        $found = true;
        break;
    }
}
if (!$found || !class_exists('ProductController')) {
    json_response(false, 'Server error: product controller not found.');
}

$controller = new ProductController();

try {
    switch (strtolower($action)) {
        case 'view_all':
            $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : null;
            $offset = isset($_POST['offset']) ? (int)$_POST['offset'] : 0;
            $products = $controller->view_all_products_ctr($limit, $offset);
            if ($products === false) {
                json_response(false, 'Failed to fetch products.');
            }
            json_response(true, 'Products fetched', ['products' => $products, 'count' => count($products)]);
            break;

        case 'search':
            $query = trim($_POST['query'] ?? '');
            $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : null;
            $offset = isset($_POST['offset']) ? (int)$_POST['offset'] : 0;
            
            if (empty($query)) {
                json_response(false, 'Search query is required.');
            }
            
            $products = $controller->search_products_ctr($query, $limit, $offset);
            if ($products === false) {
                json_response(false, 'Search failed.');
            }
            json_response(true, 'Search completed', ['products' => $products, 'count' => count($products), 'query' => $query]);
            break;

        case 'filter_category':
            $cat_id = isset($_POST['cat_id']) ? (int)$_POST['cat_id'] : 0;
            $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : null;
            $offset = isset($_POST['offset']) ? (int)$_POST['offset'] : 0;
            
            if ($cat_id <= 0) {
                json_response(false, 'Invalid category ID.');
            }
            
            $products = $controller->filter_products_by_category_ctr($cat_id, $limit, $offset);
            if ($products === false) {
                json_response(false, 'Filter failed.');
            }
            json_response(true, 'Products filtered', ['products' => $products, 'count' => count($products)]);
            break;

        case 'filter_brand':
            $brand_id = isset($_POST['brand_id']) ? (int)$_POST['brand_id'] : 0;
            $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : null;
            $offset = isset($_POST['offset']) ? (int)$_POST['offset'] : 0;
            
            if ($brand_id <= 0) {
                json_response(false, 'Invalid brand ID.');
            }
            
            $products = $controller->filter_products_by_brand_ctr($brand_id, $limit, $offset);
            if ($products === false) {
                json_response(false, 'Filter failed.');
            }
            json_response(true, 'Products filtered', ['products' => $products, 'count' => count($products)]);
            break;

        case 'view_single':
            $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
            
            if ($product_id <= 0) {
                json_response(false, 'Invalid product ID.');
            }
            
            $product = $controller->view_single_product_ctr($product_id);
            if ($product === false || empty($product)) {
                json_response(false, 'Product not found.');
            }
            json_response(true, 'Product fetched', ['product' => $product]);
            break;

        case 'composite_search':
            // EXTRA CREDIT: Advanced search with multiple filters
            $filters = [
                'query' => trim($_POST['query'] ?? ''),
                'cat_id' => isset($_POST['cat_id']) ? (int)$_POST['cat_id'] : 0,
                'brand_id' => isset($_POST['brand_id']) ? (int)$_POST['brand_id'] : 0,
                'max_price' => isset($_POST['max_price']) ? (float)$_POST['max_price'] : null,
                'min_price' => isset($_POST['min_price']) ? (float)$_POST['min_price'] : null
            ];
            $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : null;
            $offset = isset($_POST['offset']) ? (int)$_POST['offset'] : 0;
            
            $products = $controller->composite_search_ctr($filters, $limit, $offset);
            if ($products === false) {
                json_response(false, 'Composite search failed.');
            }
            json_response(true, 'Composite search completed', ['products' => $products, 'count' => count($products), 'filters' => $filters]);
            break;

        default:
            json_response(false, 'Unknown action: ' . htmlspecialchars($action));
    }
} catch (Exception $ex) {
    error_log("product_actions.php Exception: " . $ex->getMessage() . "\n" . $ex->getTraceAsString());
    json_response(false, 'Server error. Check logs.');
}

