<?php
// iforenta_api/get_user.php
declare(strict_types=1);

// رؤوس + منع أي إخراج غير JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
ini_set('display_errors', '0');
error_reporting(E_ALL);

// لو صار Fatal بنهاية التنفيذ، أخرجه كـ JSON بدل صفحة فاضية
register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        if (function_exists('ob_get_length') && ob_get_length()) { @ob_clean(); }
        echo json_encode([
            'status'  => 'error',
            'message' => 'Fatal: '.$e['message'].' at '.$e['file'].':'.$e['line']
        ], JSON_UNESCAPED_UNICODE);
    }
});

require_once __DIR__ . '/config.php';

if (!isset($conn) || !$conn) {
    http_response_code(500);
    if (function_exists('ob_get_length') && ob_get_length()) { @ob_clean(); }
    echo json_encode([
        'status'  => 'error',
        'message' => $GLOBALS['DB_CONNECT_ERROR'] ?? 'DB not initialized'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/*
  جدولك:
  id, username, phone, password, created_at

  نُرجِع:
  id, name (من username), phone, role (ثابت user), avatar_url (فارغ), created_at (اختياري).
*/
$sql = "SELECT 
          id,
          COALESCE(username,'')  AS username,
          COALESCE(phone,'')     AS phone,
          COALESCE(created_at,'') AS created_at
        FROM app_users
        ORDER BY id DESC";

$res = $conn->query($sql);

$data = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $data[] = [
            'id'         => (int)$row['id'],
            'name'       => $row['username'],   // ← mapping من username
            'phone'      => $row['phone'],
            'role'       => 'user',             // قيمة افتراضية للتطبيق
            'avatar_url' => '',                 // ما فيش عمود صورة في الجدول
            'created_at' => $row['created_at'], // مفيد لو حاب تستخدمه
        ];
    }
}

// تنظيف أي مخرجات غير مقصودة ثم طباعة JSON فقط
if (function_exists('ob_get_length') && ob_get_length()) { @ob_clean(); }
echo json_encode(['status' => 'success', 'data' => $data], JSON_UNESCAPED_UNICODE);
exit;
