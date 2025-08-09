<?php
// iforenta_api/get_cart.php
error_reporting(0);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

include 'config.php';

$user_id = intval($_GET['user_id'] ?? 0);
if ($user_id <= 0) {
  http_response_code(400);
  echo json_encode(['error'=>'invalid user_id']);
  exit;
}

$sql = "
  SELECT c.id AS cart_item_id,
         c.quantity,
         i.id AS item_id,
         i.name,
         i.price,
         i.image_url,
         i.description,
         i.category_id
  FROM cart_items c
  JOIN items i ON c.item_id = i.id
  WHERE c.user_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

$cart = [];
while ($row = $res->fetch_assoc()) {
  $cart[] = $row;
}
echo json_encode($cart);
