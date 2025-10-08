<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: POST, OPTIONS');

ini_set('display_errors', '0');
error_reporting(E_ALL);

// التقط أي Fatal كـ JSON
register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        echo json_encode([
            'status'  => 'error',
            'message' => 'Fatal: '.$e['message'].' at '.$e['file'].':'.$e['line']
        ], JSON_UNESCAPED_UNICODE);
    }
});

require_once __DIR__ . '/config.php';

if (!isset($conn) || !$conn) {
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>'DB not initialized'], JSON_UNESCAPED_UNICODE);
    exit;
}

$id         = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$current    = trim($_POST['current_password'] ?? '');
$newpass    = trim($_POST['new_password'] ?? '');

if ($id <= 0 || $current === '' || $newpass === '') {
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>'id/current_password/new_password required'], JSON_UNESCAPED_UNICODE);
    exit;
}

// جلب الهاش الحالي
$stmt = $conn->prepare("SELECT password_hash FROM admins WHERE id = ? LIMIT 1");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>'prepare: '.$conn->error], JSON_UNESCAPED_UNICODE);
    exit;
}
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
if (!($row = $res->fetch_assoc())) {
    http_response_code(404);
    echo json_encode(['status'=>'error','message'=>'user not found'], JSON_UNESCAPED_UNICODE);
    $stmt->close();
    exit;
}
$hash = $row['password_hash'] ?? '';
$stmt->close();

// تحقق من كلمة السر الحالية
if (!password_verify($current, $hash)) {
    http_response_code(403);
    echo json_encode(['status'=>'error','message'=>'current_password_invalid'], JSON_UNESCAPED_UNICODE);
    exit;
}

// تحديث كلمة السر (بدون updated_at لأن الجدول لا يحتويه)
$newhash = password_hash($newpass, PASSWORD_BCRYPT);

$u = $conn->prepare("UPDATE admins SET password_hash = ? WHERE id = ? LIMIT 1");
if (!$u) {
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>'prepare: '.$conn->error], JSON_UNESCAPED_UNICODE);
    exit;
}
$u->bind_param('si', $newhash, $id);
$ok = $u->execute();
$err = $u->error;
$u->close();

if ($ok) {
    echo json_encode(['status'=>'success'], JSON_UNESCAPED_UNICODE);
} else {
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>$err], JSON_UNESCAPED_UNICODE);
}
