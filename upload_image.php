<?php
// upload_image.php — robust & clean JSON

declare(strict_types=1);

// ====== CORS & headers ======
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

ini_set('display_errors', '0');
error_reporting(E_ALL);

// ردّ على preflight
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

// التقط الأخطاء القاتلة
register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        respond(500, ['status'=>'error','message'=>'Fatal: '.$e['message']]);
    }
});

// اسم الحقل المدعوم: image (ويدعم file احتياطاً)
$fieldName = isset($_FILES['image']) ? 'image' : (isset($_FILES['file']) ? 'file' : null);
if (!$fieldName) {
    respond(400, ['status'=>'error','message'=>'No image uploaded. Expected field "image".']);
}

$file = $_FILES[$fieldName];

// أخطاء الرفع
if ($file['error'] !== UPLOAD_ERR_OK) {
    $errMap = [
        UPLOAD_ERR_INI_SIZE   => 'File exceeds upload_max_filesize.',
        UPLOAD_ERR_FORM_SIZE  => 'File exceeds MAX_FILE_SIZE.',
        UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
        UPLOAD_ERR_NO_FILE    => 'No file uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the upload.',
    ];
    respond(400, [
        'status'=>'error',
        'message'=>$errMap[$file['error']] ?? ('Upload error code '.$file['error']),
    ]);
}

// تحقق الحجم (10MB حد افتراضي)
$maxBytes = 10 * 1024 * 1024;
if ((int)$file['size'] > $maxBytes) {
    respond(400, ['status'=>'error','message'=>'File too large (>10MB).']);
}

// تحقق الامتداد وMIME
$allowedExts  = ['jpg','jpeg','png','gif','webp'];
$allowedMimes = ['image/jpeg','image/png','image/gif','image/webp'];

$origName = basename($file['name']);
$ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExts, true)) {
    respond(400, ['status'=>'error','message'=>'Invalid file type (extension).']);
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->file($file['tmp_name']) ?: 'application/octet-stream';
if (!in_array($mime, $allowedMimes, true)) {
    respond(400, ['status'=>'error','message'=>'Invalid MIME type.']);
}

// مجلد الرفع
require_once __DIR__ . '/config.php'; // يمكن تعريف BASE_URL هنا اختيارياً
if (!defined('BASE_URL')) {
    define('BASE_URL', '');
}

$baseUploadDir = rtrim(__DIR__, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'uploads';

// مجلد فرعي اختياري من POST[folder] (مثلاً items)
$sub = '';
if (!empty($_POST['folder'])) {
    $sub = preg_replace('~[^a-zA-Z0-9_-]~', '', (string)$_POST['folder']);
}
$uploadDir = $baseUploadDir . ($sub ? DIRECTORY_SEPARATOR . $sub : '');

if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
    respond(500, ['status'=>'error','message'=>'Failed to create upload directory.']);
}
if (!is_writable($uploadDir)) {
    respond(500, ['status'=>'error','message'=>'Upload directory is not writable.']);
}

// اسم آمن للملف ونقل
$safeName = bin2hex(random_bytes(8)) . '.' . $ext;
$destPath = $uploadDir . DIRECTORY_SEPARATOR . $safeName;

if (!is_uploaded_file($file['tmp_name'])) {
    respond(400, ['status'=>'error','message'=>'Temp file is not a valid uploaded file.']);
}
if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    respond(500, ['status'=>'error','message'=>'Failed to move uploaded file.']);
}

// بناء URL عام صالح للوصول من الهاتف
$relative = '/uploads' . ($sub ? '/'.$sub : '') . '/' . $safeName;

// لو عرّفت BASE_URL في config.php — يُفضَّل ذلك لتثبيت IP/دومين
if (defined('BASE_URL') && BASE_URL) {
    $url = rtrim((string)BASE_URL, '/') . $relative;
} else {
    // fallback: استخرج من الطلب (قد ينتج localhost لو استدعيت من الجهاز نفسه)
    $protoHeader = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? null;
    $scheme = $protoHeader ? explode(',', $protoHeader)[0] :
              ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http');
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    $base = ($base === '.' || $base === '/') ? '' : $base;
    $url  = $scheme . '://' . $host . $base . $relative;
}

// استجابة النجاح
respond(200, [
    'status'   => 'success',
    'url'      => $url,       // رابط مطلق للصورة
    'path'     => $relative,  // مسار نسبي (مفيد للتخزين)
    'filename' => $safeName,
    'mime'     => $mime,
    'size'     => (int)$file['size'],
]);

// لا تُغلق الوسم بـ ?> لتجنّب المسافات الزائدة
