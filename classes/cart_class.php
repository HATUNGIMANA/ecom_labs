<?php
require_once __DIR__ . '/../settings/db_class.php';

class cart_class {
    public $db = null;
    public $last_error = '';

    public function __construct() {
        $dbc = new db_connection();
        // prefer returning the mysqli object; db_conn returns the mysqli or false
        $conn = $dbc->db_conn();
        if ($conn === false) {
            // try older method then grab property if set
            $dbc->db_connect();
            $conn = $dbc->db;
        }
        $this->db = $conn;
    }

    protected function is_connected() {
        if (!$this->db) return false;
        // ensure we have a mysqli instance
        if (!($this->db instanceof mysqli)) return false;
        if ($this->db->connect_errno) return false;
        return true;
    }

    // Add product to cart. If exists, increment quantity.
    public function add_to_cart($customer_id, $session_key, $product_id, $quantity = 1, $unit_price = null) {
        if (!$this->is_connected()) { $this->last_error = 'DB connection failed'; return false; }
        $product_id = (int)$product_id;
        $quantity = max(1, (int)$quantity);

        // decide lookup field
        if (!empty($customer_id)) {
            $sql = "SELECT cart_id, quantity FROM carts WHERE customer_id = ? AND product_id = ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('ii', $customer_id, $product_id);
        } else {
            $sql = "SELECT cart_id, quantity FROM carts WHERE session_key = ? AND product_id = ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('si', $session_key, $product_id);
        }
        if (!$stmt) { $this->last_error = 'Prepare failed'; return false; }
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();

        if ($row) {
            // update quantity
            $newQty = $row['quantity'] + $quantity;
            $updSql = "UPDATE carts SET quantity = ? WHERE cart_id = ?";
            $ust = $this->db->prepare($updSql);
            $ust->bind_param('ii', $newQty, $row['cart_id']);
            if ($ust->execute()) { $ust->close(); return true; }
            $this->last_error = 'Update failed: ' . $ust->error;
            $ust->close();
            return false;
        } else {
            // insert
            // determine unit price snapshot if not provided
            if ($unit_price === null) {
                // attempt to get product price
                $pstmt = $this->db->prepare('SELECT product_price FROM products WHERE product_id = ? LIMIT 1');
                $pstmt->bind_param('i', $product_id);
                $pstmt->execute();
                $pr = $pstmt->get_result()->fetch_assoc();
                $unit_price = $pr ? (float)$pr['product_price'] : 0.00;
                $pstmt->close();
            }
            $insSql = "INSERT INTO carts (customer_id, session_key, product_id, quantity, unit_price) VALUES (?, ?, ?, ?, ?)";
            $ist = $this->db->prepare($insSql);
            if ($ist) {
                if (!empty($customer_id)) {
                    // customer_id (i), session_key (s), product_id (i), quantity (i), unit_price (d)
                    $ist->bind_param('isiid', $customer_id, $session_key, $product_id, $quantity, $unit_price);
                } else {
                    // insert with NULL customer_id explicitly by using NULL in SQL
                    $ist->close();
                    $insSql2 = "INSERT INTO carts (customer_id, session_key, product_id, quantity, unit_price) VALUES (NULL, ?, ?, ?, ?)";
                    $ist2 = $this->db->prepare($insSql2);
                    if ($ist2) {
                        $ist2->bind_param('siid', $session_key, $product_id, $quantity, $unit_price);
                        $res2 = $ist2->execute();
                        if ($res2) { $ist2->close(); return true; }
                        $this->last_error = 'Execute failed: ' . $ist2->error;
                        $ist2->close();
                        return false;
                    }
                    // if prepare failed fall through to fallback
                }
                // execute prepared statement for customer case
                $res = $ist->execute();
                if ($res) { $ist->close(); return true; }
                $this->last_error = 'Execute failed: ' . $ist->error;
                $ist->close();
                return false;
            }
            // fallback simple insert if prepare not available
            $sql2 = "INSERT INTO carts (customer_id, session_key, product_id, quantity, unit_price) VALUES (" .
                ($customer_id ? (int)$customer_id : 'NULL') . ", '" . $this->db->real_escape_string($session_key) . "', " . (int)$product_id . ", " . (int)$quantity . ", " . (float)$unit_price . ")";
            if ($this->db->query($sql2)) return true;
            $this->last_error = 'Insert failed: ' . $this->db->error;
            return false;
        }
    }

    public function update_quantity($cart_id, $qty) {
        if (!$this->is_connected()) { $this->last_error = 'DB connection failed'; return false; }
        $cart_id = (int)$cart_id; $qty = max(1, (int)$qty);
        $stmt = $this->db->prepare('UPDATE carts SET quantity = ? WHERE cart_id = ?');
        if (!$stmt) { $this->last_error = 'Prepare failed'; return false; }
        $stmt->bind_param('ii', $qty, $cart_id);
        $res = $stmt->execute();
        if (!$res) $this->last_error = 'Execute failed: ' . $stmt->error;
        $stmt->close();
        return $res;
    }

    public function remove_item($cart_id) {
        if (!$this->is_connected()) { $this->last_error = 'DB connection failed'; return false; }
        $cart_id = (int)$cart_id;
        $stmt = $this->db->prepare('DELETE FROM carts WHERE cart_id = ?');
        if (!$stmt) { $this->last_error = 'Prepare failed'; return false; }
        $stmt->bind_param('i', $cart_id);
        $res = $stmt->execute();
        if (!$res) $this->last_error = 'Execute failed: ' . $stmt->error;
        $stmt->close();
        return $res;
    }

    public function empty_cart($customer_id, $session_key) {
        if (!$this->is_connected()) { $this->last_error = 'DB connection failed'; return false; }
        if (!empty($customer_id)) {
            $sql = "DELETE FROM carts WHERE customer_id = " . (int)$customer_id;
        } else {
            $sql = "DELETE FROM carts WHERE session_key = '" . $this->db->real_escape_string($session_key) . "'";
        }
        return $this->db->query($sql);
    }

    public function get_cart_items($customer_id, $session_key) {
        if (!$this->is_connected()) { $this->last_error = 'DB connection failed'; return []; }
        // If user is logged in, return items belonging to that user OR items tied to the current session_key
        // This helps surface any session-scoped items that haven't yet been merged and avoids showing other users' guest carts.
        $escaped_session = $this->db->real_escape_string($session_key);
        if (!empty($customer_id)) {
            $sql = "SELECT c.cart_id, c.product_id, c.quantity, c.unit_price, p.product_title, p.product_image
                    FROM carts c LEFT JOIN products p ON c.product_id = p.product_id
                    WHERE c.customer_id = " . (int)$customer_id . " ";
            if (!empty($session_key)) {
                $sql .= " OR c.session_key = '" . $escaped_session . "' ";
            }
            $sql .= " ORDER BY c.added_at DESC";
        } else {
            $sql = "SELECT c.cart_id, c.product_id, c.quantity, c.unit_price, p.product_title, p.product_image
                    FROM carts c LEFT JOIN products p ON c.product_id = p.product_id
                    WHERE c.session_key = '" . $escaped_session . "' ORDER BY c.added_at DESC";
        }
        $res = $this->db->query($sql);
        if (!$res) return [];
        $out = [];
        while ($r = $res->fetch_assoc()) $out[] = $r;
        return $out;
    }
}
