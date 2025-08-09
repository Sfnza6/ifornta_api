<?php
// iforenta_api/add_item.php
error_reporting(0);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

include 'config.php';

$input = json_decode(file_get_contents('php://input'), true);
if (json_last_error()===JSON_ERROR_NONE) {
    $name        = trim($input['name'] ?? '');
    $description = trim($input['description'] ?? '');
    $price       = floatval($input['price'] ?? 0);
    $imageUrl    = trim($input['image_url'] ?? '');
    $categoryId  = intval($input['category_id'] ?? 0);
    $isFeatured  = intval($input['is_featured'] ?? 0);
} else {
    $name        = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price       = floatval($_POST['price'] ?? 0);
    $imageUrl    = trim($_POST['image_url'] ?? '');
    $categoryId  = intval($_POST['category_id'] ?? 0);
    $isFeatured  = intval($_POST['is_featured'] ?? 0);
}

if ($name===''||$imageUrl===''||$categoryId<=0) {
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>'name,image_url,category_id required']);
    exit;
}

$sql  = "INSERT INTO items (name,description,price,image_url,category_id,is_featured)
         VALUES (?,?,?,?,?,?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssdssi", $name,$description,$price,$imageUrl,$categoryId,$isFeatured);

if ($stmt->execute()) {
    echo json_encode(['status'=>'success','id'=>$stmt->insert_id]);
} else {
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>$stmt->error]);
}
?>
