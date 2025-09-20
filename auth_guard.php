<?php
// include بعد config.php
function require_token_and_role(mysqli $conn, array $rolesAllowed) {
  $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
  if (!preg_match('~Bearer\s+([a-f0-9]{64})~i', $auth, $m)) {
    http_response_code(401);
    echo json_encode(['status'=>'error','message'=>'Unauthorized']); exit;
  }
  $token = $m[1];

  $stmt = $conn->prepare("SELECT id, role FROM admins WHERE api_token=? AND is_active=1 LIMIT 1");
  $stmt->bind_param('s', $token);
  $stmt->execute();
  $r = $stmt->get_result();
  if ($r->num_rows === 0) {
    http_response_code(401);
    echo json_encode(['status'=>'error','message'=>'Unauthorized']); exit;
  }
  $row = $r->fetch_assoc();
  if (!in_array($row['role'], $rolesAllowed, true)) {
    http_response_code(403);
    echo json_encode(['status'=>'error','message'=>'Forbidden']); exit;
  }
  return (int)$row['id']; // ممكن تحتاجه
}
