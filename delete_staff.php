<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/config.php';

$id = intval($_POST['id'] ?? 0);
if ($id <= 0) {
  http_response_code(400);
  echo json_encode(['status'=>'error','message'=>'invalid id'], JSON_UNESCAPED_UNICODE);
  exit;
}

$stmt = $conn->prepare("DELETE FROM admins WHERE id=? LIMIT 1");
$stmt->bind_param('i', $id);

if ($stmt->execute()) {
  echo json_encode(['status'=>'success'], JSON_UNESCAPED_UNICODE);
} else {
  http_response_code(500);
  echo json_encode(['status'=>'error','message'=>$stmt->error], JSON_UNESCAPED_UNICODE);
}
$stmt->close();
