<?php
// iforenta_api/get_favorites.php
error_reporting(0);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

include 'config.php';

$user_id = intval($_GET['user_id'] ?? 0);
if ($user_id <= 0) {
  http_response_code(400);
  echo json_encode(['error'=>'invalid user_id']);
  exit;
}

$sql = "
  SELECT f.id AS fav_id,
         i.id AS id,
         i.name,
         i.price,
         i.image_url,
         i.description,
         i.category_id
  FROM favorites f
  JOIN items i ON f.item_id = i.id
  WHERE f.user_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

$favs = [];
while ($row = $res->fetch_assoc()) {
  $favs[] = $row;
}
echo json_encode($favs);
