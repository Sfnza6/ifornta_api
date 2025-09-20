<?php
// iforenta_api/update_item.php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
ini_set('display_errors','0'); error_reporting(E_ALL);

// نظّف أي مخرجات قديمة
if (function_exists('ob_get_length') && ob_get_length()) { @ob_clean(); }

// التقط أي Fatal كـ JSON
register_shutdown_function(function () {
  $e = error_get_last();
  if ($e && in_array($e['type'], [E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR])) {
    if (function_exists('ob_get_length') && ob_get_length()) { @ob_clean(); }
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>'Fatal: '.$e['message']], JSON_UNESCAPED_UNICODE);
  }
});

require_once __DIR__.'/config.php';
if (!isset($conn) || !$conn) {
  http_response_code(500);
  if (function_exists('ob_get_length') && ob_get_length()) { @ob_clean(); }
  echo json_encode(['status'=>'error','message'=>$GLOBALS['DB_CONNECT_ERROR'] ?? 'DB not initialized'], JSON_UNESCAPED_UNICODE);
  exit;
}

// استقبل JSON أو form-data
$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);
$src = is_array($payload) ? $payload : $_POST;

/**
 * الحقول المدعومة:
 * id (required), name (required), price (required),
 * description (optional), discount (optional), category_id (optional),
 * image_url (optional)  -- لو فاضي، بنبقي على الصورة الحالية
 */
$id          = intval($src['id'] ?? 0);
$name        = trim($src['name'] ?? '');
$price       = trim($src['price'] ?? '');
$description = trim($src['description'] ?? '');
$discount    = trim($src['discount'] ?? '');
$category_id = isset($src['category_id']) && $src['category_id']!=='' ? intval($src['category_id']) : null;

// يقبل image_url أو image (توافقًا للخلف)
$image_url   = trim($src['image_url'] ?? ($src['image'] ?? ''));

if ($id <= 0 || $name === '' || $price === '') {
  http_response_code(400);
  if (function_exists('ob_get_length') && ob_get_length()) { @ob_clean(); }
  echo json_encode(['status'=>'error','message'=>'id,name,price required'], JSON_UNESCAPED_UNICODE);
  exit;
}

$priceF    = floatval($price);
$discountF = ($discount === '') ? null : floatval($discount);

/**
 * ملاحظة: عمود الصورة في الجدول اسمه image_url (كما اتفقنا)
 * لو image_url فاضي => لا نحدّث الصورة (نتركها كما هي)
 * لو موفّر => نحدّثها
 */
$setImage = ($image_url !== '');

// جهّز الاستعلام ديناميكيًا
$fields = "name=?, description=?, price=?";
$paramsTypes = "ssd";
$params = [$name, $description, $priceF];

if ($setImage) {
  $fields .= ", image_url=?";
  $paramsTypes .= "s";
  $params[] = $image_url;
}
if (!is_null($category_id)) {
  $fields .= ", category_id=?";
  $paramsTypes .= "i";
  $params[] = $category_id;
}
if (!is_null($discountF)) {
  $fields .= ", discount=?";
  $paramsTypes .= "d";
  $params[] = $discountF;
}
$fields .= ", updated_at=NOW()";

$sql = "UPDATE items SET $fields WHERE id=?";
$paramsTypes .= "i";
$params[] = $id;

$stmt = $conn->prepare($sql);
if (!$stmt) {
  http_response_code(500);
  if (function_exists('ob_get_length') && ob_get_length()) { @ob_clean(); }
  echo json_encode(['status'=>'error','message'=>$conn->error], JSON_UNESCAPED_UNICODE);
  exit;
}

// ربط باراميترات ديناميكية
$stmt->bind_param($paramsTypes, ...$params);

if ($stmt->execute()) {
  if (function_exists('ob_get_length') && ob_get_length()) { @ob_clean(); }
  echo json_encode(['status'=>'success'], JSON_UNESCAPED_UNICODE);
} else {
  http_response_code(500);
  if (function_exists('ob_get_length') && ob_get_length()) { @ob_clean(); }
  echo json_encode(['status'=>'error','message'=>$stmt->error], JSON_UNESCAPED_UNICODE);
}
$stmt->close();

// لا تضع علامة إغلاق PHP في آخر الملف
