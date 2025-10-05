<?php
// config.php

// ===== إعدادات قاعدة البيانات =====
$host     = "localhost";   // أو 127.0.0.1 على السيرفر
$user     = "root";        // اسم المستخدم
$password = "";            // كلمة مرور MySQL
$dbname   = "db"; // اسم قاعدة البيانات

// ===== الاتصال =====
$conn = new mysqli($host, $user, $password, $dbname);

// التحقق من الاتصال
if ($conn->connect_error) {
    die(json_encode([
        'status'  => 'error',
        'message' => 'Database connection failed: ' . $conn->connect_error
    ], JSON_UNESCAPED_UNICODE));
}

// ===== إعداد رابط الأساس (لخدمة الصور) =====
// عدّل الـIP ليكون IP جهازك في الشبكة (تقدر تجيبه من ipconfig/ifconfig)
// مثلاً: 192.168.1.129
define('BASE_URL', 'http://192.168.1.129/iforenta_api');

// لاحظ: مجلد iforenta_api لازم يكون هو نفس مكان ملفات الـPHP
?>
