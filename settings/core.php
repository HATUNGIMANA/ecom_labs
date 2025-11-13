<?php
/**
 * Settings/core.php
 * Core session management and authentication functions
 */

// Ensure session is started (only if not already started)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// For header redirection
ob_start();

/**
 * Check if a user is logged in
 * @return bool Returns true if user is logged in (session exists), false otherwise
 */
function is_logged_in()
{
    // Check if customer_id exists in session (indicates user is logged in)
    return isset($_SESSION['customer_id']) && !empty($_SESSION['customer_id']);
}

/**
 * Check if a user has administrative privileges
 * @return bool Returns true if user has admin privileges (role = 1), false otherwise
 */
function is_admin()
{
    // Check if user is logged in first
    if (!is_logged_in()) {
        return false;
    }
    
    // Check if user role is 1 (Administrator/Restaurant Owner)
    // Role 1 = Administrator/Restaurant Owner
    // Role 2 = Customer
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] == 1;
}

/**
 * Get the current logged-in user's ID
 * @return int|null Returns user ID if logged in, null otherwise
 */
function get_user_id()
{
    if (is_logged_in()) {
        return $_SESSION['customer_id'];
    }
    return null;
}

/**
 * Get the current logged-in user's name
 * @return string|null Returns user name if logged in, null otherwise
 */
function get_user_name()
{
    if (is_logged_in()) {
        return isset($_SESSION['customer_name']) ? $_SESSION['customer_name'] : null;
    }
    return null;
}

/**
 * Get the current logged-in user's role
 * @return int|null Returns user role if logged in, null otherwise (1 = Admin, 2 = Customer)
 */
function get_user_role()
{
    if (is_logged_in()) {
        return isset($_SESSION['user_role']) ? $_SESSION['user_role'] : null;
    }
    return null;
}

/**
 * Return the web-path base for the current application (trailing slash included).
 * Example: if site is served at /Ecommerce/EcomLabs/index.php this returns '/Ecommerce/EcomLabs/'.
 */
function site_base_url()
{
    $script = $_SERVER['SCRIPT_NAME'] ?? '/';
    $dir = rtrim(dirname($script), '/\\');
    if ($dir === '' || $dir === '.') return '/';
    return $dir . '/';
}

?>
