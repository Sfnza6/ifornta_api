<?php
// iforenta_api/remove_cart_item.php
error_reporting(0);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

include 'config.php';

$input = json_decode(file_get_contents('php://input'), true);
$cart_id = intval($input['cart_item_id'] ?? ($_POST['cart_item_id'] ?? 0));

if ($cart_id <= 0) {
  http_response_code(400);
  echo json_encode(['status'=>'error','message'=>'invalid cart_item_id']);
  exit;
}

$sql = "DELETE FROM cart_items WHERE id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $cart_id);

if ($stmt->execute()) {
  echo json_encode(['status'=>'success']);
} else {
  http_response_code(500);
  echo json_encode(['status'=>'error','message'=>$stmt->error]);
}
