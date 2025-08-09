<?php
header("Content-Type: application/json; charset=UTF-8");
include 'config.php';

$phone    = $_POST['phone']    ?? "";
$password = $_POST['password'] ?? "";

// التحقق من إتمام الحقول
if ($phone === "" || $password === "") {
    echo json_encode([
        "status"  => "error",
        "message" => "بيانات ناقصة"
    ]);
    exit;
}

// التحقق من صحة بيانات الدخول
$query = "SELECT * FROM users WHERE phone = ? AND password = ?";
$stmt  = $conn->prepare($query);
$stmt->bind_param("ss", $phone, $password);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $userData = $result->fetch_assoc();
    echo json_encode([
        "status" => "success",
        "data"   => [
            "id"       => $userData['id'],
            "username" => $userData['username'],
            "phone"    => $userData['phone']
            // يمكنك إضافة أي حقول أخرى تحتاجها مثل created_at أو عنوان المستخدم
        ]
    ]);
} else {
    echo json_encode([
        "status"  => "error",
        "message" => "رقم الهاتف أو كلمة المرور غير صحيحة"
    ]);
}

exit;
?>
