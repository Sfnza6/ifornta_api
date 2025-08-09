<?php
// iforenta_api/update_order_status.php
error_reporting(0);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

include 'config.php';

$input    = json_decode(file_get_contents('php://input'), true);
$order_id = intval($input['order_id'] ?? 0);
$status   = trim($input['status'] ?? '');

// قائمة الحالات المسموح بها
$allowed = ['pending','assigned','preparing','picked_up','delivered'];

if ($order_id <= 0 || !in_array($status, $allowed)) {
  http_response_code(400);
  echo json_encode(['status'=>'error','message'=>'invalid parameters']);
  exit;
}

$stmt = $conn->prepare("UPDATE orders SET status=? WHERE id=?");
$stmt->bind_param("si", $status, $order_id);

if ($stmt->execute()) {
  echo json_encode(['status'=>'success']);
} else {
  http_response_code(500);
  echo json_encode(['status'=>'error','message'=>$stmt->error]);
}
?>
