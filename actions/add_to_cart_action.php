<?php
// actions/add_to_cart_action.php
session_start();
header('Content-Type: application/json');

// Simple error handling wrapper to ensure valid JSON responses
ob_start();
set_error_handler(function($errno, $errstr, $errfile, $errline){
	$msg = "PHP Error [$errno]: $errstr in $errfile on line $errline";
	error_log($msg . "\n", 3, __DIR__ . '/../logs/cart_actions.log');
	// don't execute PHP internal handler
	return true;
});
set_exception_handler(function($ex){
	$msg = "Uncaught Exception: " . $ex->getMessage();
	error_log($msg . "\n", 3, __DIR__ . '/../logs/cart_actions.log');
	http_response_code(500);
	echo json_encode(['success' => false, 'message' => 'Internal server error']);
	exit;
});

try {
	require_once __DIR__ . '/../settings/core.php';
	require_once __DIR__ . '/../controllers/cart_controller.php';

	$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
	$qty = isset($_POST['qty']) ? (int)$_POST['qty'] : 1;

	$customer_id = isset($_SESSION['customer_id']) ? (int)$_SESSION['customer_id'] : null;
	$session_key = session_id();

	$ctrl = new CartController();
	$res = $ctrl->add_to_cart_ctr($customer_id, $session_key, $product_id, $qty);

	// clear any buffered output (warnings etc.) and respond with JSON
	if (ob_get_length()) ob_clean();
	echo json_encode($res);
} catch (Throwable $e) {
	if (ob_get_length()) ob_clean();
	$msg = 'Exception in add_to_cart_action: ' . $e->getMessage();
	error_log($msg . "\n" . $e->getTraceAsString() . "\n", 3, __DIR__ . '/../logs/cart_actions.log');
	http_response_code(500);
	echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
