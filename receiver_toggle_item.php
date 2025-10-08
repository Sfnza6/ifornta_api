<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/config.php';

$item_id   = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
$is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 0;

if ($item_id <= 0) {
  http_response_code(400);
  echo json_encode(['status'=>'error','message'=>'item_id required'], JSON_UNESCAPED_UNICODE);
  exit;
}

$st = $conn->prepare("UPDATE items SET is_active=? WHERE id=? LIMIT 1");
$st->bind_param('ii', $is_active, $item_id);
$ok = $st->execute(); $err = $st->error; $st->close();

if ($ok) echo json_encode(['status'=>'success']);
else { http_response_code(500); echo json_encode(['status'=>'error','message'=>$err]); }
