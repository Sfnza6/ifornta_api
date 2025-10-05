<?php
// iforenta_api/add_to_cart.php
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
function intParam($arr, string $key, int $default = 0): int {
  if (!isset($arr[$key])) return $default;
  return (int)filter_var($arr[$key], FILTER_VALIDATE_INT, ['options' => ['default' => $default]]);
}

// ===== Read input (JSON or form) =====
$input = json_decode(file_get_contents('php://input'), true);
if (json_last_error() === JSON_ERROR_NONE && is_array($input)) {
  $user_id = intParam($input, 'user_id');
  $item_id = intParam($input, 'item_id');
  $qty     = intParam($input, 'quantity', 1);
} else {
  $user_id = intParam($_POST, 'user_id');
  $item_id = intParam($_POST, 'item_id');
  $qty     = intParam($_POST, 'quantity', 1);
}

// Sanitize
$qty = max(1, $qty);
if ($user_id <= 0 || $item_id <= 0) {
  jdie(false, ['message' => 'invalid parameters'], 400);
}

/**
 * خيار 1 (مستحسن): لو عندك قيد فريد UNIQUE(user_id, item_id) في جدول cart
 * نستخدم ON DUPLICATE KEY لدمج الكمية وتحديث updated_at.
 */
$sql = "INSERT INTO cart (user_id, item_id, quantity, created_at, updated_at)
        VALUES (?, ?, ?, NOW(), NOW())
        ON DUPLICATE KEY UPDATE
          quantity = quantity + VALUES(quantity),
          updated_at = NOW()";

$stmt = $conn->prepare($sql);
if (!$stmt) {
  jdie(false, ['message' => 'prepare failed: '.$conn->error], 500);
}
$stmt->bind_param('iii', $user_id, $item_id, $qty);

if (!$stmt->execute()) {
  jdie(false, ['message' => 'execute failed: '.$stmt->error], 500);
}

// نحاول إحضار السطر النهائي بعد العملية
$sel = $conn->prepare("SELECT id, user_id, item_id, quantity, created_at, updated_at FROM cart WHERE user_id=? AND item_id=? LIMIT 1");
$sel->bind_param('ii', $user_id, $item_id);
$sel->execute();
$res = $sel->get_result();

$row = $res ? $res->fetch_assoc() : null;
if (!$row) {
  // في حالات نادرة جدًا لو ما رجعش، نرجّع نجاح بدون تفاصيل
  jdie(true, ['message' => 'added', 'user_id' => $user_id, 'item_id' => $item_id, 'quantity_added' => $qty]);
}

// ممكن تحب ترجع السعر والإجمالي من items:
$price = null;
$pi = $conn->prepare("SELECT price FROM items WHERE id=? LIMIT 1");
$pi->bind_param('i', $item_id);
$pi->execute();
$pir = $pi->get_result();
if ($pir && ($pr = $pir->fetch_assoc())) {
  $price = (float)$pr['price'];
}

$out = [
  'message'   => 'added',
  'cart_item' => [
    'id'         => (int)$row['id'],
    'user_id'    => (int)$row['user_id'],
    'item_id'    => (int)$row['item_id'],
    'quantity'   => (int)$row['quantity'],
    'created_at' => $row['created_at'],
    'updated_at' => $row['updated_at'],
  ],
];

// لو عرفنا السعر، نحسب line_total الحالي
if ($price !== null) {
  $out['cart_item']['price'] = $price;
  $out['cart_item']['line_total'] = $price * (int)$row['quantity'];
}

jdie(true, $out);
