<?php
// iforenta_api/remove_favorite.php
declare(strict_types=1);

// ===== Headers / CORS =====
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require_once __DIR__ . '/config.php';
mysqli_set_charset($conn, 'utf8mb4');

// ===== Helpers =====
function jdie(bool $ok, array $data = [], int $code = 200): void {
  http_response_code($code);
  echo json_encode(['ok' => $ok] + $data, JSON_UNESCAPED_UNICODE);
  exit;
}
function intParam($arr, string $key, int $default = 0): int {
  if (!isset($arr[$key])) return $default;
  return (int)filter_var($arr[$key], FILTER_VALIDATE_INT, ['options'=>['default'=>$default]]);
}

// ===== Input =====
$input = json_decode(file_get_contents('php://input'), true);
if (json_last_error() === JSON_ERROR_NONE && is_array($input)) {
  $user_id = intParam($input, 'user_id');
  $item_id = intParam($input, 'item_id');
} else {
  $user_id = intParam($_POST, 'user_id');
  $item_id = intParam($_POST, 'item_id');
}

if ($user_id <= 0 || $item_id <= 0) {
  jdie(false, ['message' => 'invalid parameters'], 400);
}

// ===== Delete =====
$sql = "DELETE FROM favorites WHERE user_id=? AND item_id=? LIMIT 1";
$stmt = $conn->prepare($sql);
if (!$stmt) jdie(false, ['message' => 'prepare failed: '.$conn->error], 500);

$stmt->bind_param("ii", $user_id, $item_id);
if (!$stmt->execute()) {
  jdie(false, ['message' => 'execute failed: '.$stmt->error], 500);
}

if ($stmt->affected_rows > 0) {
  jdie(true, ['message' => 'removed']);
} else {
  jdie(true, ['message' => 'not found']);
}
