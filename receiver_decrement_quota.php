<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/config.php';

$item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
$qty     = isset($_POST['qty']) ? max(1,(int)$_POST['qty']) : 1;

if ($item_id <= 0) {
  http_response_code(400);
  echo json_encode(['status'=>'error','message'=>'item_id required'], JSON_UNESCAPED_UNICODE);
  exit;
}

$today = date('Y-m-d');
$st = $conn->prepare("SELECT daily_quota, quota_date, quota_used, is_active FROM items WHERE id=? LIMIT 1");
$st->bind_param('i', $item_id);
$st->execute(); $res = $st->get_result();
if (!($r = $res->fetch_assoc())) { http_response_code(404); echo json_encode(['status'=>'error','message'=>'item not found']); exit; }
$st->close();

$daily = is_null($r['daily_quota']) ? null : (int)$r['daily_quota'];
$used  = (int)$r['quota_used'];
$date  = $r['quota_date'];
$active= (int)$r['is_active'];

if (!is_null($daily) && $date !== $today) {
  $used = 0;
}

if (!is_null($daily)) {
  $used += $qty;
  $remaining = max(0, $daily - $used);
  $auto_off  = ($remaining <= 0) ? 1 : $active;

  $u = $conn->prepare("UPDATE items SET quota_used=?, quota_date=?, is_active=? WHERE id=?");
  $u->bind_param('isii', $used, $today, $auto_off, $item_id);
  $ok = $u->execute(); $err = $u->error; $u->close();

  if (!$ok) { http_response_code(500); echo json_encode(['status'=>'error','message'=>$err]); exit; }
  echo json_encode(['status'=>'success','remaining'=>$remaining,'is_active'=>$auto_off]); exit;
} else {
  echo json_encode(['status'=>'success','remaining'=>null,'is_active'=>$active]); exit;
}
