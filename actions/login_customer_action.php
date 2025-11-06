<?php
// actions/login_customer_action.php
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

// Check if user is already logged in
if (isset($_SESSION['customer_id'])) {
    json_response(false, 'You are already logged in.');
}

// Try to include the customer controller
$included = false;
$try_paths = [
    __DIR__ . '/../controllers/customer_controller.php', // typical
    __DIR__ . '/../../controllers/customer_controller.php', // if actions is deeper
    __DIR__ . '/customer_controller.php' // fallback
];

foreach ($try_paths as $p) {
    if (file_exists($p)) {
        require_once $p;
        $included = true;
        break;
    }
}

if (!$included) {
    error_log("login_customer_action.php: cannot find controllers/customer_controller.php (paths tried): " . implode(", ", $try_paths));
    json_response(false, 'Server configuration error: controller not found. Check server logs.');
}

// Ensure class exists
if (!class_exists('CustomerController')) {
    error_log("login_customer_action.php: CustomerController class not found after include.");
    json_response(false, 'Server error: controller missing. Check server logs.');
}

// Collect and validate input
$required = ['customer_email', 'customer_pass'];
$input = [];

foreach ($required as $field) {
    if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
        json_response(false, "Field {$field} is required");
    }
    $input[$field] = trim($_POST[$field]);
}

// Validate email format
if (!filter_var($input['customer_email'], FILTER_VALIDATE_EMAIL)) {
    json_response(false, 'Invalid email format');
}

// Prepare data for controller
$login_data = [
    'customer_email' => $input['customer_email'],
    'customer_pass' => $input['customer_pass']
];

// Attempt login
try {
    $controller = new CustomerController();
    $result = $controller->login_customer_ctr($login_data);

    if ($result['success']) {
        // Set session variables
        $customer = $result['customer'];
        $_SESSION['customer_id'] = $customer['customer_id'];
        $_SESSION['customer_name'] = $customer['customer_name'];
        $_SESSION['customer_email'] = $customer['customer_email'];
        $_SESSION['user_role'] = $customer['user_role'];
        $_SESSION['customer_country'] = isset($customer['customer_country']) ? $customer['customer_country'] : null;
        $_SESSION['customer_city'] = isset($customer['customer_city']) ? $customer['customer_city'] : null;
        $_SESSION['customer_contact'] = isset($customer['customer_contact']) ? $customer['customer_contact'] : null;
        $_SESSION['customer_image'] = isset($customer['customer_image']) ? $customer['customer_image'] : null;
        
        // Return success
        json_response(true, $result['message'], [
            'customer_id' => $customer['customer_id'],
            'customer_name' => $customer['customer_name']
        ]);
    } else {
        json_response(false, $result['message']);
    }
} catch (Exception $ex) {
    error_log("login_customer_action.php Exception: " . $ex->getMessage() . "\n" . $ex->getTraceAsString());
    json_response(false, 'Server error during login. Check server logs.');
}

