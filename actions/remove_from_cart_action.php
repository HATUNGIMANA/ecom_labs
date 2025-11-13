<?php
// actions/remove_from_cart_action.php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../controllers/cart_controller.php';

$cart_id = isset($_POST['cart_id']) ? (int)$_POST['cart_id'] : 0;
$ctrl = new CartController();
$res = $ctrl->remove_from_cart_ctr($cart_id);

echo json_encode($res);
