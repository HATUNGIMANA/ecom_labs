<?php
// actions/process_checkout_action.php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../controllers/cart_controller.php';
require_once __DIR__ . '/../controllers/order_controller.php';

$customer_id = isset($_SESSION['customer_id']) ? (int)$_SESSION['customer_id'] : null;
$session_key = session_id();

$cartCtrl = new CartController();
$orderCtrl = new OrderController();

$items = $cartCtrl->get_user_cart_ctr($customer_id, $session_key);
if (!$items || count($items) == 0) {
    echo json_encode(['status' => 'error', 'message' => 'Cart is empty']);
    exit;
}

// compute totals server-side
$total = 0.0;
foreach ($items as $it) {
    $price = isset($it['unit_price']) ? (float)$it['unit_price'] : 0.0;
    $qty = isset($it['quantity']) ? (int)$it['quantity'] : 0;
    $total += $price * $qty;
}

// create order
$order = $orderCtrl->create_order_ctr($customer_id, $total);
if (!$order || !isset($order['order_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to create order']);
    exit;
}
$order_id = $order['order_id'];
$order_ref = $order['order_ref'];

$okAll = true;
foreach ($items as $it) {
    $pid = (int)$it['product_id'];
    $qty = (int)$it['quantity'];
    $uprice = (float)$it['unit_price'];
    $ok = $orderCtrl->add_order_detail_ctr($order_id, $pid, $qty, $uprice);
    if (!$ok) { $okAll = false; break; }
}

if (!$okAll) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to save order details']);
    exit;
}

// record simulated payment
$payment_ref = 'PAY-' . strtoupper(substr(md5(uniqid('', true)),0,8));
$paid = $orderCtrl->record_payment_ctr($order_id, $total, $payment_ref, 'success');
if (!$paid) {
    echo json_encode(['status' => 'error', 'message' => 'Payment recording failed']);
    exit;
}

// empty cart
$empt = $cartCtrl->empty_cart_ctr($customer_id, $session_key);

echo json_encode(['status' => 'success', 'order_ref' => $order_ref, 'order_id' => $order_id, 'message' => 'Order processed']);
