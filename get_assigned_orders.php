<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
if ($_SERVER['REQUEST_METHOD']==='OPTIONS') exit;

require_once __DIR__.'/config.php';
mysqli_set_charset($conn,'utf8mb4');

/*
  يرجع كل الطلبات بحالة assigned
  - اختيارياً: ?driver_id=ID أو ?driver_i=ID لتصفية سائق معيّن
  - يضم بيانات السائق + العميل
*/

$driverId = (int)($_GET['driver_id'] ?? $_GET['driver_i'] ?? 0);

/* نحدّد اسم العمود الفعلي (driver_i أو driver_id) */
$colsRes = mysqli_query($conn, "SHOW COLUMNS FROM app_orders");
$driverCol = null;
if ($colsRes) {
  while ($r = mysqli_fetch_assoc($colsRes)) {
    if (strcasecmp($r['Field'], 'driver_i')  === 0) $driverCol = 'driver_i';
    if (strcasecmp($r['Field'], 'driver_id') === 0) $driverCol = $driverCol ?? 'driver_id';
  }
}
if ($driverCol === null) { $driverCol = 'driver_id'; } // افتراضي

$sql = "
SELECT 
  o.id,
  o.user_id,
  o.address,
  o.payment_method,
  o.total,
  o.status,
  o.status_order,
  o.created_at,
  o.dest_lat, o.dest_lng, o.pickup_lat, o.pickup_lng,
  o.$driverCol AS driver_i,
  d.name  AS driver_name,
  d.phone AS driver_phone,
  u.username AS customer_name,
  u.phone    AS customer_phone
FROM app_orders o
LEFT JOIN app_drivers d ON d.id = o.$driverCol
LEFT JOIN app_users   u ON u.id = o.user_id
WHERE o.status = 'assigned' " . ($driverId > 0 ? " AND o.$driverCol = ? " : "") . "
ORDER BY o.created_at DESC
";

try {
  if ($driverId > 0) {
    $st = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($st, 'i', $driverId);
    mysqli_stmt_execute($st);
    $res = mysqli_stmt_get_result($st);
  } else {
    $res = mysqli_query($conn, $sql);
  }

  $rows = [];
  if ($res) {
    while ($r = mysqli_fetch_assoc($res)) {
      $r['total']    = (float)$r['total'];
      $r['driver_i'] = isset($r['driver_i']) ? (int)$r['driver_i'] : 0;
      $rows[] = $r;
    }
  }

  echo json_encode(['ok'=>true,'orders'=>$rows], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'msg'=>'DB_ERROR: '.$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
