<?php
// orders_version.php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/config.php';
if (!isset($conn) || !$conn) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'message'=>'DB not initialized'], JSON_UNESCAPED_UNICODE);
  exit;
}
mysqli_set_charset($conn, 'utf8mb4');

// لو عندك عمود updated_at تستخدمه، فهو أدق من max(id)
// مثال: SELECT UNIX_TIMESTAMP(MAX(updated_at)) AS v ...
$sql = "
  SELECT COALESCE(MAX(id), 0) AS v
  FROM app_orders
  WHERE status IN ('pending','processing')
";
$res = mysqli_query($conn, $sql);
$row = $res ? mysqli_fetch_assoc($res) : ['v'=>0];
$version = (int)($row['v'] ?? 0);

echo json_encode(['ok'=>true, 'v'=>$version], JSON_UNESCAPED_UNICODE);
