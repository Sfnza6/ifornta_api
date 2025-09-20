<?php
// seed_admin.php — شغّله مرة وحدة لإضافة الأدمن
header('Content-Type: text/plain; charset=utf-8');
require_once __DIR__ . '/config.php';

$name   = 'معتز السايح';
$phone  = '0913208060';
$pass   = '123456789';
$role   = 'admin';
$avatar = ''; // تقدر تحط رابط صورة لاحقًا

$hash = password_hash($pass, PASSWORD_BCRYPT);

$sql = "INSERT INTO admins (name, phone, password_hash, avatar_url, role)
        VALUES (?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
if (!$stmt) {
  die("Prepare failed: " . $conn->error);
}
$stmt->bind_param('sssss', $name, $phone, $hash, $avatar, $role);

if ($stmt->execute()) {
  echo "✔ تم إدخال الأدمن بنجاح.\n";
  echo "ID: " . $stmt->insert_id . "\n";
} else {
  echo "✖ فشل الإدخال: " . $stmt->error . "\n";
}

$stmt->close();
$conn->close();
