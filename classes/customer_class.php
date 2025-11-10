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
    error_log('customer_class: Cannot find db_class.php');
    throw new Exception('Database class file not found');
}

/**
 * Customer class that extends database connection
 * Contains customer methods: add customer, edit customer, delete customer, etc.
 */
class customer_class extends db_connection
{
    /**
     * Add a new customer (registration)
     * @param array $data Customer data including: customer_name, customer_email, customer_pass, customer_country, customer_city, customer_contact, customer_image (optional), user_role
     * @return array|false Returns customer_id on success, false on failure
     */
    public function add_customer($data)
    {
        // Connect to database
        if (!$this->db_connect()) {
            return false;
        }

        // Prepare SQL query
        $sql = "INSERT INTO customer (customer_name, customer_email, customer_pass, customer_country, customer_city, customer_contact, customer_image, user_role) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        // Prepare statement
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return false;
        }

        // Bind parameters
        $customer_name = $data['customer_name'];
        $customer_email = $data['customer_email'];
        $customer_pass = $data['customer_pass']; // Already hashed in controller/action
        $customer_country = $data['customer_country'];
        $customer_city = $data['customer_city'];
        $customer_contact = $data['customer_contact'];
        $customer_image = isset($data['customer_image']) && $data['customer_image'] !== NULL ? $data['customer_image'] : NULL;
        $user_role = isset($data['user_role']) ? (int)$data['user_role'] : 2; // Default to 2 (customer)

        // Handle NULL for customer_image - use "s" for string or NULL
        $stmt->bind_param("sssssssi", $customer_name, $customer_email, $customer_pass, $customer_country, $customer_city, $customer_contact, $customer_image, $user_role);

        // Execute query
        if ($stmt->execute()) {
            $customer_id = $this->db->insert_id;
            $stmt->close();
            return $customer_id;
        } else {
            $stmt->close();
            return false;
        }
    }

    /**
     * Check if email already exists
     * @param string $email Email to check
     * @return bool True if email exists, false otherwise
     */
    public function check_email_exists($email)
    {
        // Use prepared statement for better security
        if (!$this->db_connect()) {
            return false;
        }
        
        $sql = "SELECT customer_id FROM customer WHERE customer_email = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        
        return $exists;
    }

    /**
     * Edit customer information
     * @param int $customer_id Customer ID
     * @param array $data Customer data to update
     * @return bool True on success, false on failure
     */
    public function edit_customer($customer_id, $data)
    {
        if (!$this->db_connect()) {
            return false;
        }

        // Build update query dynamically based on provided data
        $updates = [];
        $params = [];
        $types = "";

        if (isset($data['customer_name'])) {
            $updates[] = "customer_name = ?";
            $params[] = $data['customer_name'];
            $types .= "s";
        }
        if (isset($data['customer_email'])) {
            $updates[] = "customer_email = ?";
            $params[] = $data['customer_email'];
            $types .= "s";
        }
        if (isset($data['customer_pass'])) {
            $updates[] = "customer_pass = ?";
            $params[] = $data['customer_pass'];
            $types .= "s";
        }
        if (isset($data['customer_country'])) {
            $updates[] = "customer_country = ?";
            $params[] = $data['customer_country'];
            $types .= "s";
        }
        if (isset($data['customer_city'])) {
            $updates[] = "customer_city = ?";
            $params[] = $data['customer_city'];
            $types .= "s";
        }
        if (isset($data['customer_contact'])) {
            $updates[] = "customer_contact = ?";
            $params[] = $data['customer_contact'];
            $types .= "s";
        }
        if (isset($data['customer_image'])) {
            $updates[] = "customer_image = ?";
            $params[] = $data['customer_image'];
            $types .= "s";
        }
        if (isset($data['user_role'])) {
            $updates[] = "user_role = ?";
            $params[] = (int)$data['user_role'];
            $types .= "i";
        }

        if (empty($updates)) {
            return false;
        }

        $sql = "UPDATE customer SET " . implode(", ", $updates) . " WHERE customer_id = ?";
        $types .= "i";
        $params[] = $customer_id;

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param($types, ...$params);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }

    /**
     * Delete a customer
     * @param int $customer_id Customer ID to delete
     * @return bool True on success, false on failure
     */
    public function delete_customer($customer_id)
    {
        if (!$this->db_connect()) {
            return false;
        }

        $sql = "DELETE FROM customer WHERE customer_id = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("i", $customer_id);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }

    /**
     * Get customer by ID
     * @param int $customer_id Customer ID
     * @return array|false Customer data or false on failure
     */
    public function get_customer($customer_id)
    {
        $sql = "SELECT * FROM customer WHERE customer_id = ?";
        
        if (!$this->db_connect()) {
            return false;
        }
        
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $customer = $result->fetch_assoc();
        $stmt->close();
        
        return $customer;
    }

    /**
     * Get customer by email
     * @param string $email Customer email
     * @return array|false Customer data or false on failure
     */
    public function get_customer_by_email($email)
    {
        $sql = "SELECT * FROM customer WHERE customer_email = ?";
        
        if (!$this->db_connect()) {
            return false;
        }
        
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $customer = $result->fetch_assoc();
        $stmt->close();
        
        return $customer;
    }

    /**
     * Login customer - verify email and password
     * @param string $email Customer email
     * @param string $password Plain text password to verify
     * @return array|false Returns customer data array on success, false on failure
     */
    public function login_customer($email, $password)
    {
        // Get customer by email
        $customer = $this->get_customer_by_email($email);
        
        if (!$customer) {
            return false; // Customer not found
        }
        
        // Verify password
        if (password_verify($password, $customer['customer_pass'])) {
            // Remove password from returned data for security
            unset($customer['customer_pass']);
            return $customer;
        }
        
        return false; // Password incorrect
    }
}

