<?php
// ====== Headers & Clean Output ======
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
// لا تطبع تحذيرات في الاستجابة حتى لا ينكسر JSON
error_reporting(0);
ini_set('display_errors', 0);
if (function_exists('ob_get_length') && ob_get_length()) { ob_clean(); }

require_once __DIR__ . '/config.php';
// ====== Read Input (POST or JSON) ======
$in = $_POST;
if (!$in) {
  $raw = file_get_contents('php://input');
  $j   = json_decode($raw, true);
  if (is_array($j)) $in = $j;
}

$phone    = isset($in['phone'])    ? trim($in['phone'])    : '';
$password = isset($in['password']) ? trim($in['password']) : '';

if ($phone === '' || $password === '') {
  echo json_encode(['ok'=>false,'msg'=>'رقم الهاتف أو كلمة السر فارغة'], JSON_UNESCAPED_UNICODE);
  exit;
}

// ====== Normalize phone ======
$digits = preg_replace('/\D+/', '', $phone);       // keep digits only
if (strpos($digits, '218') === 0) {                // +218xxxx -> 0xxxx
  $digits = '0'.substr($digits, 3);
}
$with0 = $digits;
$no0   = ltrim($digits, '0');

try {
  // ====== Find driver ======
  $st = $pdo->prepare("
    SELECT id, name, phone, password AS pw, created_at
    FROM app_drivers
    WHERE REPLACE(phone,' ','') IN (?, ?)
    LIMIT 1
  ");
  $st->execute([$with0, $no0]);
  $drv = $st->fetch(PDO::FETCH_ASSOC);

  if (!$drv) {
    echo json_encode(['ok'=>false,'msg'=>'السائق غير موجود'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  // نصّية حسب المطلوب (بدون hash)
  if ($drv['pw'] !== $password) {
    echo json_encode(['ok'=>false,'msg'=>'كلمة المرور غير صحيحة'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $driver_id = (int)$drv['id'];

  // ====== Stats helpers ======
  $scalar = function($q,$p=[]) use($pdo){
    $s=$pdo->prepare($q);
    $s->execute($p);
    return $s->fetchColumn();
  };

  // ====== Counters ======
  $delivered = (int)  $scalar("SELECT COUNT(*) FROM app_orders WHERE driver_id=? AND TRIM(LOWER(status))='delivered'", [$driver_id]);
  $pending   = (int)  $scalar("SELECT COUNT(*) FROM app_orders WHERE driver_id=? AND TRIM(LOWER(status))='pending'",   [$driver_id]);
  $rejected  = (int)  $scalar("SELECT COUNT(*) FROM app_orders WHERE driver_id=? AND TRIM(LOWER(status))='rejected'",  [$driver_id]);

  // ====== Amounts ======
  $earnings       = (float)$scalar("SELECT COALESCE(SUM(total),0) FROM app_orders WHERE driver_id=? AND TRIM(LOWER(status))='delivered'", [$driver_id]);
  $pending_amount = (float)$scalar("SELECT COALESCE(SUM(total),0) FROM app_orders WHERE driver_id=? AND TRIM(LOWER(status))='pending'",   [$driver_id]);

  // ====== Response ======
  echo json_encode([
    'ok'     => true,
    'msg'    => 'تم تسجيل الدخول',
    'driver' => [
      'id'         => $driver_id,
      'name'       => $drv['name'],
      'phone'      => $drv['phone'],
      'created_at' => $drv['created_at'],
    ],
    'stats'  => [
      // عدّادات
      'delivered'        => $delivered,
      'pending'          => $pending,
      'rejected'         => $rejected,
      'delivered_count'  => $delivered,
      'pending_count'    => $pending,
      'rejected_count'   => $rejected,
      // مبالغ (مفاتيح متعددة لتوافق الواجهة)
      'earnings'         => $earnings,        // الربح
      'profit'           => $earnings,        // اسم بديل
      'pending_amount'   => $pending_amount,  // المديونية/المستحقات
      'dues'             => $pending_amount,  // اسم بديل
      'debt'             => $pending_amount,  // اسم بديل
    ],
  ], JSON_UNESCAPED_UNICODE);
  exit;

} catch (Throwable $e) {
  // لا تُظهر تفاصيل الخطأ للعميل حتى لا ينكسر JSON
  http_response_code(500);
  echo json_encode(['ok'=>false,'msg'=>'خطأ في الخادم'], JSON_UNESCAPED_UNICODE);
  exit;
}