<?php
// iforenta_api/delete_item.php
declare(strict_types=1);

// ===== Headers & CORS =====
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

ini_set('display_errors', '0');      // لا تطبع HTML errors
error_reporting(E_ALL);

// ردّ على OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// دالة رد JSON نظيف
function respond(int $code, array $data): void {
    if (function_exists('ob_get_length') && ob_get_length()) { @ob_clean(); }
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// التقط الـ Fatal وأعد JSON
register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        respond(500, ['status' => 'error', 'message' => 'Fatal: '.$e['message']]);
    }
});

// اسمح فقط بـ POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['status' => 'error', 'message' => 'Method not allowed']);
}

require_once __DIR__ . '/config.php'; // تأكد أن هذا الملف لا يطبع أي شيء

if (!isset($conn) || !$conn) {
    respond(500, ['status' => 'error', 'message' => ($GLOBALS['DB_CONNECT_ERROR'] ?? 'DB not initialized')]);
}

// جدول وعمود المفتاح
$table = 'items';
$pk    = 'id';

// استلام id من JSON أو POST
$raw = file_get_contents('php://input');
$payload = json_decode((string)$raw, true);
$id = 0;

if (is_array($payload) && isset($payload['id'])) {
    $id = (int)$payload['id'];
} elseif (isset($_POST['id'])) {
    $id = (int)$_POST['id'];
}

if ($id <= 0) {
    respond(400, ['status' => 'error', 'message' => 'invalid id']);
}

// حضّر ونفّذ
$stmt = $conn->prepare("DELETE FROM {$table} WHERE {$pk} = ?");
if (!$stmt) {
    respond(500, ['status' => 'error', 'message' => $conn->error]);
}
$stmt->bind_param('i', $id);

if (!$stmt->execute()) {
    $err = $stmt->error ?: 'Delete failed';
    $stmt->close();
    respond(500, ['status' => 'error', 'message' => $err]);
}

// تحقّق من عدد الصفوف المتأثرة
$affected = $stmt->affected_rows;
$stmt->close();

if ($affected > 0) {
    respond(200, ['status' => 'success']);
} else {
    // لم يتم العثور على السجل
    respond(404, ['status' => 'error', 'message' => 'not found']);
}

// ملاحظة: لا تضع وسم إغلاق PHP لتجنّب أي مسافات إضافية.
