<?php
// get_order_items.php
// يرجّع عناصر الطلب من جدول app_order_items
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require_once __DIR__ . '/config.php';
if (!isset($conn) || !$conn) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'message'=>'DB not initialized'], JSON_UNESCAPED_UNICODE);
  exit;
}
mysqli_set_charset($conn, 'utf8mb4');

/** order_id من GET أو POST */
$order_id = 0;
if (isset($_GET['order_id'])) {
  $order_id = (int)$_GET['order_id'];
} elseif (isset($_POST['order_id'])) {
  $order_id = (int)$_POST['order_id'];
}

if ($order_id <= 0) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'message'=>'order_id مطلوب'], JSON_UNESCAPED_UNICODE);
  exit;
}

/**
 * نقرأ الحقول مطابقة للجدول:
 * item_id, title, quantity, unit_price, line_total
 * ونضمن حساب line_total لو كان NULL عبر COALESCE
 */
$sql = "
  SELECT 
    item_id,
    title,
    quantity,
    unit_price,
    COALESCE(line_total, quantity * unit_price) AS line_total
  FROM app_order_items
  WHERE order_id = ?
  ORDER BY id ASC
";

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'message'=>'Prepare failed: '.mysqli_error($conn)], JSON_UNESCAPED_UNICODE);
  exit;
}

mysqli_stmt_bind_param($stmt, 'i', $order_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$items = [];
if ($res) {
  while ($r = mysqli_fetch_assoc($res)) {
    $items[] = [
      'item_id'    => (int)($r['item_id'] ?? 0),
      'title'      => (string)($r['title'] ?? ''),
      'quantity'   => (int)($r['quantity'] ?? 0),
      'unit_price' => (float)($r['unit_price'] ?? 0),
      'line_total' => (float)($r['line_total'] ?? 0),
    ];
  }
}

// نجاح حتى لو بدون عناصر
echo json_encode(['ok'=>true, 'items'=>$items], JSON_UNESCAPED_UNICODE);
