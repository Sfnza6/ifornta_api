<?php
// iforenta_api/update_cart_extra.php
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

include 'config.php';

$in = json_decode(file_get_contents('php://input'), true);
$user_id  = intval($in['user_id']  ?? ($_POST['user_id']  ?? 0));
$item_id  = intval($in['item_id']  ?? ($_POST['item_id']  ?? 0));
$extra_id = intval($in['extra_id'] ?? ($_POST['extra_id'] ?? 0));
$qty      = intval($in['qty']      ?? ($_POST['qty']      ?? -1));

if ($user_id <= 0 || $item_id <= 0 || $extra_id <= 0 || $qty < 0) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'message'=>'invalid parameters']);
  exit;
}

if ($qty == 0) {
  $sql = "DELETE FROM cart_extras WHERE user_id=? AND item_id=? AND extra_id=?";
  $st = $conn->prepare($sql);
  $st->bind_param("iii", $user_id, $item_id, $extra_id);
  $ok = $st->execute();
  echo json_encode(['ok'=>$ok, 'qty'=>0]);
  exit;
}

$sql = "INSERT INTO cart_extras (user_id,item_id,extra_id,qty)
        VALUES (?,?,?,?)
        ON DUPLICATE KEY UPDATE qty=VALUES(qty)";
$st = $conn->prepare($sql);
$st->bind_param("iiii", $user_id, $item_id, $extra_id, $qty);
$ok = $st->execute();

echo json_encode(['ok'=>$ok, 'qty'=>$qty]);
