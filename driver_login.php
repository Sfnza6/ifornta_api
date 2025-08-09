<?php
// iforenta_api/driver_login.php
error_reporting(0);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

include 'config.php';

$input = json_decode(file_get_contents('php://input'), true);
$phone = trim($input['phone'] ?? '');
$pass  = trim($input['password'] ?? '');

if ($phone === '' || $pass === '') {
  http_response_code(400);
  echo json_encode(['status'=>'error','message'=>'phone and password required']);
  exit;
}

$stmt = $conn->prepare("SELECT id,name,password FROM drivers WHERE phone=?");
$stmt->bind_param("s", $phone);
$stmt->execute();
$res = $stmt->get_result();

if ($row = $res->fetch_assoc()) {
  // تحقق من كلمة المرور المخزنة (bcrypt)
  if (password_verify($pass, $row['password'])) {
    // أنشئ توكن بسيط (يمكن استبداله JWT أو غيره)
    $token = bin2hex(random_bytes(16));
    // (اختياري) خزّن $token في جدول sessions إن أردت تدقيق لاحقاً
    echo json_encode([
      'id'    => (int)$row['id'],
      'name'  => $row['name'],
      'token' => $token,
    ]);
    exit;
  }
}

http_response_code(401);
echo json_encode(['status'=>'error','message'=>'invalid credentials']);
