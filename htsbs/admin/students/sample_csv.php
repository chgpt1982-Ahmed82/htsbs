<?php
/*
=====================================================================
admin/students/sample_csv.php — تحميل ملف CSV نموذجي للاستيراد
=====================================================================
*/

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../core/Auth.php';

if (!Auth::check() || (int)($_SESSION['role_id'] ?? 0) !== 1) {
    die('Access Denied');
}

$db = (new Database())->connect();

/* أول صف موجود فعلاً — ليكون المثال صالحاً للاستيراد مباشرة */
$sampleClass = (string)$db->query(
    "SELECT class_name FROM classes ORDER BY id LIMIT 1"
)->fetchColumn();

if ($sampleClass === '') {
    $sampleClass = 'اسم_الصف';
}

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="students_sample.csv"');

while (ob_get_level() > 0) {
    ob_end_clean();
}

$out = fopen('php://output', 'w');

/* BOM لدعم العربية في Excel */
fwrite($out, "\xEF\xBB\xBF");

fputcsv($out, [
    'الرقم الأكاديمي',
    'الرقم الشخصي',
    'اسم الطالب',
    'الصف',
    'المعدل',
    'هاتف ولي الأمر',
    'هاتف الطالب',
]);

fputcsv($out, ['20260001', '990012345', 'أحمد محمد علي',  $sampleClass, '3.5', '33001122', '36001122']);
fputcsv($out, ['20260002', '990012346', 'فاطمة سالم خالد', $sampleClass, '3.8', '33003344', '36003344']);

fclose($out);
exit;