<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
require_once __DIR__.'/config.php';

$user_id = intval($_GET['user_id'] ?? 0);
if ($user_id <= 0) {
  http_response_code(400);
  echo json_encode(['status'=>'error','message'=>'invalid user_id'], JSON_UNESCAPED_UNICODE);
  exit;
}

$q = $conn->prepare("SELECT id, username, phone, created_at FROM app_users WHERE id=? LIMIT 1");
$q->bind_param('i', $user_id);
$q->execute();
$res = $q->get_result();
$row = $res->fetch_assoc();
$q->close();

if (!$row) {
  echo json_encode(['status'=>'success','data'=>null], JSON_UNESCAPED_UNICODE);
} else {
  echo json_encode(['status'=>'success','data'=>$row], JSON_UNESCAPED_UNICODE);
}
