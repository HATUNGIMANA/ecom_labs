<?php
// Prefer environment variables (used on hosted servers). Fall back to local/XAMPP defaults.
$env_host = getenv('DB_HOST') ?: getenv('DB_SERVER');
$env_db   = getenv('DB_DATABASE') ?: getenv('DB_NAME');
$env_user = getenv('DB_USERNAME') ?: getenv('DB_USER');
$env_pass = getenv('DB_PASSWORD') ?: getenv('DB_PASS');

$finalHost = $env_host !== false && $env_host !== null ? $env_host : 'localhost';
$finalUser = $env_user !== false && $env_user !== null ? $env_user : 'root';
$finalPass = $env_pass !== false && $env_pass !== null ? $env_pass : '';
$finalDB   = $env_db !== false && $env_db !== null ? $env_db : 'shoppn';

if (!defined('SERVER'))   define('SERVER', $finalHost);
if (!defined('USERNAME')) define('USERNAME', $finalUser);
if (!defined('PASSWD'))   define('PASSWD', $finalPass);
if (!defined('DATABASE')) define('DATABASE', $finalDB);
