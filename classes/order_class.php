<?php
require_once __DIR__ . '/../settings/db_class.php';

class order_class {
    public $db = null;
    public $last_error = '';

    public function __construct() {
        $dbc = new db_connection();
        $conn = $dbc->db_conn();
        if ($conn === false) {
            $dbc->db_connect();
            $conn = $dbc->db;
        }
        $this->db = $conn;
    }

    protected function is_connected() {
        if (!$this->db) return false;
        if (!($this->db instanceof mysqli)) return false;
        if ($this->db->connect_errno) return false;
        return true;
    }

    public function create_order($customer_id, $total_amount) {
        if (!$this->is_connected()) { $this->last_error = 'DB connection failed'; return false; }
        $order_ref = 'ORDER-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid('', true)),0,6));
        $stmt = $this->db->prepare('INSERT INTO orders (order_ref, customer_id, total_amount, status) VALUES (?, ?, ?, ?)');
        if (!$stmt) { $this->last_error = 'Prepare failed'; return false; }
        $status = 'confirmed';
        $stmt->bind_param('sids', $order_ref, $customer_id, $total_amount, $status);
        if (!$stmt->execute()) { $this->last_error = 'Execute failed: ' . $stmt->error; $stmt->close(); return false; }
        $order_id = $this->db->insert_id;
        $stmt->close();
        return ['order_id' => $order_id, 'order_ref' => $order_ref];
    }

    public function add_order_detail($order_id, $product_id, $qty, $unit_price) {
        if (!$this->is_connected()) { $this->last_error = 'DB connection failed'; return false; }
        $subtotal = $qty * $unit_price;
        $stmt = $this->db->prepare('INSERT INTO order_details (order_id, product_id, unit_price, quantity, subtotal) VALUES (?, ?, ?, ?, ?)');
        if (!$stmt) { $this->last_error = 'Prepare failed'; return false; }
        $stmt->bind_param('iidid', $order_id, $product_id, $unit_price, $qty, $subtotal);
        $res = $stmt->execute();
        if (!$res) $this->last_error = 'Execute failed: '.$stmt->error;
        $stmt->close();
        return $res;
    }

    public function record_payment($order_id, $amount, $payment_ref, $status = 'success') {
        if (!$this->is_connected()) { $this->last_error = 'DB connection failed'; return false; }
        $stmt = $this->db->prepare('INSERT INTO payments (order_id, payment_method, amount, payment_ref, status) VALUES (?, ?, ?, ?, ?)');
        if (!$stmt) { $this->last_error = 'Prepare failed'; return false; }
        $method = 'SIMULATED';
        $stmt->bind_param('isdss', $order_id, $method, $amount, $payment_ref, $status);
        $res = $stmt->execute();
        if (!$res) $this->last_error = 'Execute failed: '.$stmt->error;
        $stmt->close();
        return $res;
    }

    public function get_orders_by_user($customer_id) {
        if (!$this->is_connected()) return [];
        $sql = 'SELECT * FROM orders WHERE customer_id = ' . (int)$customer_id . ' ORDER BY created_at DESC';
        $res = $this->db->query($sql);
        if (!$res) return [];
        $out = [];
        while ($r = $res->fetch_assoc()) $out[] = $r;
        return $out;
    }
}
