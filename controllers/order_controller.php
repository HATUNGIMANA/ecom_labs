<?php
require_once __DIR__ . '/../classes/order_class.php';

class OrderController {
    protected $order;
    public function __construct() { $this->order = new order_class(); }

    public function create_order_ctr($customer_id, $total_amount) {
        return $this->order->create_order($customer_id, $total_amount);
    }

    public function add_order_detail_ctr($order_id, $product_id, $qty, $unit_price) {
        return $this->order->add_order_detail($order_id, $product_id, $qty, $unit_price);
    }

    public function record_payment_ctr($order_id, $amount, $payment_ref, $status = 'success') {
        return $this->order->record_payment($order_id, $amount, $payment_ref, $status);
    }
}
