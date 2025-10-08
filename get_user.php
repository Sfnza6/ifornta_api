<?php
// /iforenta_api/get_user.php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
ob_start();

require_once __DIR__ . '/config.php';

// دالة تحويل صف لهيكل موحّد
function map_user(array $row): array {
    return [
        'id'         => (int)$row['id'],
        'username'   => $row['username'] ?? '',
        'name'       => $row['username'] ?? '',    // للتوافق مع موديل التطبيق
        'phone'      => $row['phone'] ?? '',
        'avatar_url' => $row['avatar_url'] ?? '',
        'created_at' => $row['created_at'] ?? '',
        'role'       => 'user',
    ];
}

// مدخلات
$id    = isset($_GET['id'])    ? (int)$_GET['id'] : 0;
$phone = isset($_GET['phone']) ? trim($_GET['phone']) : '';
$q     = isset($_GET['q'])     ? trim($_GET['q'])     : '';
$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = max(1, min(200, (int)($_GET['limit'] ?? 50)));
$offset = ($page - 1) * $limit;

try {
    // ===== حالة: مستخدم واحد (id أو phone)
    if ($id > 0 || $phone !== '') {
        if ($id > 0) {
            $sql  = "SELECT id, username, phone, COALESCE(avatar_url,'') AS avatar_url, COALESCE(created_at,'') AS created_at
                     FROM app_users WHERE id = ? LIMIT 1";
            $stmt = $conn->prepare($sql);
            if (!$stmt) { throw new Exception('DB prepare failed: '.$conn->error); }
            $stmt->bind_param('i', $id);
        } else {
            $sql  = "SELECT id, username, phone, COALESCE(avatar_url,'') AS avatar_url, COALESCE(created_at,'') AS created_at
                     FROM app_users WHERE phone = ? LIMIT 1";
            $stmt = $conn->prepare($sql);
            if (!$stmt) { throw new Exception('DB prepare failed: '.$conn->error); }
            $stmt->bind_param('s', $phone);
        }

        $stmt->execute();
        $res = $stmt->get_result();

        if ($row = $res->fetch_assoc()) {
            $user = map_user($row);
            if (function_exists('ob_get_length') && ob_get_length()) { @ob_clean(); }
            echo json_encode(['status' => 'success', 'user' => $user], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (function_exists('ob_get_length') && ob_get_length()) { @ob_clean(); }
        echo json_encode(['status' => 'error', 'message' => 'المستخدم غير موجود'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ===== حالة: قائمة المستخدمين (للأدمن)
    // فلترة/بحث
    $where = "WHERE 1";
    $types = '';
    $binds = [];

    if ($q !== '') {
        $where .= " AND (username LIKE ? OR phone LIKE ?)";
        $like = "%$q%";
        $types .= 'ss';
        $binds[] = $like;
        $binds[] = $like;
    }

    // إجمالي للصفحات
    $countSql = "SELECT COUNT(*) AS cnt FROM app_users $where";
    $countStmt = $conn->prepare($countSql);
    if (!$countStmt) { throw new Exception('DB prepare failed: '.$conn->error); }
    if ($types !== '') { $countStmt->bind_param($types, ...$binds); }
    $countStmt->execute();
    $total = (int)($countStmt->get_result()->fetch_assoc()['cnt'] ?? 0);

    // جلب الصفحة
    $sql = "SELECT id, username, phone, COALESCE(avatar_url,'') AS avatar_url, COALESCE(created_at,'') AS created_at
            FROM app_users
            $where
            ORDER BY id DESC
            LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) { throw new Exception('DB prepare failed: '.$conn->error); }

    // ربط باراميترات البحث + limit/offset
    if ($types !== '') {
        $types2 = $types . 'ii';
        $params = array_merge($binds, [$limit, $offset]);
        $stmt->bind_param($types2, ...$params);
    } else {
        $stmt->bind_param('ii', $limit, $offset);
    }

    $stmt->execute();
    $res = $stmt->get_result();

    $list = [];
    while ($row = $res->fetch_assoc()) {
        $list[] = map_user($row);
    }

    if (function_exists('ob_get_length') && ob_get_length()) { @ob_clean(); }
    // نُرجع data ومرادف users للتوافق مع أي تطبيق قديم
    echo json_encode([
        'status' => 'success',
        'data'   => $list,
        'users'  => $list,
        'page'   => $page,
        'limit'  => $limit,
        'total'  => $total,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    if (function_exists('ob_get_length') && ob_get_length()) { @ob_clean(); }
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
