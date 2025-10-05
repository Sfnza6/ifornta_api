<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config.php';
$driver_id = isset($_GET['driver_id']) ? intval($_GET['driver_id']) : 0;
if ($driver_id <= 0) {
  echo json_encode(['ok'=>false,'msg'=>'driver_id مفقود أو غير صالح'], JSON_UNESCAPED_UNICODE); exit;
}

try {
  $sql = "SELECT o.id, o.address, o.payment_method, o.total, o.status, o.status_order,
                 o.dest_lat, o.dest_lng, o.pickup_lat, o.pickup_lng,
                 u.username AS customer_name, u.phone AS customer_phone,
                 DATE_FORMAT(o.created_at, '%Y-%m-%d %H:%i:%s') AS created_at
          FROM app_orders o
          LEFT JOIN app_users u ON u.id = o.user_id
          WHERE o.driver_id=?
          ORDER BY o.created_at DESC";
  $st = $pdo->prepare($sql);
  $st->execute([$driver_id]);
  $rows = $st->fetchAll();

  echo json_encode(['ok'=>true,'orders'=>$rows], JSON_UNESCAPED_UNICODE);

} catch(Throwable $e){
  echo json_encode(['ok'=>false,'msg'=>'خطأ في الخادم: '.$e->getMessage()], JSON_UNESCAPED_UNICODE);
}