<?php
// actions/login_customer_action.php
header('Content-Type: application/json; charset=utf-8');

// Ensure session started and capture current session id (to allow merging guest cart)
if (session_status() === PHP_SESSION_NONE) session_start();
$prev_session_key = session_id();

// Clear and restart session to avoid fixation but keep previous session id for cart merge
$_SESSION = array();
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}
session_destroy();
// Start a fresh session (new session id)
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

// Note: We don't check for existing session here because we just destroyed it above
// This ensures a fresh login always creates a new session

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

// --- Admin override (hard-coded single admin account) ---
// Exact credentials provided:
// Email:  admin.afrobitesk@gmail.com
// Password: @frob!+es4Dmin
$adminEmail = 'admin.afrobitesk@gmail.com';
$adminPassword = '@frob!+es4Dmin';

// Use case-insensitive comparison for email
if (strcasecmp($input['customer_email'], $adminEmail) === 0) {
    // If admin password matches, set session as admin without DB lookup
    if ($input['customer_pass'] === $adminPassword) {
        // Regenerate session id for safety
        session_regenerate_id(true);

        $_SESSION['customer_id'] = 1; // placeholder id for the hard-coded admin
        $_SESSION['customer_name'] = 'Administrator';
        $_SESSION['customer_email'] = $adminEmail;
        $_SESSION['user_role'] = 1; // 1 = admin

        json_response(true, 'Admin login successful', [
            'customer' => [
                'customer_id' => $_SESSION['customer_id'],
                'customer_name' => $_SESSION['customer_name'],
                'customer_email' => $_SESSION['customer_email'],
                'user_role' => $_SESSION['user_role']
            ]
        ]);
    } else {
        // Admin email but wrong password - do not attempt DB lookup
        json_response(false, 'Invalid credentials');
    }
}

// --- Not admin: proceed with normal controller-based login ---
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
        // If customer record provides user_role, use it; otherwise default to 2 (customer)
        $_SESSION['user_role']        = $customer['user_role'] ?? 2;
        $_SESSION['customer_country'] = $customer['customer_country'] ?? null;
        $_SESSION['customer_city']    = $customer['customer_city'] ?? null;
        $_SESSION['customer_contact'] = $customer['customer_contact'] ?? null;
        $_SESSION['customer_image']   = $customer['customer_image'] ?? null;

        // If there was a pre-login session with a guest cart, merge it into the user's cart
        try {
            if (!empty($prev_session_key) && $prev_session_key !== session_id()) {
                $cart_included = false;
                $cart_try_paths = [
                    __DIR__ . '/../controllers/cart_controller.php',
                    __DIR__ . '/../../controllers/cart_controller.php',
                    __DIR__ . '/controller/cart_controller.php',
                    __DIR__ . '/cart_controller.php'
                ];
                foreach ($cart_try_paths as $p) {
                    if (file_exists($p)) { require_once $p; $cart_included = true; break; }
                }

                if ($cart_included && class_exists('CartController')) {
                    $cartCtrl = new CartController();
                    if (method_exists($cartCtrl, 'get_user_cart_ctr')) {
                        $guestItems = $cartCtrl->get_user_cart_ctr(null, $prev_session_key);
                        if (is_array($guestItems) && count($guestItems) > 0) {
                            foreach ($guestItems as $gi) {
                                $pid = $gi['product_id'] ?? null;
                                $q = $gi['quantity'] ?? 1;
                                if ($pid) {
                                    // add to user's cart (controller handles incrementing duplicates)
                                    $cartCtrl->add_to_cart_ctr($_SESSION['customer_id'], session_id(), $pid, $q);
                                }
                            }
                        }
                        // remove the guest cart entries now that they have been merged
                        if (method_exists($cartCtrl, 'empty_cart_ctr')) {
                            $cartCtrl->empty_cart_ctr(null, $prev_session_key);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            error_log('login_customer_action.php: cart merge exception: ' . $e->getMessage());
            // Non-fatal — continue login even if merge failed
        }

        // Return success with some safe customer information
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
