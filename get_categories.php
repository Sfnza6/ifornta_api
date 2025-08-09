<?php
error_reporting(0);                // إخفاء التحذيرات
header('Content-Type: application/json');
include 'config.php';

$sql = "SELECT * FROM categories";
$result = $conn->query($sql);

$categories = [];
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

echo json_encode($categories);
?>
