<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

$limit = isset($_GET['limit']) ? max(1, min(200, (int)$_GET['limit'])) : 50;

try {
  $rows = [];

  // 1) استخدم PDO لو متوفر في config.php
  if (isset($pdo) && $pdo instanceof PDO) {
    $stmt = $pdo->prepare("
      SELECT id, name, description, price, discount, image_url,
             category_id, created_at, updated_at
      FROM items
      ORDER BY id DESC
      LIMIT :lim
    ");
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // 2) أو استخدم mysqli لو متوفر في config.php
  } elseif (isset($conn) && $conn instanceof mysqli) {
    $lim  = (int)$limit;
    $sql  = "SELECT id, name, description, price, discount, image_url,
                    category_id, created_at, updated_at
             FROM items
             ORDER BY id DESC
             LIMIT $lim";
    $res  = $conn->query($sql);
    if ($res) {
      while ($r = $res->fetch_assoc()) { $rows[] = $r; }
      $res->free();
    }

  // 3) فولباك: حاول بناء اتصال PDO من ثوابت DB_* لو معرّفة
  }  else {
    throw new Exception('DB connection not available. تأكد أن config.php يعرّف $pdo (PDO) أو $conn (mysqli) أو ثوابت DB_HOST/DB_NAME/DB_USER/DB_PASS.');
  }

  echo json_encode(['ok' => true, 'items' => $rows], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false, 'message'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
