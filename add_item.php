<?php
// iforenta_api/add_item.php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

ini_set('display_errors', '0');
error_reporting(E_ALL);

// نظّف أي مخرجات سابقة
if (function_exists('ob_get_length') && ob_get_length()) { @ob_clean(); }

// تحويل أي Fatal إلى JSON
register_shutdown_function(function () {
  $e = error_get_last();
  if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
    if (function_exists('ob_get_length') && ob_get_length()) { @ob_clean(); }
    http_response_code(500);
    echo json_encode(['ok'=>false,'message'=>'Fatal: '.$e['message']], JSON_UNESCAPED_UNICODE);
  }
});

require_once __DIR__ . '/config.php';

// BASE_URL اختياري من config.php — إن لم يكن معرّفًا، نبنيه من الطلب
if (!defined('BASE_URL')) {
  $scheme = (isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http');
  $base   = $scheme.'://'.$_SERVER['HTTP_HOST'].rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
  define('BASE_URL', $base);
}

if (!isset($conn) || !$conn) {
  http_response_code(500);
  if (function_exists('ob_get_length') && ob_get_length()) { @ob_clean(); }
  echo json_encode(['ok'=>false,'message'=>$GLOBALS['DB_CONNECT_ERROR'] ?? 'DB not initialized'], JSON_UNESCAPED_UNICODE);
  exit;
}

$name        = trim($_POST['name']        ?? '');
$description = trim($_POST['description'] ?? '');
$price       = isset($_POST['price']) ? (float)$_POST['price'] : 0.0;
$discountStr = trim((string)($_POST['discount'] ?? ''));
$category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;

// دعم تمرير رابط صورة جاهز بدل الرفع
$image_url_input = trim((string)($_POST['image_url'] ?? ''));

if ($name === '' || $price <= 0 || $category_id <= 0) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'message'=>'الاسم/السعر/القسم مطلوب'], JSON_UNESCAPED_UNICODE);
  exit;
}

$discount = ($discountStr === '') ? null : (float)$discountStr;

/* 1) INSERT (بدون image_url كبداية) */
$sql = "INSERT INTO items
        (name, description, price, discount, category_id, rating, order_count, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, 0, 0, NOW(), NOW())";
$stmt = $conn->prepare($sql);
if (!$stmt) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'message'=>'DB prepare: '.$conn->error], JSON_UNESCAPED_UNICODE);
  exit;
}
$stmt->bind_param('ssdsi', $name, $description, $price, $discount, $category_id);
if (!$stmt->execute()) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'message'=>'DB execute: '.$stmt->error], JSON_UNESCAPED_UNICODE);
  $stmt->close(); exit;
}
$itemId = $stmt->insert_id;
$stmt->close();

$final_image_url = '';

/* 2) لو وصلنا image_url جاهز من العميل (بدون رفع) */
if ($image_url_input !== '') {
  // طبع الرابط لو كان نسبيًا
  $img = $image_url_input;
  if (stripos($img, 'http://') !== 0 && stripos($img, 'https://') !== 0) {
    $img = BASE_URL . (str_starts_with($img, '/') ? $img : '/'.$img);
  }

  $u = $conn->prepare("UPDATE items SET image_url = ?, updated_at = NOW() WHERE id = ? LIMIT 1");
  if ($u) {
    $u->bind_param('si', $img, $itemId);
    $u->execute();
    $u->close();
    $final_image_url = $img;
  }
}

/* 3) أو لو وصل ملف image بالـ multipart */
if (isset($_FILES['image']) && !empty($_FILES['image']['name']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
  $uploadDir = __DIR__ . '/uploads';
  if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0775, true); }
  if (!is_writable($uploadDir)) {
    echo json_encode(['ok'=>true,'id'=>$itemId,'image_url'=>$final_image_url,'warn'=>'مجلد uploads غير قابل للكتابة']); exit;
  }

  $mime = @mime_content_type($_FILES['image']['tmp_name']) ?: '';
  $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
  $ext = $allowed[$mime] ?? strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION) ?: 'jpg');

  $fname  = uniqid('img_', true) . '.' . $ext;
  $target = $uploadDir . '/' . $fname;

  if (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
    echo json_encode(['ok'=>true,'id'=>$itemId,'image_url'=>$final_image_url,'warn'=>'فشل رفع الصورة']); exit;
  }

  $imgUrl = BASE_URL . '/uploads/' . $fname;
  $u = $conn->prepare("UPDATE items SET image_url = ?, updated_at = NOW() WHERE id = ? LIMIT 1");
  if ($u) {
    $u->bind_param('si', $imgUrl, $itemId);
    $u->execute();
    $u->close();
  }
  $final_image_url = $imgUrl;
}

if (function_exists('ob_get_length') && ob_get_length()) { @ob_clean(); }
echo json_encode(['ok'=>true, 'id'=>$itemId, 'image_url'=>$final_image_url], JSON_UNESCAPED_UNICODE);
