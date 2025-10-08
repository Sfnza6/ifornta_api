<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/config.php';

/**
 * باراميترات اختيارية:
 * - limit:   عدد النتائج (1..200)    (افتراضي 50)
 * - offset:  إزاحة للصفحـة            (افتراضي 0)
 * - category_id: فلترة بالتصنيف
 * - q:       نص بحث في الاسم/الوصف
 * - sort:    newest | price_asc | price_desc | popular | rating  (افتراضي newest)
 */
$limit  = isset($_GET['limit'])  ? max(1, min(200, (int)$_GET['limit'])) : 50;
$offset = isset($_GET['offset']) ? max(0, (int)$_GET['offset'])          : 0;
$catId  = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$q      = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$sort   = isset($_GET['sort']) ? strtolower((string)$_GET['sort']) : 'newest';

$allowedSort = [
  'newest'     => 'i.id DESC',
  'price_asc'  => 'i.price ASC',
  'price_desc' => 'i.price DESC',
  'popular'    => 'i.order_count DESC, i.id DESC',
  'rating'     => 'i.rating DESC, i.id DESC',
];
$orderBy = $allowedSort[$sort] ?? $allowedSort['newest'];

try {
  $rows = [];

  // ============== باستخدام PDO (إن كان $pdo معرفًا) ==============
  if (isset($pdo) && $pdo instanceof PDO) {
    $where = [];
    $params = [];

    if ($catId > 0) {
      $where[] = 'i.category_id = :cat';
      $params[':cat'] = $catId;
    }
    if ($q !== '') {
      // بحث بسيط في الاسم والوصف
      $where[] = '(i.name LIKE :q OR i.description LIKE :q)';
      $params[':q'] = "%{$q}%";
    }

    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    $sql = "
      SELECT
        i.id,
        i.name,
        i.description,
        i.price,
        i.discount,
        i.image_url,
        i.category_id,
        i.rating,
        i.order_count,
        i.created_at,
        i.updated_at
      FROM items i
      {$whereSql}
      ORDER BY {$orderBy}
      LIMIT :limit OFFSET :offset
    ";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
      $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // ============== أو باستخدام mysqli (إن كان $conn معرفًا) ==============
  } elseif (isset($conn) && $conn instanceof mysqli) {
    mysqli_set_charset($conn, 'utf8mb4');

    $where = [];
    if ($catId > 0) {
      $where[] = 'i.category_id = ' . (int)$catId;
    }
    if ($q !== '') {
      $qEsc = '%' . $conn->real_escape_string($q) . '%';
      $where[] = "(i.name LIKE '{$qEsc}' OR i.description LIKE '{$qEsc}')";
    }
    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    $lim  = (int)$limit;
    $off  = (int)$offset;

    $sql = "
      SELECT
        i.id,
        i.name,
        i.description,
        i.price,
        i.discount,
        i.image_url,
        i.category_id,
        i.rating,
        i.order_count,
        i.created_at,
        i.updated_at
      FROM items i
      {$whereSql}
      ORDER BY {$orderBy}
      LIMIT {$lim} OFFSET {$off}
    ";

    $res = $conn->query($sql);
    if ($res) {
      while ($r = $res->fetch_assoc()) {
        $rows[] = $r;
      }
      $res->free();
    }

  } else {
    throw new RuntimeException(
      'DB connection not available. تأكد أن config.php يعرّف $pdo (PDO) أو $conn (mysqli).'
    );
  }

  echo json_encode(['ok' => true, 'items' => $rows], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
