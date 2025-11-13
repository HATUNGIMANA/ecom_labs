<?php
require_once __DIR__ . '/../classes/cart_class.php';

class CartController {
    protected $cart;
    public function __construct() { $this->cart = new cart_class(); }

    public function add_to_cart_ctr($customer_id, $session_key, $product_id, $qty = 1) {
        $ok = $this->cart->add_to_cart($customer_id, $session_key, $product_id, $qty);
        return ['success' => $ok, 'message' => $ok ? 'Added to cart' : $this->cart->last_error];
    }

    public function update_cart_item_ctr($cart_id, $qty) {
        $ok = $this->cart->update_quantity($cart_id, $qty);
        return ['success' => $ok, 'message' => $ok ? 'Quantity updated' : $this->cart->last_error];
    }

    public function remove_from_cart_ctr($cart_id) {
        $ok = $this->cart->remove_item($cart_id);
        return ['success' => $ok, 'message' => $ok ? 'Removed' : $this->cart->last_error];
    }

    public function get_user_cart_ctr($customer_id, $session_key) {
        $items = $this->cart->get_cart_items($customer_id, $session_key);
        return $items;
    }

    public function empty_cart_ctr($customer_id, $session_key) {
        $ok = $this->cart->empty_cart($customer_id, $session_key);
        return ['success' => $ok, 'message' => $ok ? 'Cart emptied' : $this->cart->last_error];
    }
}
