<?php
// add_offer.php
error_reporting(0);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

include 'config.php';

// قراءة JSON أو form-data
$input = json_decode(file_get_contents('php://input'), true);
if (json_last_error() === JSON_ERROR_NONE) {
    $title     = trim($input['title'] ?? '');
    $image_url = trim($input['image_url'] ?? '');
} else {
    $title     = trim($_POST['title'] ?? '');
    $image_url = trim($_POST['image_url'] ?? '');
}

if ($title === '' || $image_url === '') {
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>'Both title and image_url are required.']);
    exit;
}

$sql  = "INSERT INTO offers (title, image_url) VALUES (?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $title, $image_url);

if ($stmt->execute()) {
    echo json_encode(['status'=>'success','id'=>$stmt->insert_id]);
} else {
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>$stmt->error]);
}
?>
