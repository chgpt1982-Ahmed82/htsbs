<?php
/*
=====================================================================
admin/teachers/delete.php — حذف معلم
⚠️ البيانات تُقرأ قبل الحذف — بعده تضيع نهائياً
⚠️ يُمنع الحذف إذا كان للمعلم دروس أو إسنادات
=====================================================================
*/

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../core/Auth.php';
require_once '../../core/Logger.php';
require_once '../../app/models/Teacher.php';

/* ==================== الصلاحية: أدمن فقط ==================== */
if (!Auth::check() || (int)($_SESSION['role_id'] ?? 0) !== 1) {
    die('Access Denied');
}

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die('Teacher ID Not Found');
}

$teacher = new Teacher();

/* ✅ نقرأ بيانات المعلم قبل الحذف */
$row = $teacher->getById($id);

if (!$row) {
    die('Teacher Not Found');
}

$name = trim((string)($row['full_name'] ?? ''));

$label = ($name !== '' ? $name : ('#' . $id))
       . (!empty($row['specialization']) ? ' - ' . $row['specialization'] : '')
       . (!empty($row['email']) ? ' | ' . $row['email'] : '');

$db = (new Database())->connect();

/*
==================== حماية: الإسنادات ====================
حذف معلم له إسنادات يقطع ارتباط الصفوف بمقرراتها
*/
$stmt = $db->prepare("SELECT COUNT(*) FROM course_assignments WHERE teacher_id = ?");
$stmt->execute([$id]);
$assignments = (int)$stmt->fetchColumn();

/*
==================== حماية: دروس التعلم التفاعلي ====================
حذف معلم له دروس يمحو الدروس والأنشطة ومعها تقدّم الطلاب كاملاً
(جداول LMS قد لا تكون مستوردة بعد — لذلك try/catch)
*/
$lessons = 0;

try {
    $stmt = $db->prepare("SELECT COUNT(*) FROM lms_lessons WHERE teacher_id = ?");
    $stmt->execute([$id]);
    $lessons = (int)$stmt->fetchColumn();
} catch (Throwable $ex) {
    /* وحدة LMS غير مثبتة — نتجاهل */
}

if ($assignments > 0 || $lessons > 0) {

    /* نسجّل المحاولة المرفوضة */
    Logger::log(
        'teachers',
        'delete_blocked',
        "محاولة حذف المعلم ($label) — رُفضت: "
        . "$assignments إسناد، $lessons درس تفاعلي",
        'teacher',
        $id,
        'warning'
    );

    die(
        "لا يمكن حذف المعلم: مرتبط بـ $assignments إسناد و $lessons درس تفاعلي.<br>"
        . "احذف إسناداته ودروسه أولاً، أو انقلها إلى معلم آخر."
    );
}

/* ==================== الحذف ==================== */
$result = $teacher->delete($id);

if ($result) {

    /* خطورة "خطر" تلقائياً — صف أحمر في لوحة السجلات */
    Logger::deleted('teachers', $label, $id);

    header("Location: " . BASE_URL . "/admin/teachers/index.php");
    exit;

} else {
    die('Delete Failed');
}