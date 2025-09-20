<?php
// iforenta_api/order_status.php
error_reporting(0);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

include 'config.php';
require_token_and_role($conn, ['admin','receiver']); // <<< يسمح للاثنين

$order_id = intval($_GET['order_id'] ?? 0);
if ($order_id <= 0) {
  http_response_code(400);
  echo json_encode(['status'=>'error','message'=>'invalid order_id']);
  exit;
}

$stmt = $conn->prepare("SELECT status FROM orders WHERE id=?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$res = $stmt->get_result();

if ($row = $res->fetch_assoc()) {
  echo json_encode(['status' => $row['status']]);
} else {
  http_response_code(404);
  echo json_encode(['status'=>'error','message'=>'not found']);
}
?>
