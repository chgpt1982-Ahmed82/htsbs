<?php
/*
=====================================================================
تصدير سجل النشاط إلى CSV — الأدمن فقط
يحترم نفس الفلاتر المرسلة من صفحة index.php
=====================================================================
*/

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../core/Auth.php';
require_once '../../core/Logger.php';

/* ==================== الصلاحية: أدمن فقط ==================== */
if (!Auth::check()) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

if ((int)($_SESSION['role_id'] ?? 0) !== 1) {
    die('Access Denied');
}

$database = new Database();
$db = $database->connect();

/* ==================== الفلاتر (نفس صفحة العرض) ==================== */
$q        = trim((string)($_GET['q'] ?? ''));
$source   = (string)($_GET['source'] ?? '');
$module   = (string)($_GET['module'] ?? '');
$action   = (string)($_GET['action_f'] ?? '');
$userId   = (int)($_GET['user_id'] ?? 0);
$severity = (string)($_GET['severity'] ?? '');
$from     = (string)($_GET['from'] ?? '');
$to       = (string)($_GET['to'] ?? '');

if (!in_array($source, ['system', 'lms'], true))               $source = '';
if (!in_array($severity, ['info', 'warning', 'danger'], true)) $severity = '';

$derived = "
(
    SELECT 'system' AS source, l.id AS id, l.user_id AS user_id,
           COALESCE(u.full_name, l.user_name, 'زائر') AS user_name,
           l.role_id AS role_id, l.module AS module, l.action AS action,
           l.details AS details, l.severity AS severity,
           l.ip_address AS ip_address, l.created_at AS created_at
    FROM system_logs l
    LEFT JOIN users u ON l.user_id = u.id

    UNION ALL

    SELECT 'lms' AS source, l.id AS id, l.user_id AS user_id,
           COALESCE(u.full_name, 'زائر') AS user_name,
           u.role_id AS role_id, 'lms' AS module, l.action AS action,
           l.details AS details, 'info' AS severity,
           l.ip_address AS ip_address, l.created_at AS created_at
    FROM lms_logs l
    LEFT JOIN users u ON l.user_id = u.id
) AS logs
";

$where  = [];
$params = [];

if ($q !== '') {
    $where[] = "(logs.details LIKE ? OR logs.action LIKE ? OR logs.user_name LIKE ?)";
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like);
}
if ($source !== '')   { $where[] = "logs.source = ?";   $params[] = $source; }
if ($module !== '')   { $where[] = "logs.module = ?";   $params[] = $module; }
if ($action !== '')   { $where[] = "logs.action = ?";   $params[] = $action; }
if ($userId > 0)      { $where[] = "logs.user_id = ?";  $params[] = $userId; }
if ($severity !== '') { $where[] = "logs.severity = ?"; $params[] = $severity; }
if ($from !== '')     { $where[] = "DATE(logs.created_at) >= ?"; $params[] = $from; }
if ($to !== '')       { $where[] = "DATE(logs.created_at) <= ?"; $params[] = $to; }

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

/* حد أقصى للتصدير حماية من استهلاك الذاكرة على الاستضافة المشتركة */
$sql = "SELECT * FROM $derived $whereSql
        ORDER BY logs.created_at DESC, logs.id DESC
        LIMIT 10000";

$stmt = $db->prepare($sql);
$stmt->execute($params);

/* سجل عملية التصدير نفسها */
Logger::log('system', 'export_logs', 'تصدير سجل النشاط إلى CSV');

/* ==================== إخراج CSV ==================== */
$fileName = 'system_logs_' . date('Y-m-d_His') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Pragma: no-cache');
header('Expires: 0');

while (ob_get_level() > 0) {
    ob_end_clean();
}

$out = fopen('php://output', 'w');

// BOM لدعم العربية في Excel
fwrite($out, "\xEF\xBB\xBF");

$roleLabels = [1 => 'أدمن', 2 => 'معلم', 3 => 'طالب', 4 => 'ولي أمر'];
$sevLabels  = ['info' => 'عادي', 'warning' => 'تنبيه', 'danger' => 'خطر'];

fputcsv($out, [
    'التاريخ والوقت',
    'المستخدم',
    'الدور',
    'المصدر',
    'الوحدة',
    'الإجراء',
    'التفاصيل',
    'الخطورة',
    'IP',
]);

while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($out, [
        date('Y-m-d H:i:s', strtotime((string)$r['created_at'])),
        $r['user_name'] ?? 'زائر',
        $roleLabels[(int)($r['role_id'] ?? 0)] ?? '—',
        $r['source'] === 'lms' ? 'التعلم التفاعلي' : 'النظام',
        $r['module'],
        $r['action'],
        $r['details'] ?? '',
        $sevLabels[$r['severity'] ?? 'info'] ?? 'عادي',
        $r['ip_address'] ?? '',
    ]);
}

fclose($out);
exit;
