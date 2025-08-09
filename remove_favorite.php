<?php
// iforenta_api/remove_favorite.php
error_reporting(0);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

include 'config.php';

$input = json_decode(file_get_contents('php://input'), true);
$user_id = intval($input['user_id'] ?? ($_POST['user_id'] ?? 0));
$item_id = intval($input['item_id'] ?? ($_POST['item_id'] ?? 0));

if ($user_id <= 0 || $item_id <= 0) {
  http_response_code(400);
  echo json_encode(['status'=>'error','message'=>'invalid parameters']);
  exit;
}

$sql = "DELETE FROM favorites WHERE user_id=? AND item_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $item_id);

if ($stmt->execute()) {
  echo json_encode(['status'=>'success']);
} else {
  http_response_code(500);
  echo json_encode(['status'=>'error','message'=>$stmt->error]);
}
