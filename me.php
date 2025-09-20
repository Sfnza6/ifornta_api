<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require_once __DIR__.'/config.php';
function out($arr, int $code=200){ http_response_code($code); echo json_encode($arr, JSON_UNESCAPED_UNICODE); exit; }

$hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = '';
if (preg_match('~Bearer\s+([A-Za-z0-9]+)~', $hdr, $m)) { $token = $m[1]; }
elseif (!empty($_GET['token'])) { $token = $_GET['token']; }
if ($token === '') out(['status'=>'error','message'=>'No token'], 401);

$stmt = $conn->prepare("SELECT id,name,phone,avatar_url,role,is_active FROM admins WHERE api_token=? LIMIT 1");
$stmt->bind_param('s', $token);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) out(['status'=>'error','message'=>'Invalid token'], 401);
$row = $res->fetch_assoc();

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'];
$base   = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$root   = "$scheme://$host$base/..";

$avatar = (string)$row['avatar_url'];
if ($avatar !== '' && !preg_match('~^https?://~i',$avatar)) {
  $avatar = $root.'/'.ltrim($avatar,'/');
}

out([
  'status'=>'success',
  'admin'=>[
    'id'    => (int)$row['id'],
    'name'  => $row['name'],
    'phone' => $row['phone'],
    'avatar_url' => $avatar,
    'role'  => $row['role'],
  ]
]);
