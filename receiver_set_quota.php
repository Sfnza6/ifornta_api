<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/config.php';

$item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
$quota   = isset($_POST['daily_quota']) ? trim($_POST['daily_quota']) : '';

if ($item_id <= 0) {
  http_response_code(400);
  echo json_encode(['status'=>'error','message'=>'item_id required'], JSON_UNESCAPED_UNICODE);
  exit;
}

if ($quota === '' || strcasecmp($quota,'null')===0) {
  $st = $conn->prepare("UPDATE items SET daily_quota=NULL, quota_used=0, quota_date=NULL WHERE id=?");
  $st->bind_param('i', $item_id);
} else {
  $q = max(0, (int)$quota);
  $today = date('Y-m-d');
  $st = $conn->prepare("UPDATE items SET daily_quota=?, quota_date=?, quota_used=0, is_active=1 WHERE id=?");
  $st->bind_param('isi', $q, $today, $item_id);
}
$ok = $st->execute(); $err = $st->error; $st->close();

if ($ok) echo json_encode(['status'=>'success']);
else { http_response_code(500); echo json_encode(['status'=>'error','message'=>$err]); }
