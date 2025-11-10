<?php
/**
 * classes/product_class.php
 *
 * Product class that extends database connection.
 * Robust include logic so the file works even when include paths differ.
 */

//
// Try to include your project's DB helper(s). Use __DIR__ so includes
// are always relative to this file's location. Try several common paths.
//
$db_includes = [
    __DIR__ . '/../settings/db_class.php',
    __DIR__ . '/../settings/db_cred.php',
    __DIR__ . '/../settings/db_credentials.php',
    __DIR__ . '/../../settings/db_class.php',
    __DIR__ . '/../../settings/db_cred.php',
    __DIR__ . '/db_class.php',
    __DIR__ . '/db_cred.php'
];

foreach ($db_includes as $inc) {
    if (file_exists($inc)) {
        @require_once $inc;
        break;
    }
}

/**
 * If your project does not provide a db_connection base class (older projects),
 * define a minimal, safe fallback db_connection class that provides:
 *  - $this->db (mysqli instance)
 *  - db_connect() to initialize $this->db
 *
 * This prevents fatal errors when the expected include path is wrong.
 */
if (!class_exists('db_connection')) {
    class db_connection
    {
        /** @var mysqli|null */
        protected $db = null;

        /**
         * Try to connect using constants if available; otherwise try local defaults.
         * @return bool
         */
        public function db_connect()
        {
            if ($this->db instanceof mysqli) {
                return true;
            }

            // Prefer constants commonly used in projects
            $candidates = [];

            if (defined('DB_HOST') && defined('DB_USER') && defined('DB_NAME')) {
                $candidates[] = [
                    'host' => DB_HOST,
                    'user' => defined('DB_USER') ? DB_USER : '',
                    'pass' => defined('DB_PASS') ? DB_PASS : '',
                    'db'   => DB_NAME
                ];
            }

            // Older constant names sometimes used in this project
            if (defined('SERVER') && defined('USERNAME') && defined('DATABASE')) {
                $candidates[] = [
                    'host' => SERVER,
                    'user' => USERNAME,
                    'pass' => defined('PASSWD') ? PASSWD : '',
                    'db'   => DATABASE
                ];
            }

            // sensible local defaults (XAMPP)
            $candidates[] = ['host' => 'localhost', 'user' => 'root', 'pass' => '', 'db' => 'shoppn'];
            $candidates[] = ['host' => 'localhost', 'user' => 'root', 'pass' => '', 'db' => 'ecommerce_2025A_eric_hatungimana'];

            foreach ($candidates as $c) {
                if (empty($c['host']) || empty($c['user']) || empty($c['db'])) {
                    continue;
                }
                $mysqli = @new mysqli($c['host'], $c['user'], $c['pass'] ?? '', $c['db']);
                if ($mysqli && !$mysqli->connect_errno) {
                    $mysqli->set_charset('utf8mb4');
                    $this->db = $mysqli;
                    return true;
                }
            }

            // last-ditch: try localhost root without DB then fail
            $mysqli = @new mysqli('localhost', 'root', '', '');
            if ($mysqli && !$mysqli->connect_errno) {
                $mysqli->set_charset('utf8mb4');
                $this->db = $mysqli;
                return true;
            }

            return false;
        }
    }
}

/**
 * Product class - provides CRUD and search for products.
 */
class product_class extends db_connection
{
    public $last_error = '';
    /**
     * Add a new product
     * @param array $data
     * @return int|false inserted product_id or false on failure
     */
    public function add($data)
    {
        if (!$this->db_connect()) {
            $this->last_error = 'Database connection failed';
            return false;
        }

        $product_cat = isset($data['product_cat']) ? (int)$data['product_cat'] : 0;
        $product_brand = isset($data['product_brand']) ? (int)$data['product_brand'] : 0;
        $product_title = trim($data['product_title'] ?? '');
        $product_price = isset($data['product_price']) ? (float)$data['product_price'] : 0.0;
        $product_desc = trim($data['product_desc'] ?? '');
        $product_keywords = trim($data['product_keywords'] ?? '');
        $product_image = trim($data['product_image'] ?? '');

        if (empty($product_title) || $product_cat <= 0 || $product_brand <= 0 || $product_price <= 0) {
            return false;
        }

        $sql = "INSERT INTO products (product_cat, product_brand, product_title, product_price, product_desc, product_keywords, product_image)
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            $this->last_error = 'Prepare failed: ' . $this->db->error;
            error_log("product_class::add prepare failed: " . $this->db->error);
            return false;
        }

        // types: i (int), i (int), s (string), d (double), s, s, s
        $stmt->bind_param('iisdsss', $product_cat, $product_brand, $product_title, $product_price, $product_desc, $product_keywords, $product_image);

        if ($stmt->execute()) {
            $newId = $this->db->insert_id;
            $stmt->close();
            return (int)$newId;
        } else {
            $this->last_error = 'Execute failed: ' . $stmt->error;
            error_log("product_class::add execute failed: " . $stmt->error);
            $stmt->close();
            return false;
        }
    }

    /**
     * Get all products (with category + brand)
     * @return array|false
     */
    public function get_all()
    {
        if (!$this->db_connect()) {
            return false;
        }

        $sql = "SELECT p.product_id, p.product_cat, p.product_brand, p.product_title,
                       p.product_price, p.product_desc, p.product_keywords, p.product_image,
                       c.cat_id, c.cat_name,
                       b.brand_id, b.brand_name
                FROM products p
                LEFT JOIN categories c ON p.product_cat = c.cat_id
                LEFT JOIN brands b ON p.product_brand = b.brand_id
                ORDER BY c.cat_name, b.brand_name, p.product_title";

        $res = $this->db->query($sql);
        if (!$res) {
            error_log("product_class::get_all query failed: " . $this->db->error);
            return false;
        }

        $rows = [];
        while ($r = $res->fetch_assoc()) {
            $rows[] = $r;
        }
        $res->free();

        return $rows;
    }

    /**
     * Get products created by a specific user (if created_by exists)
     * @param int|null $user_id
     * @return array|false
     */
    public function get_all_by_user($user_id = null)
    {
        if (!$this->db_connect()) {
            return false;
        }

        // Build base query
        $sql = "SELECT p.product_id, p.product_cat, p.product_brand, p.product_title,
                       p.product_price, p.product_desc, p.product_keywords, p.product_image,
                       c.cat_id, c.cat_name,
                       b.brand_id, b.brand_name
                FROM products p
                LEFT JOIN categories c ON p.product_cat = c.cat_id
                LEFT JOIN brands b ON p.product_brand = b.brand_id";

        // Check if created_by exists in table
        $columns = $this->db->query("SHOW COLUMNS FROM products LIKE 'created_by'");
        if ($columns && $columns->num_rows > 0 && $user_id !== null) {
            $sql .= " WHERE p.created_by = " . (int)$user_id;
        }
        $sql .= " ORDER BY c.cat_name, b.brand_name, p.product_title";

        $res = $this->db->query($sql);
        if (!$res) {
            error_log("product_class::get_all_by_user query failed: " . $this->db->error);
            return false;
        }

        $rows = [];
        while ($r = $res->fetch_assoc()) {
            $rows[] = $r;
        }
        $res->free();

        return $rows;
    }

    /**
     * Update a product
     * @param int $product_id
     * @param array $data
     * @return bool
     */
    public function update($product_id, $data)
    {
        if (!$this->db_connect()) {
            return false;
        }

        $product_id = (int)$product_id;
        if ($product_id <= 0) {
            return false;
        }

        $product_cat = isset($data['product_cat']) ? (int)$data['product_cat'] : 0;
        $product_brand = isset($data['product_brand']) ? (int)$data['product_brand'] : 0;
        $product_title = trim($data['product_title'] ?? '');
        $product_price = isset($data['product_price']) ? (float)$data['product_price'] : 0.0;
        $product_desc = trim($data['product_desc'] ?? '');
        $product_keywords = trim($data['product_keywords'] ?? '');

        if (empty($product_title) || $product_cat <= 0 || $product_brand <= 0 || $product_price <= 0) {
            return false;
        }

        $sql = "UPDATE products SET product_cat = ?, product_brand = ?, product_title = ?, product_price = ?, product_desc = ?, product_keywords = ? WHERE product_id = ?";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log("product_class::update prepare failed: " . $this->db->error);
            return false;
        }

        $stmt->bind_param('iisdssi', $product_cat, $product_brand, $product_title, $product_price, $product_desc, $product_keywords, $product_id);
        $ok = $stmt->execute();
        if (!$ok) {
            error_log("product_class::update execute failed: " . $stmt->error);
        }
        $stmt->close();

        return (bool)$ok;
    }

    /**
     * Delete a product
     * @param int $product_id
     * @param int|null $user_id
     * @return bool
     */
    public function delete($product_id, $user_id = null)
    {
        if (!$this->db_connect()) {
            return false;
        }

        $product_id = (int)$product_id;
        if ($product_id <= 0) {
            return false;
        }

        $stmt = $this->db->prepare("DELETE FROM products WHERE product_id = ?");
        if (!$stmt) {
            error_log("product_class::delete prepare failed: " . $this->db->error);
            return false;
        }
        $stmt->bind_param('i', $product_id);
        $ok = $stmt->execute();
        if (!$ok) {
            error_log("product_class::delete execute failed: " . $stmt->error);
        }
        $stmt->close();

        return (bool)$ok;
    }

    /**
     * View all products for customers (with optional limit/offset)
     * @param int|null $limit
     * @param int $offset
     * @return array|false
     */
    public function view_all_products($limit = null, $offset = 0)
    {
        if (!$this->db_connect()) {
            return false;
        }

        $sql = "SELECT p.product_id, p.product_cat, p.product_brand, p.product_title,
                       p.product_price, p.product_desc, p.product_keywords, p.product_image,
                       c.cat_id, c.cat_name,
                       b.brand_id, b.brand_name
                FROM products p
                LEFT JOIN categories c ON p.product_cat = c.cat_id
                LEFT JOIN brands b ON p.product_brand = b.brand_id
                ORDER BY p.product_title ASC";

        if ($limit !== null && (int)$limit > 0) {
            $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        }

        $res = $this->db->query($sql);
        if (!$res) {
            error_log("product_class::view_all_products query failed: " . $this->db->error);
            return false;
        }

        $rows = [];
        while ($r = $res->fetch_assoc()) {
            $rows[] = $r;
        }
        $res->free();

        return $rows;
    }

    /**
     * Search products by query
     * @param string $query
     * @param int|null $limit
     * @param int $offset
     * @return array|false
     */
    public function search_products($query, $limit = null, $offset = 0)
    {
        if (!$this->db_connect()) {
            return false;
        }

        $searchTerm = trim((string)$query);
        if ($searchTerm === '') {
            return $this->view_all_products($limit, $offset);
        }

        // Use prepared statement with LIKE
        $like = '%' . $this->db->real_escape_string($searchTerm) . '%';

        $sql = "SELECT p.product_id, p.product_cat, p.product_brand, p.product_title,
                       p.product_price, p.product_desc, p.product_keywords, p.product_image,
                       c.cat_id, c.cat_name,
                       b.brand_id, b.brand_name
                FROM products p
                LEFT JOIN categories c ON p.product_cat = c.cat_id
                LEFT JOIN brands b ON p.product_brand = b.brand_id
                WHERE p.product_title LIKE ? OR p.product_keywords LIKE ? OR p.product_desc LIKE ?
                ORDER BY
                    CASE
                        WHEN p.product_title LIKE ? THEN 1
                        WHEN p.product_title LIKE ? THEN 2
                        WHEN p.product_keywords LIKE ? THEN 3
                        ELSE 4
                    END,
                    p.product_title ASC";

        if ($limit !== null && (int)$limit > 0) {
            $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        }

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log("product_class::search_products prepare failed: " . $this->db->error);
            return false;
        }

        // Bind same like pattern multiple times as required by the query
        $likeFirst = $searchTerm . '%'; // for prefix checks
        $likeAny = '%' . $searchTerm . '%';

        // Bind parameters: six strings (three for WHERE, two for CASE prefix checks, one for keywords CASE)
        $stmt->bind_param('sssssss', $likeAny, $likeAny, $likeAny, $likeFirst, $likeAny, $likeAny, $likeAny);
        $ok = $stmt->execute();
        if (!$ok) {
            error_log("product_class::search_products execute failed: " . $stmt->error);
            $stmt->close();
            return false;
        }

        $result = $stmt->get_result();
        $rows = [];
        while ($r = $result->fetch_assoc()) {
            $rows[] = $r;
        }
        $stmt->close();

        return $rows;
    }

    /**
     * Filter products by category
     */
    public function filter_products_by_category($cat_id, $limit = null, $offset = 0)
    {
        if (!$this->db_connect()) {
            return false;
        }

        $cat_id = (int)$cat_id;
        if ($cat_id <= 0) {
            return $this->view_all_products($limit, $offset);
        }

        $sql = "SELECT p.product_id, p.product_cat, p.product_brand, p.product_title,
                       p.product_price, p.product_desc, p.product_keywords, p.product_image,
                       c.cat_id, c.cat_name,
                       b.brand_id, b.brand_name
                FROM products p
                LEFT JOIN categories c ON p.product_cat = c.cat_id
                LEFT JOIN brands b ON p.product_brand = b.brand_id
                WHERE p.product_cat = ?
                ORDER BY p.product_title ASC";

        if ($limit !== null && (int)$limit > 0) {
            $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        }

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log("product_class::filter_products_by_category prepare failed: " . $this->db->error);
            return false;
        }

        $stmt->bind_param('i', $cat_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($r = $res->fetch_assoc()) {
            $rows[] = $r;
        }
        $stmt->close();

        return $rows;
    }

    /**
     * Filter products by brand
     */
    public function filter_products_by_brand($brand_id, $limit = null, $offset = 0)
    {
        if (!$this->db_connect()) {
            return false;
        }

        $brand_id = (int)$brand_id;
        if ($brand_id <= 0) {
            return $this->view_all_products($limit, $offset);
        }

        $sql = "SELECT p.product_id, p.product_cat, p.product_brand, p.product_title,
                       p.product_price, p.product_desc, p.product_keywords, p.product_image,
                       c.cat_id, c.cat_name,
                       b.brand_id, b.brand_name
                FROM products p
                LEFT JOIN categories c ON p.product_cat = c.cat_id
                LEFT JOIN brands b ON p.product_brand = b.brand_id
                WHERE p.product_brand = ?
                ORDER BY p.product_title ASC";

        if ($limit !== null && (int)$limit > 0) {
            $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        }

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log("product_class::filter_products_by_brand prepare failed: " . $this->db->error);
            return false;
        }

        $stmt->bind_param('i', $brand_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($r = $res->fetch_assoc()) {
            $rows[] = $r;
        }
        $stmt->close();

        return $rows;
    }

    /**
     * View single product
     */
    public function view_single_product($id)
    {
        if (!$this->db_connect()) {
            return false;
        }

        $id = (int)$id;
        if ($id <= 0) {
            return false;
        }

        $sql = "SELECT p.product_id, p.product_cat, p.product_brand, p.product_title,
                       p.product_price, p.product_desc, p.product_keywords, p.product_image,
                       c.cat_id, c.cat_name,
                       b.brand_id, b.brand_name
                FROM products p
                LEFT JOIN categories c ON p.product_cat = c.cat_id
                LEFT JOIN brands b ON p.product_brand = b.brand_id
                WHERE p.product_id = ? LIMIT 1";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log("product_class::view_single_product prepare failed: " . $this->db->error);
            return false;
        }

        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc() ?: null;
        $stmt->close();

        return $row;
    }

    /**
     * Composite search with filters
     */
    public function composite_search($filters = [], $limit = null, $offset = 0)
    {
        if (!$this->db_connect()) {
            return false;
        }

        $query = trim($filters['query'] ?? '');
        $cat_id = isset($filters['cat_id']) ? (int)$filters['cat_id'] : 0;
        $brand_id = isset($filters['brand_id']) ? (int)$filters['brand_id'] : 0;
        $max_price = isset($filters['max_price']) ? (float)$filters['max_price'] : null;
        $min_price = isset($filters['min_price']) ? (float)$filters['min_price'] : null;

        $sql = "SELECT p.product_id, p.product_cat, p.product_brand, p.product_title,
                       p.product_price, p.product_desc, p.product_keywords, p.product_image,
                       c.cat_id, c.cat_name,
                       b.brand_id, b.brand_name
                FROM products p
                LEFT JOIN categories c ON p.product_cat = c.cat_id
                LEFT JOIN brands b ON p.product_brand = b.brand_id
                WHERE 1=1";

        $params = [];
        $types = '';

        if ($query !== '') {
            $sql .= " AND (p.product_title LIKE ? OR p.product_keywords LIKE ? OR p.product_desc LIKE ?)";
            $qAny = '%' . $query . '%';
            $params[] = $qAny; $params[] = $qAny; $params[] = $qAny;
            $types .= 'sss';
        }

        if ($cat_id > 0) {
            $sql .= " AND p.product_cat = ?";
            $params[] = $cat_id;
            $types .= 'i';
        }

        if ($brand_id > 0) {
            $sql .= " AND p.product_brand = ?";
            $params[] = $brand_id;
            $types .= 'i';
        }

        if ($max_price !== null && $max_price > 0) {
            $sql .= " AND p.product_price <= ?";
            $params[] = $max_price;
            $types .= 'd';
        }

        if ($min_price !== null && $min_price > 0) {
            $sql .= " AND p.product_price >= ?";
            $params[] = $min_price;
            $types .= 'd';
        }

        $sql .= " ORDER BY p.product_title ASC";

        if ($limit !== null && (int)$limit > 0) {
            $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        }

        if (!empty($params)) {
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                error_log("product_class::composite_search prepare failed: " . $this->db->error);
                return false;
            }
            // bind params dynamically
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $res = $stmt->get_result();
            $rows = [];
            while ($r = $res->fetch_assoc()) {
                $rows[] = $r;
            }
            $stmt->close();
            return $rows;
        } else {
            $res = $this->db->query($sql);
            if (!$res) {
                error_log("product_class::composite_search query failed: " . $this->db->error);
                return false;
            }
            $rows = [];
            while ($r = $res->fetch_assoc()) {
                $rows[] = $r;
            }
            $res->free();
            return $rows;
        }
    }
}
