<?php
// iforenta_api/add_driver.php
error_reporting(0);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

include 'config.php';

// قراءة الـ JSON body أو الـ POST
$input = json_decode(file_get_contents('php://input'), true);
$name  = trim($input['name'] ?? $_POST['name'] ?? '');
$phone = trim($input['phone'] ?? $_POST['phone'] ?? '');
$pass  = trim($input['password'] ?? $_POST['password'] ?? '');

if ($name === '' || $phone === '' || $pass === '') {
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>'name, phone and password are required']);
    exit;
}

// هشّ كلمة المرور
$hash = password_hash($pass, PASSWORD_BCRYPT);

$sql  = "INSERT INTO drivers (name,phone,password) VALUES (?,?,?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $name, $phone, $hash);

if ($stmt->execute()) {
    echo json_encode(['status'=>'success','id'=>$stmt->insert_id]);
} else {
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>$stmt->error]);
}
?>
