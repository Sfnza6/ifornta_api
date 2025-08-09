<?php
// iforenta_api/create_order.php
header('Content-Type: application/json');
include 'config.php';

// قراءة المدخلات، فتِّش على صحة user_id, items (JSON), address...
$input   = json_decode(file_get_contents('php://input'), true);
$user_id = intval($input['user_id'] ?? 0);
$address = trim($input['address'] ?? '');
// items هي قائمة من {item_id,quantity}
$items   = $input['items'] ?? [];

if ($user_id <= 0 || empty($items) || $address === '') {
  http_response_code(400);
  echo json_encode(['status'=>'error','message'=>'invalid parameters']);
  exit;
}

// 1) أنشئ الصفْ في جدول orders
$stmt = $conn->prepare("INSERT INTO orders (user_id, address) VALUES (?, ?)");
$stmt->bind_param("is", $user_id, $address);
$stmt->execute();
$orderId = $conn->insert_id;

// 2) كرّر على كل عنصر وأضف إلى order_items
$stmt2 = $conn->prepare("INSERT INTO order_items (order_id, item_id, quantity) VALUES (?, ?, ?)");
foreach ($items as $it) {
  $item_id = intval($it['item_id']);
  $quantity = intval($it['quantity']);
  $stmt2->bind_param("iii", $orderId, $item_id, $quantity);
  $stmt2->execute();
}

// 3) أرجع JSON يضم order_id كعدد
echo json_encode([
  'status'   => 'success',
  'order_id' => (int)$orderId
]);
