<?php
/*
=====================================================================
teacher/activities/delete.php — حذف نشاط
⚠️ حذف النشاط يمحو معه كل تسليمات الطلاب ودرجاتهم!
=====================================================================
*/

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../core/Auth.php';
require_once '../../core/Logger.php';
require_once '../../app/models/Activity.php';

/* ==================== الصلاحية: معلم فقط ==================== */
if (!Auth::check() || (int)($_SESSION['role_id'] ?? 0) !== 2) {
    die('Access Denied');
}

$db = (new Database())->connect();

$stmt = $db->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->execute([(int)$_SESSION['user_id']]);
$teacherId = (int)$stmt->fetchColumn();

if ($teacherId <= 0) {
    die('Teacher Not Found');
}

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die('Activity ID Not Found');
}

/*
====================================================================
✅ نقرأ بيانات النشاط قبل الحذف
مع عدد التسليمات والمصححة منها — لتوثيق حجم الفقد
====================================================================
*/
$stmt = $db->prepare("
    SELECT a.*, c.course_name,
           (SELECT COUNT(*) FROM activity_submissions s
             WHERE s.activity_id = a.id) AS submissions,
           (SELECT COUNT(*) FROM activity_submissions s
             WHERE s.activity_id = a.id AND s.grade IS NOT NULL) AS graded
    FROM activities a
    INNER JOIN courses c ON a.course_id = c.id
    WHERE a.id = ? AND a.teacher_id = ?
");
$stmt->execute([$id, $teacherId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {

    Logger::log(
        'activities',
        'delete_denied',
        "محاولة حذف نشاط لا يملكه المعلم (activity_id=$id)",
        null, null, 'danger'
    );

    die('النشاط غير موجود أو لا تملك صلاحية حذفه');
}

$title       = (string)$row['title'];
$attachment  = (string)($row['attachment'] ?? '');
$submissions = (int)$row['submissions'];
$graded      = (int)$row['graded'];

/*
==================== حماية: تسليمات مصححة ====================
حذف نشاط فيه تسليمات مصححة يمحو درجات الطلاب نهائياً.
نمنع الحذف ونطلب تأكيداً صريحاً.
*/
if ($graded > 0 && !isset($_GET['confirm'])) {

    Logger::log(
        'activities',
        'delete_blocked',
        "محاولة حذف نشاط ($title) وبه $graded تسليماً مصححاً — رُفضت",
        'course',
        (int)$row['course_id'],
        'warning'
    );

    die(
        "⛔ لا يمكن حذف النشاط: يحتوي على <strong>$graded تسليماً مصححاً</strong>"
        . " من أصل $submissions — حذفه يمحو درجات الطلاب نهائياً.<br><br>"
        . "إن كنت متأكداً، استخدم الرابط: "
        . "<a href='delete.php?id=$id&confirm=1' "
        . "onclick=\"return confirm('سيتم محو $graded درجة نهائياً. متأكد؟');\">"
        . "تأكيد الحذف</a><br><br>"
        . "<a href='index.php'>رجوع</a>"
    );
}

/* ==================== الحذف ==================== */
$model  = new Activity();
$result = $model->delete($id);

if (!$result) {
    die('Delete Failed');
}

/* حذف المرفق من القرص — لا نترك ملفات يتيمة */
$fileDeleted = false;

if ($attachment !== '') {

    $fullPath = '../../' . $attachment;

    if (strpos($attachment, 'uploads/activities/') === 0 && is_file($fullPath)) {
        $fileDeleted = @unlink($fullPath);
    }
}

/* ==================== التسجيل ==================== */
Logger::log(
    'activities',
    'delete_activity',
    "حذف نشاط ($title) - مقرر ({$row['course_name']})"
    . " | فُقد معه: $submissions تسليماً ($graded مصححاً)"
    . ($attachment !== ''
        ? ' | المرفق: ' . ($fileDeleted ? 'حُذف' : 'تعذر حذفه')
        : ''),
    'course',
    (int)$row['course_id'],
    'danger'
);

header("Location: " . BASE_URL . "/teacher/activities/index.php");
exit;