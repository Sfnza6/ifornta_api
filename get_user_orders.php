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

$sql = "SELECT id, user_id, total, status, created_at
        FROM app_orders
        WHERE user_id = ?
        ORDER BY id DESC";

$s = $conn->prepare($sql);
$s->bind_param('i', $user_id);
$s->execute();
$r = $s->get_result();

$list = [];
while ($row = $r->fetch_assoc()) {
  $list[] = $row;
}
$s->close();

echo json_encode(['status'=>'success','data'=>$list], JSON_UNESCAPED_UNICODE);
