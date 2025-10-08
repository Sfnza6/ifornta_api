<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/config.php';

if (!isset($conn) || !$conn) {
  http_response_code(500);
  echo json_encode(['status'=>'error','message'=>'DB not initialized'], JSON_UNESCAPED_UNICODE);
  exit;
}

$today = date('Y-m-d');
$out = [];

$sql = "SELECT id,name,description,price,is_active,daily_quota,quota_date,quota_used,discount,image_url,category_id
        FROM items ORDER BY id DESC";
$q = $conn->query($sql);

while ($r = $q->fetch_assoc()) {
  // اعادة ضبط عدّاد اليوم لو تغيّر التاريخ
  if (!is_null($r['daily_quota'])) {
    if ($r['quota_date'] !== $today) {
      $r['quota_date'] = $today;
      $r['quota_used'] = 0;
      $st = $conn->prepare("UPDATE items SET quota_date=?, quota_used=0 WHERE id=?");
      $st->bind_param('si', $today, $r['id']);
      $st->execute(); $st->close();
    }
  }

  $remaining = null;
  if (!is_null($r['daily_quota'])) {
    $remaining = max(0, (int)$r['daily_quota'] - (int)$r['quota_used']);
    if ($remaining <= 0 && (int)$r['is_active'] === 1) {
      $conn->query("UPDATE items SET is_active=0 WHERE id=".(int)$r['id']);
      $r['is_active'] = 0;
    }
  }

  $out[] = [
    'id'           => (int)$r['id'],
    'name'         => $r['name'],
    'description'  => $r['description'],
    'price'        => (float)$r['price'],
    'image_url'    => $r['image_url'],
    'category_id'  => (int)$r['category_id'],
    'is_active'    => (int)$r['is_active'],
    'daily_quota'  => is_null($r['daily_quota']) ? null : (int)$r['daily_quota'],
    'quota_used'   => (int)$r['quota_used'],
    'discount'     => $r['discount'],
    'remaining'    => $remaining,
  ];
}

echo json_encode(['status'=>'success','items'=>$out], JSON_UNESCAPED_UNICODE);
