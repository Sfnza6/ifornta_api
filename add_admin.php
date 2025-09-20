<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require_once __DIR__.'/config.php';
function out($a,$c=200){ http_response_code($c); echo json_encode($a, JSON_UNESCAPED_UNICODE); exit; }

$body = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$name  = trim((string)($body['name']  ?? ''));
$phone = trim((string)($body['phone'] ?? ''));
$pass  = (string)($body['password'] ?? '');
$role  = trim((string)($body['role']  ?? 'admin'));
$avatar= trim((string)($body['avatar_url'] ?? ''));

if ($name==='' || $phone==='' || $pass==='') out(['status'=>'error','message'=>'name/phone/password required'], 400);

$hash = password_hash($pass, PASSWORD_BCRYPT);

$stmt = $conn->prepare("INSERT INTO admins (name,phone,password_hash,avatar_url,role) VALUES (?,?,?,?,?)");
$stmt->bind_param('sssss', $name,$phone,$hash,$avatar,$role);
if ($stmt->execute()) {
  out(['status'=>'success','id'=>$stmt->insert_id]);
} else {
  out(['status'=>'error','message'=>$conn->error], 500);
}
