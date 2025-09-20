<?php
// iforenta_api/add_item.php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
ini_set('display_errors','0'); error_reporting(E_ALL);

// نظف أي مخرجات عالطريق
if (function_exists('ob_get_length') && ob_get_length()) { @ob_clean(); }

// التقط الـFatal كـ JSON
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

// استقبل JSON أو Form
$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);
$src = is_array($payload) ? $payload : $_POST;

$name        = trim($src['name']        ?? '');
$description = trim($src['description'] ?? '');
$price       = trim($src['price']       ?? '');
$discount    = trim($src['discount']    ?? '');
$category_id = intval($src['category_id'] ?? 0);
// يقبل image_url أو image
$image_url   = trim($src['image_url'] ?? ($src['image'] ?? ''));

// تحقق الحقول المطلوبة
$missing = [];
if ($name === '')         $missing[] = 'name';
if ($price === '')        $missing[] = 'price';
if ($category_id <= 0)    $missing[] = 'category_id';
if (!empty($missing)) {
  http_response_code(400);
  if (function_exists('ob_get_length') && ob_get_length()) { @ob_clean(); }
  echo json_encode(['status'=>'error','message'=> implode(',', $missing).' required'], JSON_UNESCAPED_UNICODE);
  exit;
}

$priceF    = floatval($price);
$discountF = ($discount === '') ? null : floatval($discount);

// عمود الصورة لديك قد يكون image أو image_url – عدّل حسب جدولك
// سنفترض اسمه image_url
if ($discountF === null) {
  $sql = "INSERT INTO items (name, description, price, image_url, category_id)
          VALUES (?, ?, ?, ?, ?)";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ssdsi", $name, $description, $priceF, $image_url, $category_id);
} else {
  $sql = "INSERT INTO items (name, description, price, image_url, category_id, discount)
          VALUES (?, ?, ?, ?, ?, ?)";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ssdsid", $name, $description, $priceF, $image_url, $category_id, $discountF);
}

if (!$stmt) {
  http_response_code(500);
  if (function_exists('ob_get_length') && ob_get_length()) { @ob_clean(); }
  echo json_encode(['status'=>'error','message'=>$conn->error], JSON_UNESCAPED_UNICODE);
  exit;
}

if ($stmt->execute()) {
  $newId = $stmt->insert_id;
  if (function_exists('ob_get_length') && ob_get_length()) { @ob_clean(); }
  echo json_encode(['status'=>'success','id'=>$newId], JSON_UNESCAPED_UNICODE);
} else {
  http_response_code(500);
  if (function_exists('ob_get_length') && ob_get_length()) { @ob_clean(); }
  echo json_encode(['status'=>'error','message'=>$stmt->error], JSON_UNESCAPED_UNICODE);
}
$stmt->close();

// لا تضع علامة إغلاق PHP في آخر الملف
