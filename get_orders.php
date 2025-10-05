<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
// منع الكاش (مهم جدًا لتفادي ومضة الاختفاء)
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
if ($_SERVER['REQUEST_METHOD']==='OPTIONS') exit;

require_once __DIR__.'/config.php';
if (!isset($conn) || !$conn) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'message'=>'DB not initialized'], JSON_UNESCAPED_UNICODE);
  exit;
}
mysqli_set_charset($conn,'utf8mb4');

function norm($s){
  $x=strtolower(trim($s));
  return in_array($x,['approved','accepted','preparing','in_prep','readying'], true) ? 'processing' : $x;
}

$userId   = isset($_GET['user_id'])?(int)$_GET['user_id']:0;
$statusQ  = isset($_GET['status'])? strtolower(trim((string)$_GET['status'])) : '';
$q        = isset($_GET['q'])? trim((string)$_GET['q']) : '';
$limit    = max(1, min(500, (int)($_GET['limit'] ?? 200)));
$offset   = max(0, (int)($_GET['offset'] ?? 0));

// الحالات الجارية (المناسبة لواجهة اليوزر)
$liveStatuses = [
  'pending','processing','approved','accepted','preparing',
  'assigned','delivering','out_for_delivery','ready'
];

// سياسة: إن ما بعتّش status → رجّع الجارية (liveStatuses)
$all = false; $statuses=[];
if ($statusQ==='all') {
  $all = true; // بدون فلترة حالة
} elseif ($statusQ!=='') {
  $statuses = array_values(array_filter(array_map('trim', explode(',', $statusQ))));
  if (!$statuses) $statuses = $liveStatuses;
} else {
  $statuses = $liveStatuses; // default: الجارية
}

$sql = "SELECT o.*,
        (SELECT COALESCE(SUM(oi.quantity),0) FROM app_order_items oi WHERE oi.order_id=o.id) AS items_count
        FROM app_orders o";

$where=[]; $params=[]; $types='';

if ($userId>0){ $where[]="o.user_id=?"; $params[]=$userId; $types.='i'; }

// فلترة الحالة
if (!$all && $statuses){
  $ph = implode(',', array_fill(0,count($statuses),'?'));
  $where[]="LOWER(o.status) IN ($ph)";
  foreach ($statuses as $s){ $params[]=strtolower($s); $types.='s'; }
}

// بحث اختياري
if ($q!==''){
  if (ctype_digit($q)){ $where[]="(o.id=? OR o.address LIKE ?)"; $params[]=(int)$q; $types.='i'; $params[]="%$q%"; $types.='s'; }
  else { $where[]="o.address LIKE ?"; $params[]="%$q%"; $types.='s'; }
}

if ($where) $sql.=" WHERE ".implode(' AND ',$where);
$sql.=" ORDER BY o.id DESC LIMIT ? OFFSET ?";
$params[]=$limit; $types.='i'; $params[]=$offset; $types.='i';

$st = mysqli_prepare($conn,$sql);
if (!$st) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'message'=>'Prepare failed: '.mysqli_error($conn)], JSON_UNESCAPED_UNICODE);
  exit;
}
$bind = [$st,$types];
foreach ($params as $k=>$_){ $bind[]=&$params[$k]; }
call_user_func_array('mysqli_stmt_bind_param',$bind);

if (!mysqli_stmt_execute($st)) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'message'=>'Execute failed: '.mysqli_stmt_error($st)], JSON_UNESCAPED_UNICODE);
  exit;
}

$res = mysqli_stmt_get_result($st);
$out=[];
while($r=mysqli_fetch_assoc($res)){
  $orig = (string)$r['status'];
  $out[] = [
    'id'               => (int)$r['id'],
    'user_id'          => (int)$r['user_id'],
    'driver_id'        => isset($r['driver_id'])?(int)$r['driver_id']:null,
    'address'          => (string)($r['address']??''),
    'payment_method'   => (int)($r['payment_method']??0),
    'total'            => (float)($r['total']??0),
    'status'           => $orig,
    'normalized_status'=> norm($orig),
    'created_at'       => (string)($r['created_at']??''),
    'status_order'     => (string)($r['status_order']??''),
    'dest_lat'         => isset($r['dest_lat'])?(float)$r['dest_lat']:0.0,
    'dest_lng'         => isset($r['dest_lng'])?(float)$r['dest_lng']:0.0,
    'pickup_lat'       => isset($r['pickup_lat'])?(float)$r['pickup_lat']:0.0,
    'pickup_lng'       => isset($r['pickup_lng'])?(float)$r['pickup_lng']:0.0,
    'items_count'      => (int)$r['items_count'],
  ];
}

echo json_encode(['ok'=>true,'count'=>count($out),'orders'=>$out], JSON_UNESCAPED_UNICODE);
