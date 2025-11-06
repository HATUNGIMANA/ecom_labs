<?php

require_once '../classes/customer_class.php';

/**
 * Customer Controller
 * Creates an instance of the customer class and runs the methods
 */
class CustomerController
{
    /**
     * Register a new customer
     * @param array $kwargs Customer data (customer_name, customer_email, customer_pass, customer_country, customer_city, customer_contact, customer_image (optional), user_role)
     * @return array Returns array with 'success' and 'message' keys
     */
    public function register_customer_ctr($kwargs)
    {
        $customer = new customer_class();

        // Check if email already exists
        if ($customer->check_email_exists($kwargs['customer_email'])) {
            return [
                'success' => false,
                'message' => 'Email already exists. Please use a different email.'
            ];
        }

        // Add customer
        $customer_id = $customer->add_customer($kwargs);

        if ($customer_id) {
            return [
                'success' => true,
                'message' => 'Registration successful! Please login to continue.',
                'customer_id' => $customer_id
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Registration failed. Please try again.'
            ];
        }
    }

    /**
     * Edit customer
     * @param int $customer_id Customer ID
     * @param array $kwargs Customer data to update
     * @return array Returns array with 'success' and 'message' keys
     */
    public function edit_customer_ctr($customer_id, $kwargs)
    {
        $customer = new customer_class();

        // If email is being updated, check if new email already exists
        if (isset($kwargs['customer_email'])) {
            $existing_customer = $customer->get_customer_by_email($kwargs['customer_email']);
            if ($existing_customer && $existing_customer['customer_id'] != $customer_id) {
                return [
                    'success' => false,
                    'message' => 'Email already exists. Please use a different email.'
                ];
            }
        }

        // Hash password if provided
        if (isset($kwargs['customer_pass'])) {
            $kwargs['customer_pass'] = password_hash($kwargs['customer_pass'], PASSWORD_DEFAULT);
        }

        $result = $customer->edit_customer($customer_id, $kwargs);

        if ($result) {
            return [
                'success' => true,
                'message' => 'Customer updated successfully.'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to update customer. Please try again.'
            ];
        }
    }

    /**
     * Delete customer
     * @param int $customer_id Customer ID
     * @return array Returns array with 'success' and 'message' keys
     */
    public function delete_customer_ctr($customer_id)
    {
        $customer = new customer_class();
        $result = $customer->delete_customer($customer_id);

        if ($result) {
            return [
                'success' => true,
                'message' => 'Customer deleted successfully.'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to delete customer. Please try again.'
            ];
        }
    }

    /**
     * Get customer by ID
     * @param int $customer_id Customer ID
     * @return array|false Customer data or false
     */
    public function get_customer_ctr($customer_id)
    {
        $customer = new customer_class();
        return $customer->get_customer($customer_id);
    }

    /**
     * Get customer by email
     * @param string $email Customer email
     * @return array|false Customer data or false
     */
    public function get_customer_by_email_ctr($email)
    {
        $customer = new customer_class();
        return $customer->get_customer_by_email($email);
    }

    /**
     * Login customer
     * @param array $kwargs Login data (customer_email, customer_pass)
     * @return array Returns array with 'success', 'message', and 'customer' keys
     */
    public function login_customer_ctr($kwargs)
    {
        $customer = new customer_class();
        
        // Validate required fields
        if (!isset($kwargs['customer_email']) || !isset($kwargs['customer_pass'])) {
            return [
                'success' => false,
                'message' => 'Email and password are required.'
            ];
        }
        
        // Attempt login
        $customer_data = $customer->login_customer($kwargs['customer_email'], $kwargs['customer_pass']);
        
        if ($customer_data) {
            return [
                'success' => true,
                'message' => 'Login successful!',
                'customer' => $customer_data
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Invalid email or password.'
            ];
        }
    }
}

