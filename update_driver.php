<?php
// iforenta_api/update_driver.php
error_reporting(0);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

include 'config.php';

$input = json_decode(file_get_contents('php://input'), true);
$id    = intval($input['id'] ?? $_POST['id'] ?? 0);
$name  = trim($input['name'] ?? $_POST['name'] ?? '');
$phone = trim($input['phone'] ?? $_POST['phone'] ?? '');
$pass  = trim($input['password'] ?? ''); // اختياري: إذا تريد تغييرها

if ($id<=0 || $name==='' || $phone==='') {
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>'id,name and phone required']);
    exit;
}

// إذا وردت كلمة سر جديدة هشّها
if ($pass !== '') {
    $hash = password_hash($pass, PASSWORD_BCRYPT);
    $sql  = "UPDATE drivers SET name=?, phone=?, password=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $name, $phone, $hash, $id);
} else {
    $sql  = "UPDATE drivers SET name=?, phone=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $name, $phone, $id);
}

if ($stmt->execute()) {
    echo json_encode(['status'=>'success']);
} else {
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>$stmt->error]);
}
?>
