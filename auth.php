<?php
// auth.php
// يحتاج config.php اللي فيه $conn (mysqli)
require_once __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');

/** اقرأ التوكن من Authorization: Bearer <token> أو من بارام token */
function get_request_token(): ?string {
  // 1) من الهيدر
  $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['Authorization'] ?? '';
  if (!$hdr && function_exists('apache_request_headers')) {
    $h = apache_request_headers();
    foreach ($h as $k => $v) {
      if (strtolower($k) === 'authorization') { $hdr = $v; break; }
    }
  }
  if ($hdr) {
    // صيَغ مدعومة: "Bearer xxx" أو "Token xxx"
    if (stripos($hdr, 'bearer ') === 0) return trim(substr($hdr, 7));
    if (stripos($hdr, 'token ')  === 0) return trim(substr($hdr, 6));
    // لو مرّرتها كقيمة مباشرة في الهيدر
    if (strlen($hdr) > 16 && strpos($hdr, ' ') === false) return trim($hdr);
  }
  // 2) من الاستعلام أو البوست (للاختبار)
  if (!empty($_GET['token']))  return trim((string)$_GET['token']);
  if (!empty($_POST['token'])) return trim((string)$_POST['token']);
  return null;
}

/** ارجع بيانات المستخدم أو اخرج 401 لو غير صالح */
function require_auth(mysqli $conn): array {
  $token = get_request_token();
  if (!$token) {
    http_response_code(401);
    echo json_encode(['ok'=>false,'message'=>'Missing token'], JSON_UNESCAPED_UNICODE);
    exit;
  }
  // NOTE: عدّل أسماء الجدول/الأعمدة حسب عندك
  // يفترض وجود users.api_token و users.is_active
  $stmt = $conn->prepare("SELECT id, name, phone FROM users WHERE api_token = ? AND (is_active=1 OR is_active IS NULL) LIMIT 1");
  $stmt->bind_param('s', $token);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res->fetch_assoc();
  $stmt->close();

  if (!$row) {
    http_response_code(401);
    echo json_encode(['ok'=>false,'message'=>'Unauthorized'], JSON_UNESCAPED_UNICODE);
    exit;
  }
  return $row; // ممكن تحتاجه لاحقاً
}
