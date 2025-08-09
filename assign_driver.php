<?php
// iforenta_api/assign_driver.php
error_reporting(0);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

include 'config.php';

$input    = json_decode(file_get_contents('php://input'), true);
$order_id = intval($input['order_id'] ?? 0);
$driver_id= intval($input['driver_id'] ?? 0);

if ($order_id <= 0 || $driver_id <= 0) {
  http_response_code(400);
  echo json_encode(['status'=>'error','message'=>'invalid parameters']);
  exit;
}

$stmt = $conn->prepare("UPDATE orders SET driver_id=?, status='assigned' WHERE id=?");
$stmt->bind_param("ii", $driver_id, $order_id);

if ($stmt->execute()) {
  echo json_encode(['status'=>'success']);
} else {
  http_response_code(500);
  echo json_encode(['status'=>'error','message'=>$stmt->error]);
}
