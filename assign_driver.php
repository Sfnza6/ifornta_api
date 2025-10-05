<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
if ($_SERVER['REQUEST_METHOD']==='OPTIONS') exit;

require_once __DIR__.'/config.php';
mysqli_set_charset($conn,'utf8mb4');

$orderId = (int)($_POST['order_id'] ?? $_GET['order_id'] ?? 0);
$driverId= (int)($_POST['driver_id']?? $_GET['driver_id']?? 0);
if ($orderId<=0 || $driverId<=0){
  http_response_code(400); echo json_encode(['ok'=>false,'message'=>'order_id و driver_id مطلوبة']); exit;
}

$st = mysqli_prepare($conn,"UPDATE app_orders SET driver_id=?, status='assigned' WHERE id=?");
mysqli_stmt_bind_param($st,'ii',$driverId,$orderId);
$ok = mysqli_stmt_execute($st);
mysqli_stmt_close($st);

echo json_encode(['ok'=>$ok,'order_id'=>$orderId,'driver_id'=>$driverId,'status'=>'assigned'], JSON_UNESCAPED_UNICODE);
