<?php
// actions/update_quantity_action.php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../controllers/cart_controller.php';

$cart_id = isset($_POST['cart_id']) ? (int)$_POST['cart_id'] : 0;
$qty = isset($_POST['qty']) ? (int)$_POST['qty'] : 1;

$ctrl = new CartController();
$res = $ctrl->update_cart_item_ctr($cart_id, $qty);

echo json_encode($res);
