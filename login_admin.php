<?php
// login_admin.php
declare(strict_types=1);

// ===== Headers / CORS =====
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

// لا تطبع أخطاء HTML للعميل
ini_set('display_errors', '0');
error_reporting(E_ALL);

// التقط الـ Fatal وأعد JSON
register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        http_response_code(500);
        echo json_encode(['status'=>'error','message'=>'Fatal: '.$e['message']], JSON_UNESCAPED_UNICODE);
    }
});

require_once __DIR__ . '/config.php';
if (!isset($conn) || !$conn) {
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>'DB not initialized'], JSON_UNESCAPED_UNICODE);
    exit;
}

// دالة إخراج موحدة
function out(array $arr, int $code = 200): void {
    http_response_code($code);
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit;
}

// اقرأ الجسم JSON أو form-data
$body  = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) { $body = $_POST; }

$phone = trim((string)($body['phone'] ?? ''));
$pass  = (string)($body['password'] ?? '');

if ($phone === '' || $pass === '') {
    out(['status'=>'error','message'=>'phone/password required'], 400);
}

// اجلب المستخدم
$sql = "SELECT id,name,phone,password_hash,avatar_url,role,is_active FROM admins WHERE phone=? LIMIT 1";
$stmt = $conn->prepare($sql);
if (!$stmt) out(['status'=>'error','message'=>$conn->error], 500);
$stmt->bind_param('s', $phone);
$stmt->execute();
$res = $stmt->get_result();

if (!$res || $res->num_rows === 0) {
    out(['status'=>'error','message'=>'Invalid credentials'], 401);
}
$row = $res->fetch_assoc();

// تأكد أنه نشط
if ((int)$row['is_active'] !== 1) {
    out(['status'=>'error','message'=>'Inactive account'], 401);
}

// تحقق كلمة السر
$hash = (string)$row['password_hash'];
$ok   = false;

// 1) Bcrypt (الموصى به)
if (!$ok && $hash !== '' && str_starts_with($hash, '$2y$')) {
    $ok = password_verify($pass, $hash);
}

// 2) توافق مع كلمات قديمة sha1 أو md5
if (!$ok) {
    // بعض أنظمة MySQL تُخزن SHA1 بصيغة HEX كبيرة بدون بادئة
    $sha1 = strtoupper(sha1($pass));
    $md5  = md5($pass);
    if (hash_equals($hash, $sha1) || hash_equals($hash, $md5)) {
        $ok = true;
    }
}

if (!$ok) {
    out(['status'=>'error','message'=>'Invalid credentials'], 401);
}

// أنشئ توكن وخزّنه
$token = bin2hex(random_bytes(32));
$u = $conn->prepare("UPDATE admins SET api_token=? WHERE id=?");
$uid = (int)$row['id'];
$u->bind_param('si', $token, $uid);
$u->execute();

// ابنِ URL مطلق للصورة إن لزم
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base   = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');     // /api
$root   = rtrim("$scheme://$host$base/..", '/');                    // جذر المشروع

$avatar = (string)($row['avatar_url'] ?? '');
if ($avatar !== '' && !preg_match('~^https?://~i', $avatar)) {
    // لو المسار نسبي خزّنه كـ URL مطلق
    $avatar = $root . '/' . ltrim($avatar, '/');
}

// استجابة النجاح
out([
    'status' => 'success',
    'token'  => $token,
    // لاحظ: الحقل admin موحّد ليستوعب أكواد Flutter الحالية
    'admin'  => [
        'id'         => (int)$row['id'],
        'name'       => (string)$row['name'],
        'phone'      => (string)$row['phone'],
        'avatar_url' => $avatar,
        'role'       => (string)$row['role'], // admin | receiver | ...
    ],
]);
