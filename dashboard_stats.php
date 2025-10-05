<?php
// iforenta_api/dashboard_stats.php
declare(strict_types=1);

// رؤوس ثابتة + منع طباعة أخطاء HTML
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
ini_set('display_errors', '0');
error_reporting(E_ALL);

// التقط أي Fatal في نهاية التنفيذ وارجعه كـ JSON
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

// لو الاتصال فشل، ارجع السبب بوضوح
if (!isset($conn) || !$conn) {
    http_response_code(500);
    if (function_exists('ob_get_length') && ob_get_length()) { @ob_clean(); }
    echo json_encode([
        'status'  => 'error',
        'message' => $GLOBALS['DB_CONNECT_ERROR'] ?? 'DB not initialized'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// counters
$items     = 0;
$orders    = 0;
$customers = 0;
$drivers   = 0; // ← بدلنا الإيرادات بعدد السائقين

// عدّادات بسيطة من الجداول
if ($q = $conn->query("SELECT COUNT(*) AS c FROM items")) {
    $items = (int)($q->fetch_assoc()['c'] ?? 0);
}
if ($q = $conn->query("SELECT COUNT(*) AS c FROM app_orders")) {
    $orders = (int)($q->fetch_assoc()['c'] ?? 0);
}
if ($q = $conn->query("SELECT COUNT(*) AS c FROM app_users")) {
    $customers = (int)($q->fetch_assoc()['c'] ?? 0);
}
if ($q = $conn->query("SELECT COUNT(*) AS c FROM app_drivers")) {
    $drivers = (int)($q->fetch_assoc()['c'] ?? 0);
}

// إخراج JSON مضمون
if (function_exists('ob_get_length') && ob_get_length()) { @ob_clean(); }
echo json_encode([
    'status' => 'success',
    'data'   => [
        'items'     => $items,
        'drivers'   => $drivers,   // ← الحقل الجديد
        'orders'    => $orders,
        'customers' => $customers,
    ]
], JSON_UNESCAPED_UNICODE);
