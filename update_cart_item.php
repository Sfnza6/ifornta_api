<?php
// iforenta_api/update_cart_item.php
error_reporting(0);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

include 'config.php';

$input = json_decode(file_get_contents('php://input'), true);
if (json_last_error() === JSON_ERROR_NONE) {
  $cart_id = intval($input['cart_item_id'] ?? 0);
  $qty     = intval($input['quantity'] ?? 1);
} else {
  $cart_id = intval($_POST['cart_item_id'] ?? 0);
  $qty     = intval($_POST['quantity'] ?? 1);
}

if ($cart_id <= 0 || $qty <= 0) {
  http_response_code(400);
  echo json_encode(['status'=>'error','message'=>'invalid parameters']);
  exit;
}

$sql = "UPDATE cart_items SET quantity=? WHERE id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $qty, $cart_id);

if ($stmt->execute()) {
  echo json_encode(['status'=>'success']);
} else {
  http_response_code(500);
  echo json_encode(['status'=>'error','message'=>$stmt->error]);
}
