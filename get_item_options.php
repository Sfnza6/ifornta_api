<?php
// get_item_options.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config.php';

function out($arr) {
  echo json_encode($arr, JSON_UNESCAPED_UNICODE);
  exit;
}

$item_id = intval($_GET['item_id'] ?? $_GET['id'] ?? 0);
if ($item_id <= 0) {
  out(['ok' => false, 'message' => 'item_id مطلوب']);
}

/* --- Addons --- */
$addons = [];
if ($stmt = $conn->prepare("SELECT id, name, price FROM item_addons WHERE item_id = ? ORDER BY id ASC")) {
  $stmt->bind_param("i", $item_id);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($row = $res->fetch_assoc()) {
    $row['price'] = (float)$row['price'];
    $addons[] = $row;
  }
  $stmt->close();
}

/* --- Removable --- */
$removes = [];
if ($stmt = $conn->prepare("SELECT id, name FROM item_removes WHERE item_id = ? ORDER BY id ASC")) {
  $stmt->bind_param("i", $item_id);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($row = $res->fetch_assoc()) {
    $removes[] = $row;
  }
  $stmt->close();
}

out([
  'ok' => true,
  'addons' => $addons,
  'removes' => $removes,
]);
