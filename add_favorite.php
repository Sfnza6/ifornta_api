<?php
// iforenta_api/add_favorite.php
declare(strict_types=1);

// إعدادات الهيدر
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require_once __DIR__ . '/config.php';
mysqli_set_charset($conn, 'utf8mb4');

// دالة مساعدة
function jdie(bool $ok, array $data = [], int $code = 200): void {
  http_response_code($code);
  echo json_encode(['ok' => $ok] + $data, JSON_UNESCAPED_UNICODE);
  exit;
}

// قراءة الإدخال (JSON أو POST)
$input = json_decode(file_get_contents('php://input'), true);
if (json_last_error() === JSON_ERROR_NONE) {
  $user_id = (int)($input['user_id'] ?? 0);
  $item_id = (int)($input['item_id'] ?? 0);
} else {
  $user_id = (int)($_POST['user_id'] ?? 0);
  $item_id = (int)($_POST['item_id'] ?? 0);
}

if ($user_id <= 0 || $item_id <= 0) {
  jdie(false, ['message' => 'invalid parameters'], 400);
}

// التحقق إن العنصر موجود أصلاً
$sqlChk = "SELECT id FROM favorites WHERE user_id=? AND item_id=? LIMIT 1";
$stmt = $conn->prepare($sqlChk);
$stmt->bind_param("ii", $user_id, $item_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res && $res->fetch_assoc()) {
  jdie(true, ['message' => 'already exists']);
}

// إدخال جديد
$sql = "INSERT INTO favorites (user_id, item_id, created_at) VALUES (?, ?, NOW())";
$stmt2 = $conn->prepare($sql);
$stmt2->bind_param("ii", $user_id, $item_id);

if ($stmt2->execute()) {
  jdie(true, [
    'message' => 'added',
    'fav_id'  => $stmt2->insert_id
  ]);
} else {
  jdie(false, ['message' => $stmt2->error], 500);
}
