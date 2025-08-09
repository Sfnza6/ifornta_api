<?php
// get_featured_items.php
error_reporting(0);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

include 'config.php';

$sql = "SELECT items.*, categories.name AS category_name
        FROM items
        LEFT JOIN categories ON items.category_id = categories.id
        WHERE items.is_featured = 1";
$result = $conn->query($sql);

$featured = [];
while ($row = $result->fetch_assoc()) {
    $featured[] = $row;
}

echo json_encode($featured);
?>
