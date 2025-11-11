<?php

// robust DB include (try common locations)
$db_paths = [
    __DIR__ . '/../settings/db_class.php',
    dirname(__DIR__) . '/settings/db_class.php',
    __DIR__ . '/../../settings/db_class.php'
];
$included = false;
foreach ($db_paths as $p) {
    if (file_exists($p)) { require_once $p; $included = true; break; }
}
if (!$included) {
    error_log('brand_class: Cannot find db_class.php');
    throw new Exception('Database class file not found');
}

/**
 * Brand class that extends database connection
 * Contains brand methods: add brand, edit brand, delete brand, get brand, etc.
 */
class brand_class extends db_connection
{
    public $last_error = '';
    /**
     * Add a new brand
     * @param array $data Brand data including: brand_name, cat_id, created_by (optional)
     * @return int|false Returns brand_id on success, false on failure
     */
    public function add($data)
    {
        if (!$this->db_connect()) {
            $this->last_error = 'Database connection failed';
            return false;
        }

        $brand_name = trim($data['brand_name'] ?? '');
        $cat_id = isset($data['cat_id']) ? (int)$data['cat_id'] : 0;
        
        if (empty($brand_name)) {
            return false;
        }

        // Check if brands table has cat_id column
        // Check whether cat_id exists to avoid prepare/execute ambiguity
        $hasCatId = false;
        $cols = $this->db->query("SHOW COLUMNS FROM brands LIKE 'cat_id'");
        if ($cols && $cols->num_rows > 0) $hasCatId = true;

        if ($hasCatId) {
            $sql = "INSERT INTO brands (brand_name, cat_id) VALUES (?, ?)";
            $stmt = $this->db->prepare($sql);
            if (!$stmt) { $this->last_error = 'Prepare failed with cat_id: ' . $this->db->error; error_log($this->last_error); return false; }
            $stmt->bind_param("si", $brand_name, $cat_id);
        } else {
            $sql = "INSERT INTO brands (brand_name) VALUES (?)";
            $stmt = $this->db->prepare($sql);
            if (!$stmt) { $this->last_error = 'Prepare failed without cat_id: ' . $this->db->error; error_log($this->last_error); return false; }
            $stmt->bind_param("s", $brand_name);
        }

        if ($stmt->execute()) {
            $brand_id = $this->db->insert_id;
            $stmt->close();
            return $brand_id;
        } else {
            $this->last_error = 'Execute failed: ' . $stmt->error;
            error_log('brand_class::add - ' . $this->last_error);
            $stmt->close();
            return false;
        }
    }

    /**
     * Check if brand exists for user and category
     * @param string $brand_name Brand name
     * @param int $cat_id Category ID
     * @param int|null $user_id User ID (optional)
     * @return bool True if exists, false otherwise
     */
    public function exists_brand_for_user($brand_name, $cat_id, $user_id = null)
    {
        if (!$this->db_connect()) {
            return false;
        }
        // Detect whether the brands table actually has a cat_id column
        $hasCatId = false;
        $cols = $this->db->query("SHOW COLUMNS FROM brands LIKE 'cat_id'");
        if ($cols && $cols->num_rows > 0) $hasCatId = true;

        if ($hasCatId) {
            $sql = "SELECT brand_id FROM brands WHERE brand_name = ? AND cat_id = ?";
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                $this->last_error = 'Prepare failed in exists_brand_for_user with cat_id: ' . $this->db->error;
                error_log($this->last_error);
                return false;
            }
            $stmt->bind_param("si", $brand_name, $cat_id);
        } else {
            // Table lacks cat_id — fall back to name-only check
            $sql = "SELECT brand_id FROM brands WHERE brand_name = ?";
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                $this->last_error = 'Prepare failed in exists_brand_for_user (no cat_id): ' . $this->db->error;
                error_log($this->last_error);
                return false;
            }
            $stmt->bind_param("s", $brand_name);
        }

        if (!$stmt->execute()) {
            $this->last_error = 'Execute failed in exists_brand_for_user: ' . $stmt->error;
            error_log($this->last_error);
            $stmt->close();
            return false;
        }
        $result = $stmt->get_result();
        if ($result === false) {
            $this->last_error = 'Get result failed in exists_brand_for_user: ' . $this->db->error;
            error_log($this->last_error);
            $stmt->close();
            return false;
        }
        $exists = $result->num_rows > 0;
        $stmt->close();

        return $exists;
    }

    /**
     * Get all brands, optionally grouped by category
     * @return array|false Array of brands or false on failure
     */
    public function get_all()
    {
        if (!$this->db_connect()) {
            return false;
        }

        // Check if cat_id column exists in brands table
        $columns = $this->db->query("SHOW COLUMNS FROM brands LIKE 'cat_id'");
        $hasCatId = $columns && $columns->num_rows > 0;
        
        if ($hasCatId) {
            // Join with categories if cat_id exists
            $sql = "SELECT b.brand_id, b.brand_name, 
                           COALESCE(b.cat_id, 0) as cat_id,
                           COALESCE(c.cat_name, 'Uncategorized') as cat_name
                    FROM brands b
                    LEFT JOIN categories c ON b.cat_id = c.cat_id
                    ORDER BY c.cat_name, b.brand_name";
        } else {
            // No cat_id column - return all brands under "Uncategorized"
            $sql = "SELECT brand_id, brand_name, 0 as cat_id, 'Uncategorized' as cat_name 
                    FROM brands 
                    ORDER BY brand_name";
        }
        
        $result = $this->db->query($sql);
        if (!$result) {
            return false;
        }

        $brands = [];
        while ($row = $result->fetch_assoc()) {
            $brands[] = $row;
        }

        return $brands;
    }

    /**
     * Get all brands by user (if user tracking exists)
     * @param int|null $user_id User ID
     * @return array|false Array of brands or false on failure
     */
    public function get_all_by_user($user_id = null)
    {
        // For now, just return all brands
        // If brands table has created_by column, filter by it
        if (!$this->db_connect()) {
            return false;
        }

        // Check if cat_id column exists in brands table
        $catIdCol = $this->db->query("SHOW COLUMNS FROM brands LIKE 'cat_id'");
        $hasCatId = $catIdCol && $catIdCol->num_rows > 0;
        
        if ($hasCatId) {
            $sql = "SELECT b.brand_id, b.brand_name, 
                           COALESCE(b.cat_id, 0) as cat_id,
                           COALESCE(c.cat_name, 'Uncategorized') as cat_name
                    FROM brands b
                    LEFT JOIN categories c ON b.cat_id = c.cat_id";
            
            // Check if created_by column exists
            $createdByCol = $this->db->query("SHOW COLUMNS FROM brands LIKE 'created_by'");
            if ($createdByCol && $createdByCol->num_rows > 0 && $user_id !== null) {
                $sql .= " WHERE b.created_by = " . (int)$user_id;
            }
            
            $sql .= " ORDER BY c.cat_name, b.brand_name";
        } else {
            // No cat_id column - return all brands under "Uncategorized"
            $sql = "SELECT brand_id, brand_name, 0 as cat_id, 'Uncategorized' as cat_name 
                    FROM brands";
            
            // Check if created_by column exists
            $createdByCol = $this->db->query("SHOW COLUMNS FROM brands LIKE 'created_by'");
            if ($createdByCol && $createdByCol->num_rows > 0 && $user_id !== null) {
                $sql .= " WHERE created_by = " . (int)$user_id;
            }
            
            $sql .= " ORDER BY brand_name";
        }
        
        $result = $this->db->query($sql);
        if (!$result) {
            return false;
        }

        $brands = [];
        while ($row = $result->fetch_assoc()) {
            $brands[] = $row;
        }

        return $brands;
    }

    /**
     * Update brand
     * @param int $brand_id Brand ID
     * @param string $brand_name New brand name
     * @param int|null $updated_by User ID who updated (optional)
     * @return bool True on success, false on failure
     */
    public function update($brand_id, $brand_name, $updated_by = null)
    {
        if (!$this->db_connect()) {
            return false;
        }

        $brand_name = trim($brand_name);
        if (empty($brand_name) || $brand_id <= 0) {
            return false;
        }

        $sql = "UPDATE brands SET brand_name = ? WHERE brand_id = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("si", $brand_name, $brand_id);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }

    /**
     * Delete brand
     * @param int $brand_id Brand ID
     * @param int|null $user_id User ID (for authorization check, optional)
     * @return bool True on success, false on failure
     */
    public function delete($brand_id, $user_id = null)
    {
        if (!$this->db_connect()) {
            return false;
        }

        if ($brand_id <= 0) {
            return false;
        }

        $sql = "DELETE FROM brands WHERE brand_id = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("i", $brand_id);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }
}

