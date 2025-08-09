<?php
// iforenta_api/get_drivers.php
error_reporting(0);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

include 'config.php';

$res = $conn->query("SELECT id,name,phone,created_at FROM drivers ORDER BY id DESC");
$drivers = [];
while ($row = $res->fetch_assoc()) {
    $drivers[] = $row;
}
echo json_encode($drivers);
?>
