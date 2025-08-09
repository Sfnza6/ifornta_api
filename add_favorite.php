<?php
// iforenta_api/add_favorite.php
error_reporting(0);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

include 'config.php';

$input = json_decode(file_get_contents('php://input'), true);
if (json_last_error() === JSON_ERROR_NONE) {
  $user_id = intval($input['user_id'] ?? 0);
  $item_id = intval($input['item_id'] ?? 0);
} else {
  $user_id = intval($_POST['user_id'] ?? 0);
  $item_id = intval($_POST['item_id'] ?? 0);
}

if ($user_id <= 0 || $item_id <= 0) {
  http_response_code(400);
  echo json_encode(['status'=>'error','message'=>'invalid parameters']);
  exit;
}

// تأكد من عدم التكرار
$sqlChk = "SELECT id FROM favorites WHERE user_id=? AND item_id=?";
$stmt = $conn->prepare($sqlChk);
$stmt->bind_param("ii", $user_id, $item_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->fetch_assoc()) {
  echo json_encode(['status'=>'success','message'=>'already exists']);
  exit;
}

$sql = "INSERT INTO favorites (user_id, item_id) VALUES (?, ?)";
$stmt2 = $conn->prepare($sql);
$stmt2->bind_param("ii", $user_id, $item_id);

if ($stmt2->execute()) {
  echo json_encode(['status'=>'success','fav_id'=>$stmt2->insert_id]);
} else {
  http_response_code(500);
  echo json_encode(['status'=>'error','message'=>$stmt2->error]);
}
