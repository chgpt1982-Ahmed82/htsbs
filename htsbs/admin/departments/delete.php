<?php
/*
=====================================================================
admin/departments/delete.php — حذف قسم
⚠️ الاسم يُقرأ قبل الحذف — بعده يضيع نهائياً
⚠️ القسم هو الجذر: يُمنع حذفه إذا كان مرتبطاً بطلاب أو معلمين أو مقررات
=====================================================================
*/

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../core/Auth.php';
require_once '../../core/Logger.php';
require_once '../../app/models/Department.php';

/* ==================== الصلاحية: أدمن فقط ==================== */
if (!Auth::check() || (int)($_SESSION['role_id'] ?? 0) !== 1) {
    die('Access Denied');
}

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die('Department ID Not Found');
}

$department = new Department();

/* ✅ نقرأ بيانات القسم قبل الحذف */
$row = $department->getById($id);

if (!$row) {
    die('Department Not Found');
}

$name  = trim((string)($row['department_name'] ?? ''));
$label = ($name !== '' ? $name : ('#' . $id));

$db = (new Database())->connect();

/*
==================== حماية ثلاثية ====================
القسم هو الجذر في هرم البيانات:
  القسم ← الطلاب + المعلمون + المقررات
حذفه وهو مرتبط بأي منها يهدم سلسلة كاملة من البيانات
*/
$stmt = $db->prepare("
    SELECT
        (SELECT COUNT(*) FROM students WHERE department_id = ?) AS students,
        (SELECT COUNT(*) FROM teachers WHERE department_id = ?) AS teachers,
        (SELECT COUNT(*) FROM courses  WHERE department_id = ?) AS courses
");
$stmt->execute([$id, $id, $id]);
$counts = $stmt->fetch(PDO::FETCH_ASSOC);

$students = (int)$counts['students'];
$teachers = (int)$counts['teachers'];
$courses  = (int)$counts['courses'];

if ($students > 0 || $teachers > 0 || $courses > 0) {

    /* نسجّل المحاولة المرفوضة */
    Logger::log(
        'departments',
        'delete_blocked',
        "محاولة حذف القسم ($label) — رُفضت: "
        . "$students طالباً، $teachers معلماً، $courses مقرراً",
        'department',
        $id,
        'warning'
    );

    die(
        "لا يمكن حذف القسم: مرتبط بـ $students طالباً و $teachers معلماً و $courses مقرراً.<br>"
        . "انقل هذه السجلات إلى قسم آخر أولاً."
    );
}

/* ==================== الحذف ==================== */
$result = $department->delete($id);

if ($result) {

    /* خطورة "خطر" تلقائياً — صف أحمر في لوحة السجلات */
    Logger::deleted('departments', $label, $id);

    header("Location: " . BASE_URL . "/admin/departments/index.php");
    exit;

} else {
    die('Delete Failed');
}