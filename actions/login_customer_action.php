<?php
// actions/login_customer_action.php
header('Content-Type: application/json; charset=utf-8');

// Start (or resume) session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

// If already logged in, return success (so frontend does not treat it as error)
if (isset($_SESSION['customer_id']) && !empty($_SESSION['customer_id'])) {
    $info = [
        'customer_id' => $_SESSION['customer_id'],
        'customer_name' => $_SESSION['customer_name'] ?? null,
        'customer_email' => $_SESSION['customer_email'] ?? null,
        'user_role' => $_SESSION['user_role'] ?? null
    ];
    json_response(true, 'Already logged in', ['customer' => $info]);
}

// Try to include the customer controller (support several likely paths)
$included = false;
$try_paths = [
    __DIR__ . '/../controllers/customer_controller.php',
    __DIR__ . '/../../controllers/customer_controller.php',
    __DIR__ . '/controller/customer_controller.php',
    __DIR__ . '/customer_controller.php'
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

if (!filter_var($input['customer_email'], FILTER_VALIDATE_EMAIL)) {
    json_response(false, 'Invalid email format');
}

// Prepare data for controller
$login_data = [
    'customer_email' => $input['customer_email'],
    'customer_pass'  => $input['customer_pass']
];

// Attempt login via controller
try {
    $controller = new CustomerController();

    if (!method_exists($controller, 'login_customer_ctr')) {
        // Some implementations may use a different method name; check alternatives
        error_log("login_customer_action.php: CustomerController::login_customer_ctr() not found. Methods: " . implode(', ', get_class_methods($controller)));
        json_response(false, 'Server error: login method not available. Check server logs.');
    }

    $result = $controller->login_customer_ctr($login_data);

    // Expect $result to be like ['success'=>true/false, 'message'=>..., 'customer'=>[...]]
    if (!is_array($result) || !array_key_exists('success', $result)) {
        error_log("login_customer_action.php: Unexpected login result: " . var_export($result, true));
        json_response(false, 'Server error during login. Check server logs.');
    }

    if ($result['success']) {
        // Ensure customer data exists
        $customer = $result['customer'] ?? null;
        if (!is_array($customer) || empty($customer['customer_id'])) {
            error_log("login_customer_action.php: Successful login but missing customer data: " . var_export($result, true));
            json_response(false, 'Server error: missing user data after login. Check server logs.');
        }

        // Regenerate session ID to prevent fixation
        session_regenerate_id(true);

        // Set session variables (defensive)
        $_SESSION['customer_id']      = $customer['customer_id'];
        $_SESSION['customer_name']    = $customer['customer_name'] ?? null;
        $_SESSION['customer_email']   = $customer['customer_email'] ?? null;
        $_SESSION['user_role']        = $customer['user_role'] ?? null;
        $_SESSION['customer_country'] = $customer['customer_country'] ?? null;
        $_SESSION['customer_city']    = $customer['customer_city'] ?? null;
        $_SESSION['customer_contact'] = $customer['customer_contact'] ?? null;
        $_SESSION['customer_image']   = $customer['customer_image'] ?? null;

        // Return success with some safe customer info
        json_response(true, $result['message'] ?? 'Login successful', [
            'customer' => [
                'customer_id' => $_SESSION['customer_id'],
                'customer_name' => $_SESSION['customer_name'],
                'customer_email' => $_SESSION['customer_email'],
                'user_role' => $_SESSION['user_role']
            ]
        ]);
    } else {
        // Login failed (bad credentials or other reason)
        json_response(false, $result['message'] ?? 'Invalid credentials');
    }
} catch (Exception $ex) {
    error_log("login_customer_action.php Exception: " . $ex->getMessage() . "\n" . $ex->getTraceAsString());
    json_response(false, 'Server error during login. Check server logs.');
}
