<?php
// get_user_current_orders.php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/config.php';
mysqli_set_charset($conn, 'utf8mb4');

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
if ($user_id <= 0) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'message'=>'user_id مطلوب'], JSON_UNESCAPED_UNICODE);
  exit;
}

/**
 * الحالات الجارية:
 * - pending        : جاري الموافقة على الطلب
 * - processing     : جاري التجهيز
 * - assigned       : تم التكليف/جاري التوصيل
 * - on_the_way     : جاري التوصيل
 * - delivering     : جاري التوصيل
 * - handover       : جاري التسليم
 *
 * المستبعدة:
 * - delivered, cancelled, rejected
 */
$sql = "
SELECT id, user_id, address, payment_method, total, status, created_at, status_order,
       dest_lat, dest_lng, pickup_lat, pickup_lng
FROM app_orders
WHERE user_id = ?
  AND status IN ('pending','processing','assigned','on_the_way','delivering','handover')
ORDER BY created_at DESC, id DESC
";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$out = [];
while ($row = mysqli_fetch_assoc($res)) {
  // تنظيف/تحويلات بسيطة
  $out[] = [
    'id'             => (int)$row['id'],
    'address'        => (string)($row['address'] ?? ''),
    'payment_method' => (int)($row['payment_method'] ?? 0),
    'total'          => (float)($row['total'] ?? 0),
    'status'         => (string)$row['status'],
    'status_order'   => (string)($row['status_order'] ?? ''),
    'created_at'     => (string)($row['created_at'] ?? ''),
  ];
}

echo json_encode(['ok'=>true,'orders'=>$out], JSON_UNESCAPED_UNICODE);
