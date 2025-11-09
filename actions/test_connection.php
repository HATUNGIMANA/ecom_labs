<?php
// test_connection.php

header('Content-Type: application/json');

echo json_encode([
    "status" => "success",
    "message" => "Backend is reachable ✅"
]);
