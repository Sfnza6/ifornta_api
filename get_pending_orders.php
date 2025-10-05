<?php
// get_pending_orders.php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/config.php';
mysqli_set_charset($conn, 'utf8mb4');

$sql = "SELECT id, user_id, address, payment_method, total, status, created_at, status_order
        FROM app_orders
        WHERE status='pending'
        ORDER BY created_at ASC, id ASC";
$res = mysqli_query($conn, $sql);

$rows = [];
while ($r = mysqli_fetch_assoc($res)) {
  $rows[] = [
    'id' => (int)$r['id'],
    'user_id' => (int)$r['user_id'],
    'address' => (string)($r['address'] ?? ''),
    'payment_method' => (int)($r['payment_method'] ?? 0),
    'total' => (float)($r['total'] ?? 0),
    'status' => (string)$r['status'],
    'status_order' => (string)($r['status_order'] ?? ''),
    'created_at' => (string)($r['created_at'] ?? ''),
  ];
}

echo json_encode(['ok'=>true, 'orders'=>$rows], JSON_UNESCAPED_UNICODE);
