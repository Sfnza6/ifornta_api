<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

$host = "127.0.0.1";   // XAMPP localhost
$db   = "db"; // اسم القاعدة
$user = "root";         // مستخدم XAMPP الافتراضي
$pass = "";             // كلمة السر الافتراضية عادةً فارغة

$dsn  = "mysql:host=$host;dbname=$db;charset=utf8mb4";

try {
  $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
  exit;
}
