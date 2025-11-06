<?php
// settings/db_cred.php
// Centralized DB credentials and small environment helpers for the project.
// --------------------------- IMPORTANT ---------------------------
// - Do NOT commit secrets to a public repo. Replace these with environment
//   variables on production hosting or use a file outside the repo.
// - This file attempts to be backward-compatible with different db_class.php
//   implementations by defining multiple constant names.
// -----------------------------------------------------------------

// -------------------- 1) Quick debug switch ----------------------
if (!defined('APP_ENV')) {
    // You can set APP_ENV to 'production' on the server (via env var or hosting UI)
    define('APP_ENV', getenv('APP_ENV') ?: 'development');
}
$DEBUG = (APP_ENV !== 'production') && (getenv('DEBUG') === '1' || getenv('DEBUG') === 'true');

// -------------------- 2) Environment variable overrides ---------
// If hosting provides environment variables, these take precedence.
// Common names used by many hosts: DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD
$env_host     = getenv('DB_HOST') ?: getenv('DB_SERVER') ?: false;
$env_name     = getenv('DB_DATABASE') ?: getenv('DB_NAME') ?: false;
$env_user     = getenv('DB_USERNAME') ?: getenv('DB_USER') ?: false;
$env_pass     = getenv('DB_PASSWORD') ?: getenv('DB_PASS') ?: false;

// -------------------- 3) Local vs School detection --------------
// Consider local if hostname contains "localhost" or "127.0.0.1" or CLI.
$is_cli = (php_sapi_name() === 'cli');
$http_host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? '');
$is_local_host = $is_cli
    || stripos($http_host, 'localhost') !== false
    || stripos($http_host, '127.0.0.1') !== false;

// -------------------- 4) Known credentials (DEV/TEST only) ------
// <-- These are the credentials you shared earlier. Replace them with env vars on the server. -->
$schoolServer   = 'localhost';
$schoolUser     = 'eric.hatungimana';
$schoolPassword = '6100202629';
$schoolDatabase = 'ecommerce_2025A_eric_hatungimana';

// Local / XAMPP defaults
$localServer    = 'localhost';
$localUser      = 'root';
$localPassword  = '';
$localDatabase  = 'shoppn'; // as used earlier in this project

// -------------------- 5) Final chosen credentials --------------
if ($env_host && $env_user && $env_name) {
    // environment variables provided -> use them
    $finalHost = $env_host;
    $finalUser = $env_user;
    $finalPass = $env_pass !== false ? $env_pass : '';
    $finalDB   = $env_name;
} elseif ($is_local_host) {
    // running locally -> use local/XAMPP
    $finalHost = $localServer;
    $finalUser = $localUser;
    $finalPass = $localPassword;
    $finalDB   = $localDatabase;
} else {
    // otherwise, assume deployment on the school server
    $finalHost = $schoolServer;
    $finalUser = $schoolUser;
    $finalPass = $schoolPassword;
    $finalDB   = $schoolDatabase;
}

// -------------------- 6) Define constants for backward compat ---
if (!defined('SERVER'))    define('SERVER', $finalHost);
if (!defined('USERNAME'))  define('USERNAME', $finalUser);
if (!defined('PASSWD'))    define('PASSWD', $finalPass);
if (!defined('DATABASE'))  define('DATABASE', $finalDB);

// also define the more common DB_* names
if (!defined('DB_HOST'))   define('DB_HOST', SERVER);
if (!defined('DB_USER'))   define('DB_USER', USERNAME);
if (!defined('DB_PASS'))   define('DB_PASS', PASSWD);
if (!defined('DB_NAME'))   define('DB_NAME', DATABASE);

// charset and PDO DSN helper
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');
if (!defined('PDO_DSN')) {
    define('PDO_DSN', 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET);
}

// -------------------- 7) Session & security tweaks -------------
if (!headers_sent()) {
    // session cookie settings: more secure defaults (can be customized)
    $cookie_secure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
    session_set_cookie_params([
        'lifetime' => defined('SESSION_LIFETIME') ? SESSION_LIFETIME : 3600,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'] ?? '',
        'secure' => $cookie_secure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

// minimal app config defaults (don't override if defined elsewhere)
if (!defined('SESSION_LIFETIME')) define('SESSION_LIFETIME', 3600);
if (!defined('PASSWORD_MIN_LENGTH')) define('PASSWORD_MIN_LENGTH', 8);

// -------------------- 8) Helpful debug info (no sensitive data) -
if ($DEBUG) {
    error_log("[db_cred] APP_ENV=" . APP_ENV . " | Using DB_HOST=" . DB_HOST . " DB_NAME=" . DB_NAME . " DB_USER=" . DB_USER);
}

// -------------------- 9) Short helper function ------------------
if (!function_exists('get_db_pdo')) {
    /**
     * Return a configured PDO instance.
     * - Use try/catch when calling this in production and do not echo sensitive errors.
     */
    function get_db_pdo(array $opts = []) {
        $default = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $options = $opts + $default;
        return new PDO(PDO_DSN, DB_USER, DB_PASS, $options);
    }
}

// -------------------- 10) Extra: map to older constant names ------
// Some older code used SERVER/USERNAME/PASSWD/DATABASE — we already set those.
// For other older projects that expect DB_* or different names, you can
// expand the mappings here (add more defines as needed).

// -------------------- 11) Security reminder ----------------------
// Remove hard-coded credentials from this file and switch to environment
// variables on production. Example on Linux export:
//   export DB_HOST=...; export DB_USER=...; export DB_PASS=...; export DB_NAME=...;
// -----------------------------------------------------------------

?>
