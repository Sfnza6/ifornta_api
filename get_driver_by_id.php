<?php
// iforenta_api/get_driver_by_id.php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

ini_set('display_errors','0'); error_reporting(E_ALL);

// تحويل أي Fatal إلى JSON نظيف
register_shutdown_function(function () {
  $e = error_get_last();
  if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>'Fatal: '.$e['message']], JSON_UNESCAPED_UNICODE);
  }
});

require_once __DIR__.'/config.php';

if (!isset($conn) || !$conn) {
  http_response_code(500);
  echo json_encode(['status'=>'error','message'=>$GLOBALS['DB_CONNECT_ERROR'] ?? 'DB not initialized'], JSON_UNESCAPED_UNICODE);
  exit;
}

$driver_id = intval($_GET['driver_id'] ?? 0);
if ($driver_id <= 0) {
  http_response_code(400);
  echo json_encode(['status'=>'error','message'=>'invalid driver_id'], JSON_UNESCAPED_UNICODE);
  exit;
}

$stmt = $conn->prepare("SELECT id, name, phone, password, created_at FROM app_drivers WHERE id=? LIMIT 1");
if (!$stmt) {
  http_response_code(500);
  echo json_encode(['status'=>'error','message'=>$conn->error], JSON_UNESCAPED_UNICODE);
  exit;
}
$stmt->bind_param('i', $driver_id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

if (!$row) {
  echo json_encode(['status'=>'success','data'=>null], JSON_UNESCAPED_UNICODE);
  exit;
}

echo json_encode(['status'=>'success','data'=>$row], JSON_UNESCAPED_UNICODE);
