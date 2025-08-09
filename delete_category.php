<?php
// delete_category.php

error_reporting(0);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

include 'config.php';

// أولًا نحاول قراءة JSON من الجسم
$input = json_decode(file_get_contents('php://input'), true);
if (json_last_error() === JSON_ERROR_NONE) {
    $id = intval($input['id'] ?? 0);
} else {
    // fallback لبيانات form-data
    $id = intval($_POST['id'] ?? 0);
}

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>'Invalid or missing id']);
    exit;
}

// ننفّذ الحذف
$sql  = "DELETE FROM categories WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(['status'=>'success']);
} else {
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>$stmt->error]);
}
?>
