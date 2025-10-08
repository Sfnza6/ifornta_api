<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config.php';

/* ---------- Helpers: قراءة من POST أو JSON ---------- */
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
  if ($id <= 0) {
    echo json_encode(['ok'=>false, 'message'=>'id مفقود أو غير صالح'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  // خريطة الحقول المسموح تحديثها
  $map = [
    'name'        => 'name',
    'description' => 'description',
    'price'       => 'price',
    'discount'    => 'discount',
    'image_url'   => 'image_url',
    'category_id' => 'category_id',
  ];

  /* =====================================================
     فرع PDO
  ===================================================== */
  if (isset($pdo) && $pdo instanceof PDO) {
    $fields = [];
    $bind   = [':id' => $id];

    foreach ($map as $in => $col) {
      $has = isset($_POST[$in]) || array_key_exists($in, _in_json());
      if (!$has) continue;

      $val = p($in);
      if ($in === 'price')       $val = ($val === '' || $val === null) ? null : (float)$val;
      if ($in === 'category_id') $val = ($val === '' || $val === null) ? null : (int)$val;
      if ($in === 'discount'   && ($val === '' || $val === null)) $val = null;

      $fields[]        = "$col = :$in";
      $bind[":$in"]    = $val;
    }

    if (!$fields) {
      echo json_encode(['ok'=>false,'message'=>'لا توجد حقول للتعديل'], JSON_UNESCAPED_UNICODE);
      exit;
    }

    $sql = "UPDATE items SET ".implode(', ', $fields)." WHERE id = :id";
    $st  = $pdo->prepare($sql);
    $st->execute($bind);

    $st = $pdo->prepare("SELECT id, name, description, price, discount, image_url, category_id, created_at, updated_at FROM items WHERE id=:id");
    $st->execute([':id'=>$id]);
    $item = $st->fetch(PDO::FETCH_ASSOC);

    echo json_encode(['ok'=>true, 'item'=>$item], JSON_UNESCAPED_UNICODE);
    exit;
  }

  /* =====================================================
     فرع mysqli
  ===================================================== */
  if (isset($conn) && $conn instanceof mysqli) {
    mysqli_set_charset($conn, 'utf8mb4');

    $sets = [];
    $types = '';
    $vals  = [];

    foreach ($map as $in => $col) {
      $has = isset($_POST[$in]) || array_key_exists($in, _in_json());
      if (!$has) continue;

      $val = p($in);

      if ($in === 'price') {
        if ($val === '' || $val === null) { $val = null; $types.='d'; }
        else { $val = (float)$val; $types.='d'; }
      } elseif ($in === 'category_id') {
        if ($val === '' || $val === null) { $val = null; $types.='i'; }
        else { $val = (int)$val; $types.='i'; }
      } elseif ($in === 'discount') {
        // يمكن تكون null
        if ($val === '' || $val === null) { $val = null; $types .= 's'; }
        else { $val = (string)$val; $types .= 's'; }
      } else {
        $val = ($val === null) ? null : (string)$val;
        $types .= 's';
      }

      $sets[] = "$col = ?";
      $vals[] = $val;
    }

    if (!$sets) {
      echo json_encode(['ok'=>false,'message'=>'لا توجد حقول للتعديل'], JSON_UNESCAPED_UNICODE);
      exit;
    }

    // WHERE id = ?
    $sql = "UPDATE items SET ".implode(', ', $sets)." WHERE id = ?";
    $st  = $conn->prepare($sql);
    if (!$st) {
      throw new Exception('تحضير الاستعلام فشل: '.$conn->error);
    }
    $types .= 'i';
    $vals[] = $id;

    // bind_param يحتاج مراجع
    $bindParams = [];
    $bindParams[] = &$types;
    foreach ($vals as $k => $v) {
      $bindParams[] = &$vals[$k];
    }
    call_user_func_array([$st, 'bind_param'], $bindParams);

    if (!$st->execute()) {
      throw new Exception('تنفيذ التحديث فشل: '.$st->error);
    }
    $st->close();

    // إعادة العنصر بعد التحديث
    $st2 = $conn->prepare("SELECT id, name, description, price, discount, image_url, category_id, created_at, updated_at FROM items WHERE id=?");
    $st2->bind_param('i', $id);
    $st2->execute();
    $res = $st2->get_result();
    $item = $res ? $res->fetch_assoc() : null;
    $st2->close();

    echo json_encode(['ok'=>true, 'item'=>$item], JSON_UNESCAPED_UNICODE);
    exit;
  }

  /* =====================================================
     لا يوجد اتصال قاعدة بيانات
  ===================================================== */
  echo json_encode([
    'ok'=>false,
    'message'=>'DB connection not available. تأكد أن config.php يوفّر $pdo (PDO) أو $conn (mysqli).'
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false, 'message'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
