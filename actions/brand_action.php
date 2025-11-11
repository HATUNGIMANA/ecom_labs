<?php
// actions/brand_action.php
// Suppress error display to prevent HTML in JSON response
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Start output buffering to catch any unwanted output
ob_start();

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) session_start();

// Simple JSON helper
function json_response(bool $ok, string $msg, array $extra = []) {
    // Clean any output buffer before sending JSON
    while (ob_get_level()) {
        ob_end_clean();
    }
    $payload = array_merge(['success' => $ok, 'message' => $msg], $extra);
    echo json_encode($payload);
    exit;
}

// Must be POST and have action
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Invalid request method. Use POST.');
}
$action = $_POST['action'] ?? null;
if (!$action) json_response(false, 'Missing action parameter.');

// include core to check session & admin
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
if (!$foundCore) {
    json_response(false, 'Server error: core settings not found.');
}

// Note: allow the 'fetch' action to be called publicly (used to populate brand lists
// on admin product pages). Only enforce auth for mutating actions below.

// include controller
$ctrl_paths = [
    __DIR__ . '/../controllers/brand_controller.php',
    __DIR__ . '/../../controllers/brand_controller.php',
    __DIR__ . '/controllers/brand_controller.php'
];
$foundCtrl = false;
foreach ($ctrl_paths as $p) {
    if (file_exists($p)) {
        require_once $p;
        $foundCtrl = true;
        break;
    }
}
if (!$foundCtrl || !class_exists('BrandController')) {
    json_response(false, 'Server error: brand controller not found.');
}

try {
    $controller = new BrandController();
} catch (Exception $e) {
    error_log('brand_action initialization error: ' . $e->getMessage());
    // also write to a file in actions/ for easier host diagnostics
    @file_put_contents(__DIR__ . '/brand_action_error.log', '['.date('c').'] init exception: '. $e->getMessage() ."\n". $e->getTraceAsString() ."\n\n", FILE_APPEND | LOCK_EX);
    json_response(false, 'Failed to initialize controller: ' . $e->getMessage());
}

// Switch on action
try {
    $actionLower = strtolower($action);

    // Enforce auth for mutating actions only
    if (in_array($actionLower, ['add','update','delete'])) {
        if (!function_exists('is_logged_in') || !function_exists('is_admin')) {
            json_response(false, 'Server misconfiguration: auth helpers missing.');
        }
        if (!is_logged_in()) json_response(false, 'Not authenticated. Please login.');
        if (!is_admin()) json_response(false, 'Unauthorized. Admin access required.');
    }

    switch ($actionLower) {
        case 'fetch':
            // fetch all brands created by the current admin (or all)
            $userId = $_SESSION['customer_id'] ?? null;
            $result = $controller->fetch_brands_ctr($userId);
            json_response(true, 'Brands fetched', ['data' => $result]);
            break;

        case 'add':
            $brand_name = trim($_POST['brand_name'] ?? '');
            $cat_id = intval($_POST['cat_id'] ?? 0);
            if ($brand_name === '' || $cat_id <= 0) {
                json_response(false, 'brand_name and cat_id are required');
            }
            // payload
            $payload = [
                'brand_name' => $brand_name,
                'cat_id' => $cat_id,
                'created_by' => $_SESSION['customer_id'] ?? null
            ];
            $res = $controller->add_brand_ctr($payload);
            if ($res['success']) json_response(true, $res['message'], ['brand_id' => $res['brand_id'] ?? null]);
            json_response(false, $res['message'] ?? 'Failed to add brand');
            break;

        case 'update':
            $brand_id = intval($_POST['brand_id'] ?? 0);
            $brand_name = trim($_POST['brand_name'] ?? '');
            if ($brand_id <= 0 || $brand_name === '') json_response(false, 'brand_id and brand_name are required');
            $payload = ['brand_id' => $brand_id, 'brand_name' => $brand_name, 'updated_by' => $_SESSION['customer_id'] ?? null];
            $res = $controller->update_brand_ctr($payload);
            if ($res['success']) json_response(true, $res['message']);
            json_response(false, $res['message'] ?? 'Failed to update brand');
            break;

        case 'delete':
            $brand_id = intval($_POST['brand_id'] ?? 0);
            if ($brand_id <= 0) json_response(false, 'brand_id is required');
            $res = $controller->delete_brand_ctr($brand_id, $_SESSION['customer_id'] ?? null);
            if ($res['success']) json_response(true, $res['message']);
            json_response(false, $res['message'] ?? 'Failed to delete brand');
            break;

        default:
            json_response(false, 'Unknown action: ' . htmlspecialchars($action));
    }
} catch (Exception $ex) {
    // Log full trace for host debugging and return a helpful message to the admin
    error_log("brand_action.php Exception: " . $ex->getMessage() . "\n" . $ex->getTraceAsString());
    @file_put_contents(__DIR__ . '/brand_action_error.log', '['.date('c').'] exception: '. $ex->getMessage() ."\n". $ex->getTraceAsString() ."\n\n", FILE_APPEND | LOCK_EX);
    // Return the exception message in JSON so the frontend can show it (admin only)
    $msg = 'Server error. ' . $ex->getMessage();
    json_response(false, $msg);
}
