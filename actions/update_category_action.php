<?php
// actions/update_category_action.php
header('Content-Type: application/json; charset=utf-8');

// Start session
session_start();

// Helper to return JSON then exit
function json_response(bool $success, string $message, array $extra = []) {
    $payload = array_merge(['success' => $success, 'message' => $message], $extra);
    echo json_encode($payload);
    exit;
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Invalid request method. Use POST.');
}

// Check if user is logged in
if (!isset($_SESSION['customer_id'])) {
    json_response(false, 'You must be logged in to update categories.');
}

// Collect and validate input
if (!isset($_POST['cat_id']) || !is_numeric($_POST['cat_id'])) {
    json_response(false, 'Category ID is required and must be numeric.');
}

if (!isset($_POST['cat_name']) || trim($_POST['cat_name']) === '') {
    json_response(false, 'Category name is required.');
}

$cat_id = (int)$_POST['cat_id'];
$cat_name = trim($_POST['cat_name']);

// Validate category name length (max 100 chars per DB)
if (mb_strlen($cat_name) > 100) {
    json_response(false, 'Category name too long (max 100 characters).');
}

// Validate category name format
if (!preg_match('/^[a-zA-Z0-9\s\-_]+$/', $cat_name)) {
    json_response(false, 'Category name can only contain letters, numbers, spaces, hyphens, and underscores.');
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
    error_log("update_category_action.php: cannot find controllers/category_controller.php");
    json_response(false, 'Server configuration error: controller not found.');
}

// Ensure class exists
if (!class_exists('CategoryController')) {
    error_log("update_category_action.php: CategoryController class not found.");
    json_response(false, 'Server error: controller missing.');
}

// Prepare data for controller
$category_data = [
    'cat_name' => $cat_name
];

// Update category
try {
    $controller = new CategoryController();
    $result = $controller->update_category_ctr($cat_id, $category_data);

    if ($result['success']) {
        json_response(true, $result['message']);
    } else {
        json_response(false, $result['message']);
    }
} catch (Exception $ex) {
    error_log("update_category_action.php Exception: " . $ex->getMessage());
    json_response(false, 'Server error while updating category.');
}

