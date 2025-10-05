<?php
header("Content-Type: application/json; charset=UTF-8");
include 'config.php';

$username = $_POST['username'] ?? "";
$phone    = $_POST['phone']    ?? "";
$password = $_POST['password'] ?? "";

// التحقق من إتمام الحقول
if ($username === "" || $phone === "" || $password === "") {
    echo json_encode([
        "status"  => "error",
        "message" => "بيانات ناقصة"
    ]);
    exit;
}

// التحقق إن كان رقم الهاتف موجوداً مسبقاً
$query = "SELECT * FROM app_users WHERE phone = ?";
$stmt  = $conn->prepare($query);
$stmt->bind_param("s", $phone);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode([
        "status"  => "error",
        "message" => "رقم الهاتف مستخدم مسبقًا"
    ]);
    exit;
}

// إدراج سجل المستخدم الجديد
$insertQuery = "INSERT INTO app_users (username, phone, password) VALUES (?, ?, ?)";
$insertStmt  = $conn->prepare($insertQuery);
$insertStmt->bind_param("sss", $username, $phone, $password);

if ($insertStmt->execute()) {
    echo json_encode([
        "status" => "success"
    ]);
} else {
    echo json_encode([
        "status"  => "error",
        "message" => "فشل التسجيل"
    ]);
}

exit;
?>
