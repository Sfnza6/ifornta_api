<?php
error_reporting(0);                // إخفاء التحذيرات
header('Content-Type: application/json');
include 'config.php';

$id = $_POST['id'];
$name = $_POST['name'];
$image_url = $_POST['image_url'];

$sql = "UPDATE categories SET name = ?, image_url = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssi", $name, $image_url, $id);

if ($stmt->execute()) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "message" => $stmt->error]);
}
?>
