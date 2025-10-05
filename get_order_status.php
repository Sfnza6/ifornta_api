<?php
// get_order_status.php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/config.php';
mysqli_set_charset($conn, 'utf8mb4');

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($order_id <= 0) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'message'=>'order_id مطلوب'], JSON_UNESCAPED_UNICODE);
    exit;
}

$sql = "SELECT status FROM app_orders WHERE id = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $order_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($res)) {
    echo json_encode(['ok'=>true, 'status'=>$row['status'] ?: 'pending'], JSON_UNESCAPED_UNICODE);
} else {
    http_response_code(404);
    echo json_encode(['ok'=>false, 'message'=>'الطلب غير موجود'], JSON_UNESCAPED_UNICODE);
}
