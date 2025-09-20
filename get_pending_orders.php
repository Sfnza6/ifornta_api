<?php
// get_pending_orders.php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require_once __DIR__ . '/config.php';

function out($data, int $code = 200) {
  http_response_code($code);
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

// ======================
// بارامترات اختيارية
// ======================
// الحالة: افتراضياً pending
$input  = array_merge($_GET, $_POST);
$status = isset($input['status']) && $input['status'] !== ''
  ? trim((string)$input['status'])
  : 'pending';

// بحث اختياري بالاسم / الهاتف / رقم الطلب
$q = trim((string)($input['q'] ?? ''));

// حد وازاحة اختيارية (للتقسيم Paging)
$limit  = max(1, (int)($input['limit'] ?? 100));
$offset = max(0, (int)($input['offset'] ?? 0));

// ======================
// بناء الاستعلام المحضّر
// ======================
$sql = "
  SELECT id, customer_name, customer_phone, address, total, status, created_at
  FROM orders
  WHERE status = ?
";

$types = 's';
$args  = [$status];

if ($q !== '') {
  $sql .= " AND (customer_name LIKE ? OR customer_phone LIKE ? OR id = ?)";
  $types .= 'ssi';

  $like = '%' . $q . '%';
  $idAsInt = (int)$q; // لو كان q رقم هيطابق id، وإلا صفر لن يطابق
  $args[] = $like;
  $args[] = $like;
  $args[] = $idAsInt;
}

$sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$types .= 'ii';
$args[]  = $limit;
$args[]  = $offset;

$stmt = $conn->prepare($sql);
if (!$stmt) {
  out(['status' => 'error', 'message' => $conn->error], 500);
}

$stmt->bind_param($types, ...$args);
$stmt->execute();
$res = $stmt->get_result();

$list = [];
while ($row = $res->fetch_assoc()) {
  $list[] = [
    'id'             => (int)$row['id'],
    'customer_name'  => (string)$row['customer_name'],
    'customer_phone' => (string)$row['customer_phone'],
    'address'        => (string)$row['address'],
    'total'          => (float)$row['total'],
    'status'         => (string)$row['status'],
    'created_at'     => (string)$row['created_at'],
  ];
}

$stmt->close();

out([
  'status' => 'success',
  'count'  => count($list),
  'data'   => $list,
]);
