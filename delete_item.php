<?php
// delete_item.php (mysqli)
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config.php';

// 1) id
$id = isset($_POST['id']) ? (int)$_POST['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
if ($id <= 0) {
  echo json_encode(['ok'=>false, 'message'=>'id غير صالح'], JSON_UNESCAPED_UNICODE);
  exit;
}

// 2) اقرأ السجل أولاً (للحصول على رابط الصورة إن وجد)
$img = '';
$stmt = $conn->prepare("SELECT image_url FROM items WHERE id = ? LIMIT 1");
if (!$stmt) {
  echo json_encode(['ok'=>false, 'message'=>'DB prepare: '.$conn->error], JSON_UNESCAPED_UNICODE);
  exit;
}
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$row) {
  // لا نعيد 404 حتى لا يظهر Network Error في التطبيق
  echo json_encode(['ok'=>false, 'message'=>'العنصر غير موجود'], JSON_UNESCAPED_UNICODE);
  exit;
}
$img = (string)($row['image_url'] ?? '');

// 3) احذف السجل
$stmt = $conn->prepare("DELETE FROM items WHERE id = ? LIMIT 1");
if (!$stmt) {
  echo json_encode(['ok'=>false, 'message'=>'DB prepare: '.$conn->error], JSON_UNESCAPED_UNICODE);
  exit;
}
$stmt->bind_param('i', $id);
$ok = $stmt->execute();
$err = $stmt->error;
$aff = $stmt->affected_rows;
$stmt->close();

if (!$ok || $aff < 1) {
  echo json_encode(['ok'=>false, 'message'=>'DB execute: '.$err], JSON_UNESCAPED_UNICODE);
  exit;
}

// 4) امسح ملف الصورة فقط إن كان موجودًا
if ($img !== '' && strpos($img, BASE_URL.'/uploads/') === 0) {
  $rel  = substr($img, strlen(BASE_URL)); // "/uploads/xxx.jpg"
  $path = __DIR__ . $rel;                  // "<dir>/uploads/xxx.jpg"
  if (is_file($path)) { @unlink($path); }
}

echo json_encode(['ok'=>true, 'id'=>$id], JSON_UNESCAPED_UNICODE);
