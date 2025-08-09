<?php
// upload_image.php

// إخفاء تحذيرات PHP
error_reporting(0);

// إعادة الاستجابة بصيغة JSON والسماح بالوصول من أي مكان (CORS)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

include 'config.php';

// التأكد من وجود ملف الصورة
if (!isset($_FILES['image'])) {
    http_response_code(400);
    echo json_encode([
      'status'  => 'error',
      'message' => 'No image uploaded'
    ]);
    exit;
}

// مسار المجلد الذي سيرفع إليه
$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// بناء اسم جديد فريد للصورة
$origName = basename($_FILES['image']['name']);
$ext      = pathinfo($origName, PATHINFO_EXTENSION);
$newName  = uniqid('img_') . '.' . $ext;
$target   = $uploadDir . $newName;

// محاولة نقل الملف من الذاكرة إلى المجلد
if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
    // بناء URL كامل للصورة مضمناً البروتوكول والهوست
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'];                     // مثلاً: 192.168.1.192
    $base   = dirname($_SERVER['SCRIPT_NAME']);          // مثلاً: /iforenta_api
    $url    = "$scheme://$host$base/uploads/$newName";

    echo json_encode([
      'status' => 'success',
      'url'    => $url
    ]);
} else {
    http_response_code(500);
    echo json_encode([
      'status'  => 'error',
      'message' => 'Upload failed'
    ]);
}
?>
