<?php
// iforenta_api/get_favorites.php
declare(strict_types=1);

// ========== Headers / CORS ==========
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require_once __DIR__ . '/config.php';
mysqli_set_charset($conn, 'utf8mb4');

// ========== Helpers ==========
function jdie(bool $ok, array $data = [], int $code = 200): void {
  http_response_code($code);
  echo json_encode(['ok' => $ok] + $data, JSON_UNESCAPED_UNICODE);
  exit;
}
function intParam($arr, string $key, int $default = 0): int {
  if (!isset($arr[$key])) return $default;
  return (int)filter_var($arr[$key], FILTER_VALIDATE_INT, ['options' => ['default' => $default]]);
}

// ========== Inputs ==========
$user_id = intParam($_GET, 'user_id');
if ($user_id <= 0) jdie(false, ['message' => 'invalid user_id'], 400);

// ترقيم اختياري
$limit  = intParam($_GET, 'limit', 100);
$offset = intParam($_GET, 'offset', 0);
if ($limit <= 0 || $limit > 500) $limit = 100;
if ($offset < 0) $offset = 0;

// ========== Query ==========
$sql = "
  SELECT
    f.id          AS favorite_id,
    i.id          AS item_id,
    i.name        AS name,
    i.price       AS price,
    i.image_url   AS image_url,
    i.description AS description,
    i.category_id AS category_id,
    COALESCE(i.rating, 0)      AS rating,
    COALESCE(i.order_count, 0) AS order_count
  FROM favorites f
  JOIN items i ON i.id = f.item_id
  WHERE f.user_id = ?
  ORDER BY f.id DESC
  LIMIT ? OFFSET ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $user_id, $limit, $offset);
$stmt->execute();
$res = $stmt->get_result();

// ========== Build Response ==========
$favorites = [];
while ($row = $res->fetch_assoc()) {
  $favorites[] = [
    'favorite_id' => (int)$row['favorite_id'],
    'item_id'     => (int)$row['item_id'],
    'name'        => (string)($row['name'] ?? ''),
    'price'       => (float)$row['price'],
    'image_url'   => (string)($row['image_url'] ?? ''),
    'description' => (string)($row['description'] ?? ''),
    'category_id' => (int)$row['category_id'],
    'rating'      => (float)$row['rating'],
    'order_count' => (int)$row['order_count'],
  ];
}

// عدّاد إجمالي (اختياري)
$count = 0;
$cntSql = "SELECT COUNT(*) AS c FROM favorites WHERE user_id = ?";
$cnt = $conn->prepare($cntSql);
$cnt->bind_param("i", $user_id);
$cnt->execute();
$cntRes = $cnt->get_result();
if ($cntRow = $cntRes->fetch_assoc()) $count = (int)$cntRow['c'];

jdie(true, [
  'favorites' => $favorites,
  'count'     => $count,
  'limit'     => $limit,
  'offset'    => $offset,
]);
