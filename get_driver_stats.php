<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config.php';

// Ensure $pdo is initialized
if (!isset($pdo)) {
    // Replace the DSN, username, and password with your actual database credentials
    $dsn = 'mysql:host=localhost;dbname=your_database;charset=utf8mb4';
    $username = 'your_username';
    $password = 'your_password';
    try {
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $e) {
        echo json_encode(['ok'=>false,'msg'=>'Database connection failed'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

$driver_id = isset($_GET['driver_id']) ? intval($_GET['driver_id']) : 0;
if ($driver_id <= 0) {
  echo json_encode(['ok'=>false,'msg'=>'driver_id مفقود أو غير صالح'], JSON_UNESCAPED_UNICODE);
  exit;
}

$scalar = function($q,$p=[]) use($pdo){ $s=$pdo->prepare($q); $s->execute($p); return $s->fetchColumn(); };

$delivered = (int)  $scalar("SELECT COUNT(*) FROM app_orders WHERE driver_id=? AND TRIM(LOWER(status))='delivered'", [$driver_id]);
$pending   = (int)  $scalar("SELECT COUNT(*) FROM app_orders WHERE driver_id=? AND TRIM(LOWER(status))='pending'",   [$driver_id]);
$rejected  = (int)  $scalar("SELECT COUNT(*) FROM app_orders WHERE driver_id=? AND TRIM(LOWER(status))='rejected'",  [$driver_id]);

$earnings  = (float)$scalar("SELECT COALESCE(SUM(total),0) FROM app_orders WHERE driver_id=? AND TRIM(LOWER(status))='delivered'", [$driver_id]);
$pending_amount = (float)$scalar("SELECT COALESCE(SUM(total),0) FROM app_orders WHERE driver_id=? AND TRIM(LOWER(status))='pending'",   [$driver_id]);

echo json_encode([
  'ok' => true,
  'driver_id' => $driver_id,
  'stats' => [
    'delivered'        => $delivered,
    'pending'          => $pending,
    'rejected'         => $rejected,
    'delivered_count'  => $delivered,
    'pending_count'    => $pending,
    'rejected_count'   => $rejected,
    'earnings'         => $earnings,
    'profit'           => $earnings,
    'pending_amount'   => $pending_amount,
    'dues'             => $pending_amount,
    'debt'             => $pending_amount,
  ]
], JSON_UNESCAPED_UNICODE);