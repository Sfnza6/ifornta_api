<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/config.php';

$sql = "
  SELECT
    id,
    name,
    phone,
    password_hash AS password,  -- alias لكي يبقى التطبيق متوافق
    avatar_url,
    role,
    api_token,
    is_active,
    created_at
  FROM admins
  ORDER BY id DESC
";

$res = $conn->query($sql);

$data = [];
if ($res) {
  while ($row = $res->fetch_assoc()) {
    $data[] = $row;
  }
  echo json_encode(['status' => 'success', 'data' => $data], JSON_UNESCAPED_UNICODE);
} else {
  http_response_code(500);
  echo json_encode(['status'=>'error','message'=>'DB: '.$conn->error], JSON_UNESCAPED_UNICODE);
}
