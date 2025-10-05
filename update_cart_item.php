<?php
// iforenta_api/update_cart_item.php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require_once __DIR__ . '/config.php';
mysqli_set_charset($conn, 'utf8mb4');

function jdie(bool $ok, array $data = [], int $code = 200): void {
  http_response_code($code);
  echo json_encode(['ok'=>$ok] + $data, JSON_UNESCAPED_UNICODE);
  exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$user_id = intval($input['user_id'] ?? ($_POST['user_id'] ?? 0));
$item_id = intval($input['item_id'] ?? ($_POST['item_id'] ?? 0));
$qty     = intval($input['quantity'] ?? ($_POST['quantity'] ?? 1));

if ($user_id <= 0 || $item_id <= 0) {
  jdie(false, ['message'=>'invalid user_id/item_id'], 400);
}

if ($qty <= 0) {
  $sql = "DELETE FROM cart WHERE user_id=? AND item_id=? LIMIT 1";
  $st = $conn->prepare($sql);
  $st->bind_param("ii", $user_id, $item_id);
  $st->execute();
  jdie(true, ['message'=>'removed']);
}

$sql = "UPDATE cart SET quantity=?, updated_at=NOW() WHERE user_id=? AND item_id=? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $qty, $user_id, $item_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
  jdie(true, ['message'=>'updated','quantity'=>$qty]);
} else {
  jdie(true, ['message'=>'no change','quantity'=>$qty]);
}
