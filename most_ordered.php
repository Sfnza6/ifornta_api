<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config.php';


// إدخال آمن وبسيط
$limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 20;
$catId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

$rows = [];

// المحاولة 1: الاعتماد على عمود items.order_count (أنسب وأسرع)
$where = $catId > 0 ? " WHERE category_id = {$catId}" : "";
$sql1 = "SELECT id, name, description, price, discount, image_url, category_id, created_at, updated_at
         FROM items
         {$where}
         ORDER BY (order_count + 0) DESC, id DESC
         LIMIT {$limit}";
$res = @$conn->query($sql1);
if ($res) {
  while ($r = $res->fetch_assoc()) $rows[] = $r;
  $res->free();
}

// المحاولة 2 (Fallback): لو مافيش order_count، نجرب تجميع من جدول order_items
if (!$rows) {
  $whereJoin = $catId > 0 ? " WHERE i.category_id = {$catId}" : "";
  $sql2 = "SELECT i.id, i.name, i.description, i.price, i.discount, i.image_url,
                  i.category_id, i.created_at, i.updated_at,
                  COUNT(oi.id) AS cnt
           FROM items i
           LEFT JOIN order_items oi ON oi.item_id = i.id
           {$whereJoin}
           GROUP BY i.id
           ORDER BY cnt DESC, i.id DESC
           LIMIT {$limit}";
  $res2 = @$conn->query($sql2);
  if ($res2) {
    while ($r = $res2->fetch_assoc()) $rows[] = $r;
    $res2->free();
  }
}

// المحاولة 3: آخر حل – رجّع الأحدث (حتى ما يكون السكشن فاضي)
if (!$rows) {
  $sql3 = "SELECT id, name, description, price, discount, image_url, category_id, created_at, updated_at
           FROM items
           {$where}
           ORDER BY created_at DESC, id DESC
           LIMIT {$limit}";
  $res3 = @$conn->query($sql3);
  if ($res3) {
    while ($r = $res3->fetch_assoc()) $rows[] = $r;
    $res3->free();
  }
}

echo json_encode($rows, JSON_UNESCAPED_UNICODE);
