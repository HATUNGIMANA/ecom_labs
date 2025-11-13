<?php
// actions/get_cart_action.php
session_start();
header('Content-Type: application/json');

ob_start();
set_error_handler(function($errno, $errstr, $errfile, $errline){
    $msg = "PHP Error [$errno]: $errstr in $errfile on line $errline";
    error_log($msg . "\n", 3, __DIR__ . '/../logs/cart_actions.log');
    return true;
});
set_exception_handler(function($ex){
    $msg = "Uncaught Exception: " . $ex->getMessage();
    error_log($msg . "\n", 3, __DIR__ . '/../logs/cart_actions.log');
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Internal server error']);
    exit;
});

try {
    require_once __DIR__ . '/../settings/core.php';
    require_once __DIR__ . '/../controllers/cart_controller.php';

    $customer_id = isset($_SESSION['customer_id']) ? (int)$_SESSION['customer_id'] : null;
    $session_key = session_id();

    $ctrl = new CartController();
    $items = $ctrl->get_user_cart_ctr($customer_id, $session_key);

    $count = is_array($items) ? count($items) : 0;
    $total = 0.0;
    foreach ($items as $it) {
        $total += (isset($it['unit_price']) ? (float)$it['unit_price'] : 0.0) * (isset($it['quantity']) ? (int)$it['quantity'] : 0);
    }

    if (ob_get_length()) ob_clean();
    echo json_encode(['status' => 'success', 'count' => $count, 'total' => $total, 'items' => $items]);
} catch (Throwable $e) {
    if (ob_get_length()) ob_clean();
    $msg = 'Exception in get_cart_action: ' . $e->getMessage();
    error_log($msg . "\n" . $e->getTraceAsString() . "\n", 3, __DIR__ . '/../logs/cart_actions.log');
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Internal server error']);
}
