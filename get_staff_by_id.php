<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/config.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
  http_response_code(400);
  echo json_encode(['status'=>'error','message'=>'invalid id'], JSON_UNESCAPED_UNICODE);
  exit;
}

$sql = "SELECT id, name, phone, password_hash AS password, avatar_url, role, api_token, is_active, created_at
        FROM admins WHERE id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
  echo json_encode(['status'=>'success','data'=>$row], JSON_UNESCAPED_UNICODE);
} else {
  http_response_code(404);
  echo json_encode(['status'=>'error','message'=>'not found'], JSON_UNESCAPED_UNICODE);
}
$stmt->close();
