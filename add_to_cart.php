<?php
// iforenta_api/add_to_cart.php
error_reporting(0);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

include 'config.php';

$input = json_decode(file_get_contents('php://input'), true);
if (json_last_error() === JSON_ERROR_NONE) {
  $user_id = intval($input['user_id'] ?? 0);
  $item_id = intval($input['item_id'] ?? 0);
  $qty     = intval($input['quantity'] ?? 1);
} else {
  $user_id = intval($_POST['user_id'] ?? 0);
  $item_id = intval($_POST['item_id'] ?? 0);
  $qty     = intval($_POST['quantity'] ?? 1);
}

if ($user_id <= 0 || $item_id <= 0 || $qty <= 0) {
  http_response_code(400);
  echo json_encode(['status'=>'error','message'=>'invalid parameters']);
  exit;
}

// إذا كانت الصنف موجود بالفعل نحدّث الكمية
$sqlCheck = "SELECT id, quantity FROM cart_items WHERE user_id=? AND item_id=?";
$stmt = $conn->prepare($sqlCheck);
$stmt->bind_param("ii", $user_id, $item_id);
$stmt->execute();
$res = $stmt->get_result();

if ($row = $res->fetch_assoc()) {
  $newQty = $row['quantity'] + $qty;
  $sqlUpd = "UPDATE cart_items SET quantity=? WHERE id=?";
  $st2 = $conn->prepare($sqlUpd);
  $st2->bind_param("ii", $newQty, $row['id']);
  $st2->execute();
  $cart_id = $row['id'];
} else {
  $sqlIns = "INSERT INTO cart_items (user_id, item_id, quantity) VALUES (?, ?, ?)";
  $st3 = $conn->prepare($sqlIns);
  $st3->bind_param("iii", $user_id, $item_id, $qty);
  $st3->execute();
  $cart_id = $st3->insert_id;
}

echo json_encode(['status'=>'success','cart_item_id'=>$cart_id]);
