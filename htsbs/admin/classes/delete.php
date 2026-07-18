<?php
/*
=====================================================================
admin/classes/delete.php — حذف صف
⚠️ الاسم يُقرأ قبل الحذف — بعده يضيع نهائياً
=====================================================================
*/

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../core/Auth.php';
require_once '../../core/Logger.php';
require_once '../../app/models/ClassModel.php';

/* ==================== الصلاحية: أدمن فقط ==================== */
if (!Auth::check() || (int)($_SESSION['role_id'] ?? 0) !== 1) {
    die('Access Denied');
}

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die('Class ID Not Found');
}

$class = new ClassModel();

/* ✅ نقرأ بيانات الصف قبل الحذف */
$row = $class->getById($id);

if (!$row) {
    die('Class Not Found');
}

$label = trim((string)($row['class_name'] ?? ''));
$label = ($label !== '' ? $label : ('#' . $id))
       . (!empty($row['academic_year']) ? ' | ' . $row['academic_year'] : '');

/*
==================== تحذير: فحص الطلاب المرتبطين ====================
حذف صف فيه طلاب يقطع ارتباطهم بكل مقرراتهم ودروسهم التفاعلية.
نمنع الحذف ونطلب نقل الطلاب أولاً.
*/
$database = new Database();
$db = $database->connect();

$stmt = $db->prepare("SELECT COUNT(*) FROM students WHERE class_id = ?");
$stmt->execute([$id]);
$studentsCount = (int)$stmt->fetchColumn();

if ($studentsCount > 0) {

    /* نسجّل المحاولة المرفوضة (مفيد أمنياً) */
    Logger::log(
        'classes',
        'delete_blocked',
        "محاولة حذف الصف ($label) وبه $studentsCount طالباً — رُفضت",
        'class',
        $id,
        'warning'
    );

    die("لا يمكن حذف الصف: يوجد $studentsCount طالباً مرتبطاً به. انقل الطلاب إلى صف آخر أولاً.");
}

/* ==================== الحذف ==================== */
$result = $class->delete($id);

if ($result) {

    /* خطورة "خطر" تلقائياً — صف أحمر في لوحة السجلات */
    Logger::deleted('classes', $label, $id);

    header("Location: " . BASE_URL . "/admin/classes/index.php");
    exit;

} else {
    die('Delete Failed');
}