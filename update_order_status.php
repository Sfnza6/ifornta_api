<?php
// iforenta_api/update_order_status.php
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/config.php';
require_token_and_role($conn, ['admin','receiver']); // <<< يسمح للاثنين

if (!$conn) {
  http_response_code(500);
  echo json_encode(['status'=>'error','message'=>$GLOBALS['DB_CONNECT_ERROR'] ?? 'DB not initialized']);
  exit;
}

// نقرأ JSON من البودي
$input    = json_decode(file_get_contents('php://input'), true) ?: [];
$order_id = intval($input['order_id'] ?? 0);
$status   = trim($input['status'] ?? '');

// الحالات المسموح بها (عدّلها حسب نظامك)
$allowed = ['pending','assigned','preparing','picked_up','delivered','cancelled'];

if ($order_id <= 0 || !in_array($status, $allowed, true)) {
  http_response_code(400);
  echo json_encode(['status'=>'error','message'=>'invalid parameters']);
  exit;
}

// Prepared statement
$stmt = $conn->prepare("UPDATE orders SET status=? WHERE id=?");
$stmt->bind_param("si", $status, $order_id);

if ($stmt->execute()) {
  echo json_encode(['status'=>'success']);
} else {
  http_response_code(500);
  echo json_encode(['status'=>'error','message'=>$stmt->error]);
}
$stmt->close();
