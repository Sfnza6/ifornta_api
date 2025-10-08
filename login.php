<?php
// /iforenta_api/login.php
header('Content-Type: application/json; charset=utf-8');
ob_start();

require_once __DIR__ . '/config.php';

$phone    = isset($_POST['phone'])    ? trim($_POST['phone'])    : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';

if ($phone === '' || $password === '') {
    echo json_encode(['status' => 'error', 'message' => 'بيانات ناقصة'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // ملاحظة: لو عندك كلمة سر مشفّرة استخدم password_verify بعد الاستعلام بالهاتف فقط.
    $sql  = "SELECT id, username, phone, COALESCE(avatar_url,'') AS avatar_url, COALESCE(created_at,'') AS created_at
             FROM app_users
             WHERE phone = ? AND password = ?
             LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('DB prepare failed: ' . $conn->error);
    }
    $stmt->bind_param('ss', $phone, $password);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        // نرجّع user (ومعاه name = username للتوافق مع الموديل)
        $user = [
            'id'         => (int)$row['id'],
            'username'   => $row['username'],
            'name'       => $row['username'],
            'phone'      => $row['phone'],
            'avatar_url' => $row['avatar_url'],
            'created_at' => $row['created_at'],
            'role'       => 'user',
        ];
        // تنظيف أي نفايات قبل الإرسال
        if (function_exists('ob_get_length') && ob_get_length()) { @ob_clean(); }
        echo json_encode(['status' => 'success', 'user' => $user], JSON_UNESCAPED_UNICODE);
    } else {
        if (function_exists('ob_get_length') && ob_get_length()) { @ob_clean(); }
        echo json_encode(['status' => 'error', 'message' => 'رقم الهاتف أو كلمة المرور غير صحيحة'], JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $e) {
    if (function_exists('ob_get_length') && ob_get_length()) { @ob_clean(); }
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
