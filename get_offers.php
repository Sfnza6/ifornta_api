<?php
// get_offers.php
error_reporting(0);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

include 'config.php';


$sql = "SELECT * FROM offers";
$result = $conn->query($sql);

$offers = [];
while ($row = $result->fetch_assoc()) {
    $offers[] = $row;
}

echo json_encode($offers);
?>
