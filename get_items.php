<?php
// iforenta_api/get_items.php
error_reporting(0);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

include 'config.php';

$featured = intval($_GET['featured'] ?? 0);
if ($featured===1) {
    $stmt = $conn->prepare("SELECT * FROM items WHERE is_featured=1");
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    $res = $conn->query("SELECT * FROM items");
}

$items = [];
while ($row = $res->fetch_assoc()) {
    $items[] = $row;
}
echo json_encode($items);
?>
