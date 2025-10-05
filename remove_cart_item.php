<?php
// iforenta_api/remove_cart_item.php
declare(strict_types=1);

// ===== Headers / CORS =====
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require_once __DIR__ . '/config.php';
mysqli_set_charset($conn, 'utf8mb4');

// ===== Helpers =====
function jdie(bool $ok, array $data = [], int $code = 200): void {
  http_response_code($code);
  echo json_encode(['ok' => $ok] + $data, JSON_UNESCAPED_UNICODE);
  exit;
}
function intParam(array $arr, string $key, int $default = 0): int {
  if (!isset($arr[$key])) return $default;
  return (int)filter_var($arr[$key], FILTER_VALIDATE_INT, ['options'=>['default'=>$default]]);
}

// ===== Input (JSON or form) =====
$input = json_decode(file_get_contents('php://input'), true);
if (json_last_error() === JSON_ERROR_NONE && is_array($input)) {
  $cart_id = intParam($input, 'cart_id', intParam($input, 'cart_item_id'));
  $user_id = intParam($input, 'user_id');
  $item_id = intParam($input, 'item_id');
} else {
  $cart_id = intParam($_POST, 'cart_id', intParam($_POST, 'cart_item_id'));
  $user_id = intParam($_POST, 'user_id');
  $item_id = intParam($_POST, 'item_id');
}

// ===== Delete by cart_id (preferred) =====
if ($cart_id > 0) {
  $stmt = $conn->prepare("DELETE FROM cart WHERE id=? LIMIT 1");
  if (!$stmt) jdie(false, ['message' => 'prepare failed: '.$conn->error], 500);

  $stmt->bind_param("i", $cart_id);
  if (!$stmt->execute()) jdie(false, ['message' => 'execute failed: '.$stmt->error], 500);

  jdie(true, ['message' => $stmt->affected_rows > 0 ? 'removed' : 'not found', 'cart_id' => $cart_id]);
}

// ===== Delete by (user_id, item_id) fallback =====
if ($user_id > 0 && $item_id > 0) {
  $stmt = $conn->prepare("DELETE FROM cart WHERE user_id=? AND item_id=? LIMIT 1");
  if (!$stmt) jdie(false, ['message' => 'prepare failed: '.$conn->error], 500);

  $stmt->bind_param("ii", $user_id, $item_id);
  if (!$stmt->execute()) jdie(false, ['message' => 'execute failed: '.$stmt->error], 500);

  jdie(true, [
    'message' => $stmt->affected_rows > 0 ? 'removed' : 'not found',
    'user_id' => $user_id,
    'item_id' => $item_id
  ]);
}
$delExtras = $conn->prepare("DELETE FROM cart_extras WHERE user_id=? AND item_id=?");
$delExtras->bind_param("ii", $user_id, $item_id);
$delExtras->execute();
// ===== Invalid input =====
jdie(false, ['message' => 'invalid cart_id / user_id+item_id'], 400);
