<?php
// delete_item.php
error_reporting(0);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

include 'config.php';

$input = json_decode(file_get_contents('php://input'), true);
$id    = intval($input['id'] ?? ($_POST['id'] ?? 0));

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>'id is required.']);
    exit;
}

$sql  = "DELETE FROM items WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(['status'=>'success']);
} else {
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>$stmt->error]);
}
?>
