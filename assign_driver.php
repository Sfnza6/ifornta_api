<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
if ($_SERVER['REQUEST_METHOD']==='OPTIONS') exit;

require_once __DIR__.'/config.php';
mysqli_set_charset($conn,'utf8mb4');

/*
  يقبل أيّ من:
  - driver_id
  - driver_i
  ويكتب في أي عمود موجود من:
  - driver_i
  - driver_id
  ويضبط الحالة إلى assigned.
*/

$orderId  = (int)($_POST['order_id']  ?? $_GET['order_id']  ?? 0);
$driverId = (int)($_POST['driver_id'] ?? $_POST['driver_i'] ?? $_GET['driver_id'] ?? $_GET['driver_i'] ?? 0);

if ($orderId <= 0 || $driverId <= 0) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'message'=>'order_id و driver_id مطلوبة'], JSON_UNESCAPED_UNICODE);
  exit;
}

/* تحقّق من الأعمدة الموجودة في الجدول */
$colsRes = mysqli_query($conn, "SHOW COLUMNS FROM app_orders");
$has_driver_i  = false;
$has_driver_id = false;
if ($colsRes) {
  while ($r = mysqli_fetch_assoc($colsRes)) {
    if (strcasecmp($r['Field'], 'driver_i')  === 0) $has_driver_i  = true;
    if (strcasecmp($r['Field'], 'driver_id') === 0) $has_driver_id = true;
  }
}

/* ابنِ جملة التحديث حسب الأعمدة المتوفّرة */
$setParts = ["status='assigned'"];
$params   = [];
$types    = '';

if ($has_driver_i)  { $setParts[] = "driver_i=?";  $types .= 'i'; $params[] = $driverId; }
if ($has_driver_id) { $setParts[] = "driver_id=?"; $types .= 'i'; $params[] = $driverId; }

$setSql = implode(', ', $setParts);
$sql = "UPDATE app_orders SET $setSql WHERE id=?";

$types .= 'i';
$params[] = $orderId;

$st = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($st, $types, ...$params);
$ok = mysqli_stmt_execute($st);
$aff = mysqli_stmt_affected_rows($st);
mysqli_stmt_close($st);

/* اجلب بيانات السائق للرد */
$drv = null;
if ($ok) {
  $q = mysqli_query($conn, "SELECT id, name, phone FROM app_drivers WHERE id=".$driverId." LIMIT 1");
  if ($q) $drv = mysqli_fetch_assoc($q);
}

echo json_encode([
  'ok'          => (bool)$ok,
  'affected'    => (int)$aff,
  'order_id'    => $orderId,
  'driver_id'   => $driverId,
  'status'      => 'assigned',
  'driver_name' => $drv['name']  ?? null,
  'driver_phone'=> $drv['phone'] ?? null
], JSON_UNESCAPED_UNICODE);
