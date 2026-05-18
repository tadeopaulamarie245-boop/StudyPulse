<?php
include("../config/db.php");

$result = $conn->query("SELECT COUNT(*) as total FROM users WHERE status='pending'");
$data = $result->fetch_assoc();

echo json_encode([
    "pending" => $data['total']
]);