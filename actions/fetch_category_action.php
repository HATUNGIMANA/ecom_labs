<?php
// actions/fetch_category_action.php
header('Content-Type: application/json; charset=utf-8');

// Start session
session_start();

// Helper to return JSON then exit
function json_response(bool $success, string $message, array $extra = []) {
    $payload = array_merge(['success' => $success, 'message' => $message], $extra);
    echo json_encode($payload);
    exit;
}

// Attempt to include core helpers so we can detect admin users and behave accordingly
$core_paths = [
    __DIR__ . '/../settings/core.php',
    __DIR__ . '/../../settings/core.php',
    __DIR__ . '/settings/core.php'
];
foreach ($core_paths as $p) {
    if (file_exists($p)) { require_once $p; break; }
}

// If the current user is an admin, don't restrict categories by customer_id (admin sees all)
$customer_id = $_SESSION['customer_id'] ?? null;
if (function_exists('is_admin') && is_admin()) {
    $customer_id = null;
}

// Try to include the category controller
$included = false;
$try_paths = [
    __DIR__ . '/../controllers/category_controller.php',
    __DIR__ . '/../../controllers/category_controller.php',
    __DIR__ . '/category_controller.php'
];

foreach ($try_paths as $p) {
    if (file_exists($p)) {
        require_once $p;
        $included = true;
        break;
    }
}

if (!$included) {
    error_log("fetch_category_action.php: cannot find controllers/category_controller.php");
    json_response(false, 'Server configuration error: controller not found.');
}

// Ensure class exists
if (!class_exists('CategoryController')) {
    error_log("fetch_category_action.php: CategoryController class not found.");
    json_response(false, 'Server error: controller missing.');
}

// Fetch categories
try {
    $controller = new CategoryController();
    $result = $controller->get_categories_ctr($customer_id);

    if ($result['success']) {
        json_response(true, $result['message'], ['categories' => $result['categories']]);
    } else {
        json_response(false, $result['message'], ['categories' => []]);
    }
} catch (Exception $ex) {
    error_log("fetch_category_action.php Exception: " . $ex->getMessage());
    json_response(false, 'Server error while fetching categories.', ['categories' => []]);
}

