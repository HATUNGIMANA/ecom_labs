<?php
// actions/register_customer_action.php
// Robust register action: validates input, checks email, hashes password, inserts user.
// Returns JSON. Designed to be easy to debug during development.

header('Content-Type: application/json; charset=utf-8');

// --- DEV: show errors while debugging (remove or set to 0 in production) ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

// Try to include the Customer class. Support possible folder layouts.
$included = false;
$try_paths = [
    __DIR__ . '/../classes/customer_class.php',
    __DIR__ . '/../../classes/customer_class.php',
    __DIR__ . '/customer_class.php'
];

foreach ($try_paths as $p) {
    if (file_exists($p)) {
        require_once $p;
        $included = true;
        break;
    }
}

if (!$included) {
    error_log("register_customer_action.php: cannot find classes/customer_class.php (paths tried): " . implode(", ", $try_paths));
    json_response(false, 'Server configuration error: user model not found. Check server logs.');
}

// Ensure class exists
if (!class_exists('customer_class')) {
    error_log("register_customer_action.php: customer_class not found after include.");
    json_response(false, 'Server error: user model missing. Check server logs.');
}

// --- Reserved admin email (do not allow registration) ---
$reservedAdminEmail = 'admin.afrobitesk@gmail.com';

// Collect + server-side validation
$required = [
    'customer_name',
    'customer_email',
    'customer_pass',
    'customer_pass_confirm',
    'customer_country',
    'customer_city',
    'customer_contact'
];

$input = [];
foreach ($required as $field) {
    if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
        json_response(false, "Field {$field} is required");
    }
    $input[$field] = trim($_POST[$field]);
}

// Basic validations
if (!filter_var($input['customer_email'], FILTER_VALIDATE_EMAIL)) {
    json_response(false, 'Invalid email format');
}

// Block registration of reserved admin email (case-insensitive)
if (strcasecmp($input['customer_email'], $reservedAdminEmail) === 0) {
    json_response(false, 'This email address is reserved and cannot be registered.');
}

// Contact pattern - allow optional leading + then 7-15 digits
if (!preg_match('/^\+?\d{7,15}$/', $input['customer_contact'])) {
    json_response(false, 'Contact number must be 7-15 digits (optional leading +).');
}

// Password checks
if (strlen($input['customer_pass']) < 8) {
    json_response(false, 'Password must be at least 8 characters');
}
if ($input['customer_pass'] !== $input['customer_pass_confirm']) {
    json_response(false, 'Password and confirm password do not match');
}

// Trim and enforce DB lengths (adjust lengths if your DB differs)
if (mb_strlen($input['customer_name']) > 100) json_response(false, 'Name too long (max 100 chars)');
if (mb_strlen($input['customer_email']) > 100) json_response(false, 'Email too long (max 100 chars)');
if (mb_strlen($input['customer_country']) > 50) json_response(false, 'Country too long (max 50 chars)');
if (mb_strlen($input['customer_city']) > 50) json_response(false, 'City too long (max 50 chars)');
if (mb_strlen($input['customer_contact']) > 15) json_response(false, 'Contact number must be 7-15 digits');

// user_role: default to 2 (customer). Prevent client from assigning admin role via form.
$user_role = 2;
if (isset($_POST['user_role']) && in_array((int)$_POST['user_role'], [1,2], true)) {
    $requested_role = (int)$_POST['user_role'];
    // Always force non-admin unless you have a secure flow to assign admins
    $user_role = ($requested_role === 1) ? 2 : 2;
}

try {
    $customerModel = new customer_class();

    // Check uniqueness (email)
    if (method_exists($customerModel, 'check_email_exists')) {
        if ($customerModel->check_email_exists($input['customer_email'])) {
            json_response(false, 'Email already in use');
        }
    } else {
        // If model lacks this helper, perform a defensive attempt (method missing)
        error_log("register_customer_action.php: customer_class::check_email_exists() not found. Please implement this helper.");
        // Proceed cautiously: you may want to reject to avoid duplicates, but here we fail with a server message
        json_response(false, 'Server configuration error: email check not available. Check server logs.');
    }

    // Hash password
    $hashed = password_hash($input['customer_pass'], PASSWORD_DEFAULT);
    if ($hashed === false) {
        json_response(false, 'Password hashing failed');
    }

    // Prepare payload
    $payload = [
        'customer_name'    => $input['customer_name'],
        'customer_email'   => $input['customer_email'],
        'customer_pass'    => $hashed,
        'customer_country' => $input['customer_country'],
        'customer_city'    => $input['customer_city'],
        'customer_contact' => $input['customer_contact'],
        'customer_image'   => null,
        'user_role'        => $user_role
    ];

    // Add customer using model's add method
    if (!method_exists($customerModel, 'add_customer')) {
        error_log("register_customer_action.php: customer_class::add_customer() not found. Methods: " . implode(', ', get_class_methods($customerModel)));
        json_response(false, 'Server configuration error: cannot add user. Check server logs.');
    }

    $newId = $customerModel->add_customer($payload);

    if ($newId) {
        // Success. Return the new id (safe to return numeric id)
        json_response(true, 'Registration successful', ['customer_id' => (int)$newId]);
    } else {
        json_response(false, 'Registration failed (no id returned).');
    }
} catch (Exception $ex) {
    // Log details to server log for debugging
    error_log("register_customer_action.php Exception: " . $ex->getMessage() . "\n" . $ex->getTraceAsString());
    // Return a generic message to client
    json_response(false, 'Server error during registration. Check server logs.');
}
