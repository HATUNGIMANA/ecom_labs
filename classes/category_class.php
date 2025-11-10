<?php

// TRY MULTIPLE PATHS FOR DB CLASS
$db_paths = [
    __DIR__ . '/../settings/db_class.php',
    dirname(__DIR__) . '/settings/db_class.php',
    __DIR__ . '/../../settings/db_class.php'
];

$included = false;
foreach ($db_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $included = true;
        break;
    }
}

if (!$included) {
    error_log("category_class: Cannot find db_class.php");
    throw new Exception("Database class file not found");
}

/**
 * Category class that extends database connection
 * Contains category methods: add category, edit category, delete category, get category, etc.
 */
class category_class extends db_connection
{
    // store last error message for callers
    public $last_error = '';
    /**
     * Add a new category
     * @param array $data Category data including: cat_name, customer_id
     * @return int|false Returns category_id on success, false on failure
     */
    public function add_category($data)
    {
        // Connect to database
        if (!$this->db_connect()) {
            $this->last_error = 'Database connection failed';
            error_log("category_class::add_category - Database connection failed");
            return false;
        }

        // Log the connection attempt
        error_log("category_class::add_category - Database connected successfully");
        error_log("category_class::add_category - Data: " . print_r($data, true));

        // Prepare SQL query - Try with customer_id first
        $sql = "INSERT INTO categories (cat_name, customer_id) VALUES (?, ?)";

        // Prepare statement
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            $this->last_error = 'Prepare failed with customer_id: ' . $this->db->error;
            error_log("category_class::add_category - " . $this->last_error);
            
            // If the above fails, try without customer_id (fallback)
            $sql = "INSERT INTO categories (cat_name) VALUES (?)";
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                $this->last_error = 'Prepare failed without customer_id: ' . $this->db->error;
                error_log("category_class::add_category - " . $this->last_error);
                return false;
            }
            $cat_name = $data['cat_name'];
            $stmt->bind_param("s", $cat_name);
        } else {
            $cat_name = $data['cat_name'];
            $customer_id = isset($data['customer_id']) ? (int)$data['customer_id'] : null;
            $stmt->bind_param("si", $cat_name, $customer_id);
        }

        // Execute query
        if ($stmt->execute()) {
            $category_id = $this->db->insert_id;
            error_log("category_class::add_category - Success! New ID: $category_id");
            $stmt->close();
            return $category_id;
        } else {
            $this->last_error = 'Execute failed: ' . $stmt->error;
            error_log("category_class::add_category - " . $this->last_error);
            $stmt->close();
            return false;
        }
    }

    /**
     * Check if category name already exists
     * @param string $cat_name Category name to check
     * @param int|null $exclude_id Category ID to exclude from check (for updates)
     * @return bool True if name exists, false otherwise
     */
    public function check_category_name_exists($cat_name, $exclude_id = null)
    {
        if (!$this->db_connect()) {
            return false;
        }

        if ($exclude_id) {
            $sql = "SELECT cat_id FROM categories WHERE cat_name = ? AND cat_id != ?";
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                return false;
            }
            $stmt->bind_param("si", $cat_name, $exclude_id);
        } else {
            $sql = "SELECT cat_id FROM categories WHERE cat_name = ?";
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                return false;
            }
            $stmt->bind_param("s", $cat_name);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();

        return $exists;
    }

    /**
     * Get all categories created by a specific user
     * @param int $customer_id Customer ID
     * @return array|false Array of categories or false on failure
     */
    public function get_categories_by_user($customer_id)
    {
        if (!$this->db_connect()) {
            return false;
        }

        // Try with customer_id first
        $sql = "SELECT * FROM categories WHERE customer_id = ? ORDER BY cat_name ASC";
        $stmt = $this->db->prepare($sql);
        
        if (!$stmt) {
            // If customer_id column doesn't exist, get all categories
            $sql = "SELECT * FROM categories ORDER BY cat_name ASC";
            $result = $this->db_query($sql);
            if (!$result) {
                return false;
            }
            return $this->db_fetch_all("SELECT * FROM categories ORDER BY cat_name ASC");
        }

        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
        $stmt->close();

        return $categories;
    }

    /**
     * Get a single category by ID
     * @param int $cat_id Category ID
     * @return array|false Category data or false on failure
     */
    public function get_category($cat_id)
    {
        if (!$this->db_connect()) {
            return false;
        }

        $sql = "SELECT * FROM categories WHERE cat_id = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("i", $cat_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $category = $result->fetch_assoc();
        $stmt->close();

        return $category;
    }

    /**
     * Update category
     * @param int $cat_id Category ID
     * @param array $data Category data to update (cat_name)
     * @return bool True on success, false on failure
     */
    public function update_category($cat_id, $data)
    {
        if (!$this->db_connect()) {
            return false;
        }

        if (!isset($data['cat_name'])) {
            return false;
        }

        $sql = "UPDATE categories SET cat_name = ? WHERE cat_id = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $cat_name = $data['cat_name'];
        $stmt->bind_param("si", $cat_name, $cat_id);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }

    /**
     * Delete category
     * @param int $cat_id Category ID
     * @return bool True on success, false on failure
     */
    public function delete_category($cat_id)
    {
        if (!$this->db_connect()) {
            return false;
        }

        $sql = "DELETE FROM categories WHERE cat_id = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("i", $cat_id);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }
}

