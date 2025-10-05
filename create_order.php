<?php
// create_order.php — inserts into app_orders + app_order_items (with transaction)
// يكتشف عمود اسم المنتج في جدول items ديناميكيًا (title | name | item_name)
declare(strict_types=1);

// ===== Headers / CORS =====
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

// لا تظهر أخطاء HTML
ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/config.php';
if (!isset($conn) || !$conn) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'message'=>'DB not initialized'], JSON_UNESCAPED_UNICODE);
  exit;
}
mysqli_set_charset($conn, 'utf8mb4');

// ===== Read JSON body if provided =====
$raw = file_get_contents('php://input') ?: '';
$asJson = null;
if (isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
  $asJson = json_decode($raw, true);
}

$IN = static function(string $key, $default = null) use ($asJson) {
  if (is_array($asJson) && array_key_exists($key, $asJson)) return $asJson[$key];
  if (isset($_POST[$key])) return $_POST[$key];
  return $default;
};

// ===== Inputs =====
$user_id      = (int)($IN('user_id', 0));
$address      = trim((string)$IN('address', ''));
$payment      = $IN('payment', $IN('payment_method', 0)); // 0/1
$payment      = is_numeric($payment) ? (int)$payment : 0;
$total_raw    = (string)$IN('total', '0');
$total        = is_numeric($total_raw) ? (float)$total_raw : 0.0;

$status_order = strtolower(trim((string)$IN('status_order', 'delivery'))); // delivery|pickup
if ($status_order !== 'delivery' && $status_order !== 'pickup') $status_order = 'delivery';

// geo (اختياري — نخزن 0.0 إن لم تصل)
$dest_lat   = $IN('dest_lat', null);
$dest_lng   = $IN('dest_lng', null);
$pickup_lat = $IN('pickup_lat', null);
$pickup_lng = $IN('pickup_lng', null);

$dest_lat   = is_null($dest_lat)   ? 0.0 : (float)$dest_lat;
$dest_lng   = is_null($dest_lng)   ? 0.0 : (float)$dest_lng;
$pickup_lat = is_null($pickup_lat) ? 0.0 : (float)$pickup_lat;
$pickup_lng = is_null($pickup_lng) ? 0.0 : (float)$pickup_lng;

// items: نقبل items_json (نص)، أو items كمصفوفة/نص JSON
$items = [];
if (is_array($asJson) && isset($asJson['items'])) {
  $items = is_array($asJson['items']) ? $asJson['items'] : (json_decode((string)$asJson['items'], true) ?: []);
} else {
  $items_json_in = $IN('items_json', $IN('items', ''));
  if (is_string($items_json_in) && $items_json_in !== '') {
    $decoded = json_decode($items_json_in, true);
    if (is_array($decoded)) $items = $decoded;
  } elseif (is_array($IN('items', []))) {
    $items = (array)$IN('items', []);
  }
}

// ===== Validation =====
if ($user_id <= 0) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'message'=>'user_id مطلوب'], JSON_UNESCAPED_UNICODE);
  exit;
}
if ($total <= 0) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'message'=>'قيمة الإجمالي غير صالحة'], JSON_UNESCAPED_UNICODE);
  exit;
}
if ($status_order === 'delivery' && $address === '') {
  http_response_code(400);
  echo json_encode(['ok'=>false,'message'=>'العنوان مطلوب للتوصيل'], JSON_UNESCAPED_UNICODE);
  exit;
}

/**
 * Helper: معرفة اسم عمود عنوان المنتج داخل جدول items
 * سنبحث في INFORMATION_SCHEMA عن أول تطابق من هذه القائمة.
 */
function detect_items_title_column(mysqli $conn, string $table = 'items'): string {
  $candidates = ['title','name','item_name'];
  $dbRes = mysqli_query($conn, "SELECT DATABASE() AS db");
  $rowDb = $dbRes ? mysqli_fetch_assoc($dbRes) : null;
  $dbName = $rowDb ? $rowDb['db'] : null;
  if (!$dbName) return 'title';

  foreach ($candidates as $col) {
    $sql = "SELECT COUNT(*) AS c
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = ?
              AND TABLE_NAME   = ?
              AND COLUMN_NAME  = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) continue;
    mysqli_stmt_bind_param($stmt, 'sss', $dbName, $table, $col);
    mysqli_stmt_execute($stmt);
    $r = mysqli_stmt_get_result($stmt);
    if ($r && ($rw = mysqli_fetch_assoc($r)) && (int)$rw['c'] > 0) {
      return $col;
    }
  }
  return 'title'; // افتراضي
}

$titleColumn = detect_items_title_column($conn, 'items');

// ===== Insert (transaction) =====
mysqli_begin_transaction($conn);
try {
  // 1) app_orders
  $status = 'pending';
  $sql = "INSERT INTO app_orders
          (user_id, driver_id, address, payment_method, total, status, created_at, status_order, dest_lat, dest_lng, pickup_lat, pickup_lng)
          VALUES (?, NULL, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?)";
  $stmt = mysqli_prepare($conn, $sql);
  if (!$stmt) throw new Exception('Prepare failed (orders): '.mysqli_error($conn));

  // types: i s i d s s d d d d
  mysqli_stmt_bind_param(
    $stmt,
    'isidssdddd',
    $user_id,         // i
    $address,         // s
    $payment,         // i
    $total,           // d
    $status,          // s
    $status_order,    // s
    $dest_lat,        // d
    $dest_lng,        // d
    $pickup_lat,      // d
    $pickup_lng       // d
  );
  if (!mysqli_stmt_execute($stmt)) {
    throw new Exception('Execute failed (orders): '.mysqli_stmt_error($stmt));
  }
  $order_id = (int)mysqli_insert_id($conn);

  // 2) app_order_items (optional if items provided)
  if (!empty($items) && is_array($items)) {
    // stmt لجلب اسم المنتج من جدول items حسب اسم العمود المكتشف
    $stmtName = mysqli_prepare(
      $conn,
      "SELECT `$titleColumn` AS t FROM `items` WHERE `id` = ? LIMIT 1"
    );

    // إدراج العناصر (لاحظ الأعمدة الصريحة)
    $stmtIt = mysqli_prepare(
      $conn,
      "INSERT INTO `app_order_items` (`order_id`, `item_id`, `title`, `quantity`, `unit_price`)
       VALUES (?,?,?,?,?)"
    );
    if (!$stmtIt) throw new Exception('Prepare failed (app_order_items): '.mysqli_error($conn));

    foreach ($items as $it) {
      $item_id    = 0;
      if (isset($it['item_id'])) $item_id = (int)$it['item_id'];
      elseif (isset($it['id']))  $item_id = (int)$it['id'];

      $quantity   = 1;
      if (isset($it['quantity'])) $quantity = (int)$it['quantity'];
      elseif (isset($it['qty']))  $quantity = (int)$it['qty'];

      $unit_price = 0.0;
      if (isset($it['unit_price'])) $unit_price = (float)$it['unit_price'];
      elseif (isset($it['price']))  $unit_price = (float)$it['price'];

      // عنوان المنتج (snapshot): من البيلود أو من جدول items أو افتراضي
      $title = '';
      if (!empty($it['title'])) {
        $title = (string)$it['title'];
      } else {
        if ($stmtName) {
          mysqli_stmt_bind_param($stmtName, 'i', $item_id);
          mysqli_stmt_execute($stmtName);
          $r = mysqli_stmt_get_result($stmtName);
          if ($r && ($row = mysqli_fetch_assoc($r))) {
            $title = (string)($row['t'] ?? '');
          }
        }
        if ($title === '') $title = 'صنف #'.$item_id;
      }

      mysqli_stmt_bind_param($stmtIt, 'iisid', $order_id, $item_id, $title, $quantity, $unit_price);
      if (!mysqli_stmt_execute($stmtIt)) {
        throw new Exception('Execute failed (app_order_items): '.mysqli_stmt_error($stmtIt));
      }
    }
  }

  mysqli_commit($conn);
  echo json_encode(['ok'=>true, 'order_id'=>$order_id], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  mysqli_rollback($conn);
  http_response_code(500);
  echo json_encode(['ok'=>false, 'message'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
