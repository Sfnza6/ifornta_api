<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

require_once __DIR__ . '/config.php';
if (!isset($conn) || !$conn) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'message'=>'DB not initialized'], JSON_UNESCAPED_UNICODE);
  exit;
}
mysqli_set_charset($conn,'utf8mb4');

function readv($k,$d=''){ return $_POST[$k] ?? $_GET[$k] ?? $d; }
function norm(string $s): string {
  $x = strtolower(trim($s));
  // لو استعملت approved/accepted/preparing في أي مكان
  if (in_array($x, ['approved','accepted','preparing','in_prep','readying'], true)) return 'processing';
  return $x;
}

$orderId = (int) readv('order_id','0');
$action  = strtolower(readv('action',''));
$target  = strtolower(readv('status',''));

if ($orderId <= 0) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'message'=>'order_id مطلوب'], JSON_UNESCAPED_UNICODE);
  exit;
}

// اجلب الحالة الحالية (لإرجاع prev_status)
$prev = null;
$sel = mysqli_prepare($conn, "SELECT status FROM app_orders WHERE id=? LIMIT 1");
mysqli_stmt_bind_param($sel, 'i', $orderId);
mysqli_stmt_execute($sel);
$res = mysqli_stmt_get_result($sel);
if (!($row = mysqli_fetch_assoc($res))) {
  http_response_code(404);
  echo json_encode(['ok'=>false,'message'=>'الطلب غير موجود'], JSON_UNESCAPED_UNICODE);
  exit;
}
$prev = (string)$row['status'];
mysqli_free_result($res);
mysqli_stmt_close($sel);

// الحالات المسموحة للتعيين المباشر
$allowed = ['pending','processing','approved','assigned','delivering','delivered','success','cancelled'];

switch ($action) {
  case 'approve':
    $new = 'processing'; // جاري التحضير
    break;
  case 'reject':
    $new = 'cancelled';
    break;
  case 'set':
    if (!$target || !in_array($target, $allowed, true)) {
      http_response_code(400);
      echo json_encode(['ok'=>false,'message'=>'status غير مسموح'], JSON_UNESCAPED_UNICODE);
      exit;
    }
    $new = $target;
    break;
  // (اختياري) دعم مباشر لتكليف سائق:
  case 'assign':
    $new = 'assigned';
    break;
  default:
    http_response_code(400);
    echo json_encode(['ok'=>false,'message'=>'action غير مدعوم'], JSON_UNESCAPED_UNICODE);
    exit;
}

// نفّذ التحديث (أضِف updated_at لو عندك عمود)
$sql = "UPDATE app_orders SET status=? WHERE id=?";
$st  = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($st, 'si', $new, $orderId);
$ok = mysqli_stmt_execute($st);
$err = mysqli_stmt_error($st);
$aff = mysqli_stmt_affected_rows($st);
mysqli_stmt_close($st);

if (!$ok) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'message'=>'DB error: '.$err], JSON_UNESCAPED_UNICODE);
  exit;
}

// (اختياري) ارفع نسخة “orders_version” لو عندك جدول app_meta
// mysqli_query($conn, "UPDATE app_meta SET value=IFNULL(value,0)+1 WHERE name='orders_version'");

// رجّع الرد
echo json_encode([
  'ok'               => true,
  'order_id'         => $orderId,
  'prev_status'      => $prev,
  'status'           => $new,
  'normalized_status'=> norm($new),
  'affected'         => $aff,
], JSON_UNESCAPED_UNICODE);
