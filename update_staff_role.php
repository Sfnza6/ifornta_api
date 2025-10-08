<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/config.php';

$id   = intval($_POST['id'] ?? 0);
$role = trim($_POST['role'] ?? '');

if ($id <= 0 || $role === '') {
  http_response_code(400);
  echo json_encode(['status'=>'error','message'=>'id/role required'], JSON_UNESCAPED_UNICODE);
  exit;
}
$allowed = ['admin','receiver'];
if (!in_array($role, $allowed, true)) {
  http_response_code(400);
  echo json_encode(['status'=>'error','message'=>'invalid role'], JSON_UNESCAPED_UNICODE);
  exit;
}

$stmt = $conn->prepare("UPDATE admins SET role=? WHERE id=? LIMIT 1");
$stmt->bind_param('si', $role, $id);

if ($stmt->execute()) {
  echo json_encode(['status'=>'success'], JSON_UNESCAPED_UNICODE);
} else {
  http_response_code(500);
  echo json_encode(['status'=>'error','message'=>$stmt->error], JSON_UNESCAPED_UNICODE);
}
$stmt->close();
