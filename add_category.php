<?php
// lib/backend/add_category.php

// إخفاء تحذيرات PHP وعرض JSON
error_reporting(0);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

include 'config.php';

// قراءة بيانات JSON من جسم الطلب إن وجدت
$input = json_decode(file_get_contents('php://input'), true);
if (json_last_error() === JSON_ERROR_NONE) {
    $name      = trim($input['name']      ?? '');
    $image_url = trim($input['image_url'] ?? '');
} else {
    // fallback إلى بيانات form-data
    $name      = trim($_POST['name']      ?? '');
    $image_url = trim($_POST['image_url'] ?? '');
}

// التحقق من وجود الحقول المطلوبة
if ($name === '' || $image_url === '') {
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Both "name" and "image_url" are required.'
    ]);
    exit;
}

// تحضير وإصدار الاستعلام
$sql  = "INSERT INTO categories (name, image_url) VALUES (?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $name, $image_url);

if ($stmt->execute()) {
    // بناء URL كامل للصفحة الجديدة إن احتجت
    echo json_encode([
        'status' => 'success',
        'id'     => $stmt->insert_id
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Database error: ' . $stmt->error
    ]);
}
?>
