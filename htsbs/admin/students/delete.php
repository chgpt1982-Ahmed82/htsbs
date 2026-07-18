<?php
/*
=====================================================================
admin/students/delete.php — حذف طالب
⚠️ البيانات تُقرأ قبل الحذف — بعده تضيع نهائياً
=====================================================================
*/

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../core/Auth.php';
require_once '../../core/Logger.php';
require_once '../../app/models/Student.php';

/* ==================== الصلاحية: أدمن فقط ==================== */
if (!Auth::check() || (int)($_SESSION['role_id'] ?? 0) !== 1) {
    die('Access Denied');
}

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die('Student ID Not Found');
}

$student = new Student();

/* ✅ نقرأ بيانات الطالب قبل الحذف */
$row = $student->getById($id);

if (!$row) {
    die('Student Not Found');
}

$name = trim((string)($row['full_name'] ?? ''));

$label = ($name !== '' ? $name : ('#' . $id))
       . (!empty($row['student_number']) ? ' (' . $row['student_number'] . ')' : '')
       . (!empty($row['class_name']) ? ' - ' . $row['class_name'] : '');

/*
==================== توثيق حجم الفقد ====================
حذف الطالب يمحو معه كل تقدمه في التعلم التفاعلي:
النجوم والشارات والمحاولات والشهادات.
نوثّق ذلك في السجل قبل الحذف — لأنه غير قابل للاسترجاع.
*/
$database = new Database();
$db = $database->connect();

$extra = '';

try {
    $stmt = $db->prepare("
        SELECT
            (SELECT COUNT(*) FROM lms_stars                    WHERE student_id = ?) AS stars,
            (SELECT COUNT(*) FROM lms_student_badges           WHERE student_id = ?) AS badges,
            (SELECT COUNT(*) FROM lms_student_activity_attempts WHERE student_id = ?) AS attempts,
            (SELECT COUNT(*) FROM lms_certificates             WHERE student_id = ?) AS certs
    ");
    $stmt->execute([$id, $id, $id, $id]);
    $lms = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($lms && ((int)$lms['attempts'] > 0 || (int)$lms['stars'] > 0)) {
        $extra = ' | فُقد معه: '
               . (int)$lms['stars']    . ' نجمة، '
               . (int)$lms['badges']   . ' شارة، '
               . (int)$lms['attempts'] . ' محاولة، '
               . (int)$lms['certs']    . ' شهادة';
    }

} catch (Throwable $ex) {
    /* جداول LMS قد لا تكون مستوردة بعد — نتجاهل بصمت */
}

/* ==================== الحذف ==================== */
$result = $student->delete($id);

if ($result) {

    /* خطورة "خطر" تلقائياً — صف أحمر في لوحة السجلات */
    Logger::deleted('students', $label . $extra, $id);

    header("Location: " . BASE_URL . "/admin/students/index.php");
    exit;

} else {
    die('Delete Failed');
}