<?php
// actions/db_test.php - simple DB connectivity test
header('Content-Type: application/json');
require_once __DIR__ . '/../settings/db_class.php';

$dbc = new db_connection();
$conn = $dbc->db_conn();
if ($conn === false) {
    // try to connect and capture error
    $ok = $dbc->db_connect();
    $conn = $dbc->db;
}

if ($conn && ($conn instanceof mysqli) && !$conn->connect_errno) {
    echo json_encode(['status' => 'ok', 'message' => 'Connected to DB', 'server_info' => mysqli_get_server_info($conn)]);
} else {
    $err = '';
    if (isset($dbc->db) && $dbc->db instanceof mysqli) $err = $dbc->db->connect_error;
    echo json_encode(['status' => 'error', 'message' => 'DB connection failed', 'error' => $err]);
}
