<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

function _in_json(): array {
  static $j = null;
  if ($j !== null) return $j;
  $raw = file_get_contents('php://input');
  $j = $raw ? json_decode($raw, true) : [];
  return is_array($j) ? $j : [];
}
function p(string $k, $d=null) {
  $j = _in_json();
  return $_POST[$k] ?? $j[$k] ?? $d;
}

try {
  $id = (int)p('id', 0);
  if ($id <= 0) { echo json_encode(['ok'=>false,'message'=>'id مفقود'], JSON_UNESCAPED_UNICODE); exit; }

  $fields = [];
  $bind   = [':id'=>$id];

  $map = [
    'name'        => 'name',
    'description' => 'description',
    'price'       => 'price',
    'discount'    => 'discount',
    'image_url'   => 'image_url',
    'category_id' => 'category_id',
  ];

  foreach ($map as $in => $col) {
    $has = isset($_POST[$in]) || array_key_exists($in, _in_json());
    if ($has) {
      $val = p($in);
      if ($in === 'price')       $val = (float)$val;
      if ($in === 'category_id') $val = (int)$val;
      if ($in === 'discount' && ($val==='' || $val===null)) $val = null;
      $fields[] = "$col = :$in";
      $bind[":$in"] = $val;
    }
  }

  if (!$fields) { echo json_encode(['ok'=>false,'message'=>'لا توجد حقول للتعديل'], JSON_UNESCAPED_UNICODE); exit; }

  $sql = "UPDATE items SET ".implode(', ', $fields)." WHERE id = :id";
  $stmt = $pdo->prepare($sql);
  $stmt->execute($bind);

  $stmt = $pdo->prepare("SELECT id, name, description, price, discount, image_url, category_id, created_at, updated_at FROM items WHERE id=:id");
  $stmt->execute([':id'=>$id]);
  $item = $stmt->fetch();

  echo json_encode(['ok'=>true,'item'=>$item], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false, 'message'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
