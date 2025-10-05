<?php
// iforenta_api/get_cart.php
declare(strict_types=1);

// ===== Headers / CORS =====
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
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
function intParam($arr, string $key, int $default = 0): int {
  if (!isset($arr[$key])) return $default;
  return (int)filter_var($arr[$key], FILTER_VALIDATE_INT, ['options'=>['default'=>$default]]);
}

// ===== Input =====
$user_id = intParam($_GET, 'user_id');
if ($user_id <= 0) jdie(false, ['message' => 'invalid user_id'], 400);

// ===== Query =====
$sql = "
  SELECT 
    c.id        AS cart_item_id,
    c.quantity  AS quantity,
    i.id        AS item_id,
    i.name      AS name,
    i.price     AS price,
    i.image_url AS image_url,
    i.description AS description,
    i.category_id AS category_id
  FROM cart c
  JOIN items i ON c.item_id = i.id
  WHERE c.user_id = ?
  ORDER BY c.id DESC
";
$stmt = $conn->prepare($sql);
if (!$stmt) jdie(false, ['message' => 'prepare failed: '.$conn->error], 500);

$stmt->bind_param("i", $user_id);
if (!$stmt->execute()) jdie(false, ['message' => 'execute failed: '.$stmt->error], 500);

$res = $stmt->get_result();

// ===== Build Response =====
$cart = [];
$subtotal = 0.0;

while ($row = $res->fetch_assoc()) {
  $price = (float)$row['price'];
  $qty   = (int)$row['quantity'];
  $line  = $price * $qty;

  $cart[] = [
    'cart_item_id' => (int)$row['cart_item_id'],
    'item_id'      => (int)$row['item_id'],
    'name'         => (string)$row['name'],
    'price'        => $price,
    'image_url'    => (string)($row['image_url'] ?? ''),
    'description'  => (string)($row['description'] ?? ''),
    'category_id'  => (int)$row['category_id'],
    'quantity'     => $qty,
    'line_total'   => $line,
  ];
  $subtotal += $line;
}

jdie(true, [
  'cart'     => $cart,
  'subtotal' => round($subtotal, 2),
]);
