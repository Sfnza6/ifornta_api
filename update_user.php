<?php
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode(['ok' => false, 'message' => 'مسموح POST فقط'], JSON_UNESCAPED_UNICODE);
  exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
  echo json_encode(['ok' => false, 'message' => 'id مطلوب'], JSON_UNESCAPED_UNICODE);
  exit;
}

$username = isset($_POST['username']) ? trim($_POST['username']) : null;
$phone    = isset($_POST['phone']) ? trim($_POST['phone']) : null;

$fields = [];
$params = [];
$types  = '';

if ($username !== null && $username !== '') {
  $fields[] = 'username = ?';
  $params[] = $username;
  $types   .= 's';
}

if ($phone !== null && $phone !== '') {
  $fields[] = 'phone = ?';
  $params[] = $phone;
  $types   .= 's';
}

$avatarUrl = null;
if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
  $uploadDir = __DIR__ . '/uploads';
  if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0777, true);
  }
  $tmp  = $_FILES['avatar']['tmp_name'];
  $ext  = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
  $name = uniqid() . ($ext ? ('.' . $ext) : '');
  $dest = $uploadDir . '/' . $name;

  if (!move_uploaded_file($tmp, $dest)) {
    echo json_encode(['ok' => false, 'message' => 'فشل رفع الصورة'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $avatarUrl = BASE_URL . '/uploads/' . $name;
  $fields[]  = 'avatar_url = ?';
  $params[]  = $avatarUrl;
  $types    .= 's';
}

if (empty($fields)) {
  echo json_encode(['ok' => false, 'message' => 'لا توجد حقول للتحديث'], JSON_UNESCAPED_UNICODE);
  exit;
}

$sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?';
$params[] = $id;
$types   .= 'i';

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);

if (!$stmt->execute()) {
  echo json_encode(['ok' => false, 'message' => 'فشل التحديث'], JSON_UNESCAPED_UNICODE);
  exit;
}

echo json_encode(['ok' => true, 'avatar_url' => $avatarUrl], JSON_UNESCAPED_UNICODE);
