<?php
// seed_receiver.php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/config.php';

function out(array $arr, int $code = 200): void {
  http_response_code($code);
  echo json_encode($arr, JSON_UNESCAPED_UNICODE);
  exit;
}

// القيم المطلوبة
$name   = 'مستقبل طلبات';
$phone  = '0913208080';
$pass   = '12345678920';
$role   = 'receiver';
$active = 1;
$avatar = ''; // اتركه فارغًا أو ضع مسارًا إذا أردت

// تحقّق من الاتصال
if (!isset($conn) || !$conn) {
  out(['status'=>'error','message'=>'DB not initialized'], 500);
}

// تحقّق إن كان الهاتف موجودًا مسبقًا
$chk = $conn->prepare('SELECT id FROM admins WHERE phone = ? LIMIT 1');
$chk->bind_param('s', $phone);
$chk->execute();
$exists = $chk->get_result()->fetch_assoc();
$chk->close();

if ($exists) {
  out([
    'status'  => 'exists',
    'message' => 'Phone already exists in admins table',
    'admin'   => ['id' => (int)$exists['id'], 'phone' => $phone]
  ]);
}

// هشّ كلمة السر ببروتوكول آمن
$hash = password_hash($pass, PASSWORD_BCRYPT);

// أضف السجل
$sql = 'INSERT INTO admins (name, phone, password_hash, avatar_url, role, api_token, is_active, created_at)
        VALUES (?, ?, ?, ?, ?, NULL, ?, NOW())';
$stmt = $conn->prepare($sql);
if (!$stmt) {
  out(['status'=>'error','message'=>$conn->error], 500);
}
$stmt->bind_param('sssssi', $name, $phone, $hash, $avatar, $role, $active);

if ($stmt->execute()) {
  $id = $stmt->insert_id;
  out([
    'status' => 'success',
    'admin'  => [
      'id'         => (int)$id,
      'name'       => $name,
      'phone'      => $phone,
      'role'       => $role,
      'avatar_url' => $avatar,
      'is_active'  => $active
    ]
  ]);
} else {
  out(['status'=>'error','message'=>$stmt->error], 500);
}
