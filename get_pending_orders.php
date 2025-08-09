<?php
// get_pending_orders.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
include 'config.php';

// جلب الطلبات المعلقة (status = 'pending' و driver_id IS NULL)
$sql = "
  SELECT 
    o.id, 
    o.user_id, 
    o.address, 
    SUM(oi.quantity * fi.price) AS total
  FROM orders o
  JOIN order_items oi ON oi.order_id = o.id
  JOIN items fi       ON fi.id = oi.item_id   -- غيّر هنا from food_items إلى items
  WHERE o.status = 'pending'
    AND o.driver_id IS NULL
  GROUP BY o.id
";

$result = $conn->query($sql);
$list = [];

while ($row = $result->fetch_assoc()) {
    $list[] = [
      'id'       => (int)$row['id'],
      'user_id'  => (int)$row['user_id'],
      'address'  => $row['address'],
      'total'    => floatval($row['total']),
    ];
}

echo json_encode($list);
