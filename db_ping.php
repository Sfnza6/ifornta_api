<?php
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . '/config.php';

if (!$conn) {
  echo json_encode([
    "ok" => false,
    "error" => $GLOBALS['DB_CONNECT_ERROR'] ?? 'unknown',
  ], JSON_UNESCAPED_UNICODE);
  exit;
}

$ver = $conn->server_info;
$r = $conn->query("SHOW TABLES");
$tables = [];
while ($row = $r->fetch_array()) { $tables[] = $row[0]; }

echo json_encode(["ok"=>true, "version"=>$ver, "tables"=>$tables], JSON_UNESCAPED_UNICODE);
