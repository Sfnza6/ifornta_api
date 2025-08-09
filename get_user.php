<?php
// iforenta_api/get_user.php

error_reporting(0);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

include 'config.php';

// افتراضياً نأخذ user_id من الجيت (يمكن تغييره حسب طريقتك)
$user_id = intval($_GET['user_id'] ?? 0);
if ($user_id <= 0) {
    http_response_code(400);
    echo json_encode(['error'=>'invalid user_id']);
    exit;
}

$sql  = "SELECT id, username, phone FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode($row);
} else {
    http_response_code(404);
    echo json_encode(['error'=>'user not found']);
}
