<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/config.php';

$name   = trim($_POST['name'] ?? '');
$phone  = trim($_POST['phone'] ?? '');
$pass   = trim($_POST['password'] ?? '');
$role   = trim($_POST['role'] ?? 'admin');
$active = intval($_POST['is_active'] ?? 1);
$avatar = trim($_POST['avatar_url'] ?? ''); // اختياري (رابط صورة)

if ($name === '' || $phone === '' || $pass === '') {
  http_response_code(400);
  echo json_encode(['status'=>'error','message'=>'name/phone/password required'], JSON_UNESCAPED_UNICODE);
  exit;
}
$allowed = ['admin','receiver'];
if (!in_array($role, $allowed, true)) $role = 'admin';

$hash  = password_hash($pass, PASSWORD_BCRYPT);
$token = bin2hex(random_bytes(16));

$sql = "INSERT INTO admins (name, phone, password_hash, avatar_url, role, api_token, is_active, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ssssssi', $name, $phone, $hash, $avatar, $role, $token, $active);

if ($stmt->execute()) {
  echo json_encode(['status'=>'success','id'=>$stmt->insert_id], JSON_UNESCAPED_UNICODE);
} else {
  http_response_code(500);
  echo json_encode(['status'=>'error','message'=>$stmt->error], JSON_UNESCAPED_UNICODE);
}
$stmt->close();
