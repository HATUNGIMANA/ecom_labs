<?php
// actions/empty_cart_action.php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../controllers/cart_controller.php';

$customer_id = isset($_SESSION['customer_id']) ? (int)$_SESSION['customer_id'] : null;
$session_key = session_id();

$ctrl = new CartController();
$res = $ctrl->empty_cart_ctr($customer_id, $session_key);

echo json_encode($res);
