<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config.php';
// استقبل القيم
$driver_id = isset($_GET['driver_id']) ? (int)$_GET['driver_id'] : 0;
$status    = isset($_GET['status'])    ? trim(strtolower($_GET['status'])) : '';

if ($driver_id <= 0) {
  echo json_encode(['ok'=>false,'msg'=>'معرّف السائق غير صالح'], JSON_UNESCAPED_UNICODE);
  exit;
}

try {
  // الاستعلام الأساسي
  $sql = "SELECT id, address, total, status, payment_method, 
                 dest_lat, dest_lng, pickup_lat, pickup_lng, created_at
          FROM app_orders
          WHERE driver_id = :driver_id";

  $params = [':driver_id' => $driver_id];

  // لو فيه status نفلتر به
  if ($status !== '') {
    $sql .= " AND LOWER(TRIM(status)) = :status";
    $params[':status'] = $status;
  }

  $sql .= " ORDER BY id DESC";

  $st = $pdo->prepare($sql);
  $st->execute($params);
  $orders = $st->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode([
    'ok'     => true,
    'orders' => $orders,
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  echo json_encode(['ok'=>false,'msg'=>'خطأ: '.$e->getMessage()], JSON_UNESCAPED_UNICODE);
}