<?php
// update_offer.php
error_reporting(0);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

include 'config.php';

$input = json_decode(file_get_contents('php://input'), true);
if (json_last_error() === JSON_ERROR_NONE) {
    $id        = intval($input['id'] ?? 0);
    $title     = trim($input['title'] ?? '');
    $image_url = trim($input['image_url'] ?? '');
} else {
    $id        = intval($_POST['id'] ?? 0);
    $title     = trim($_POST['title'] ?? '');
    $image_url = trim($_POST['image_url'] ?? '');
}

if ($id <= 0 || $title === '' || $image_url === '') {
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>'id, title and image_url are required.']);
    exit;
}

$sql  = "UPDATE offers SET title = ?, image_url = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssi", $title, $image_url, $id);

if ($stmt->execute()) {
    echo json_encode(['status'=>'success']);
} else {
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>$stmt->error]);
}
?>
