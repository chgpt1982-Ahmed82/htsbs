<?php
/*
=====================================================================
teacher/activities/update.php — تعديل نشاط
⚠️ كان 13 سطراً بلا أي حماية: أي مستخدم يعدّل أي نشاط بتغيير ?id=
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/teacher/activities/index.php");
    exit;
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

/* ==================== التحقق من المدخلات ==================== */
$title        = trim((string)($_POST['title'] ?? ''));
$instructions = trim((string)($_POST['instructions'] ?? ''));
$maxGrade     = (float)($_POST['max_grade'] ?? 0);
$dueDate      = trim((string)($_POST['due_date'] ?? ''));

if ($title === '') {
    die('عنوان النشاط مطلوب');
}

if ($maxGrade <= 0 || $maxGrade > 1000) {
    die('الدرجة العظمى يجب أن تكون بين 1 و 1000');
}

/*
====================================================================
✅ النشاط القديم — يُقرأ قبل التعديل
مع التأكد أنه من إنشاء هذا المعلم
====================================================================
*/
$stmt = $db->prepare("
    SELECT a.*, c.course_name,
           (SELECT COUNT(*) FROM activity_submissions s WHERE s.activity_id = a.id) AS submissions
    FROM activities a
    INNER JOIN courses c ON a.course_id = c.id
    WHERE a.id = ? AND a.teacher_id = ?
");
$stmt->execute([$id, $teacherId]);
$old = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$old) {

    Logger::log(
        'activities',
        'update_denied',
        "محاولة تعديل نشاط لا يملكه المعلم (activity_id=$id)",
        null, null, 'danger'
    );

    die('النشاط غير موجود أو لا تملك صلاحية تعديله');
}

$oldTitle    = (string)$old['title'];
$oldMaxGrade = (float)$old['max_grade'];
$submissions = (int)$old['submissions'];

/*
⚠️ تحذير: تغيير الدرجة العظمى بعد وجود تسليمات مصححة
يجعل الدرجات السابقة غير متسقة (طالب أخذ 8/10 والآن العظمى 20؟)
نسمح به لكن نسجّله بخطورة عالية
*/
$gradeChanged = (abs($oldMaxGrade - $maxGrade) > 0.001);

/* ==================== التحديث ==================== */
try {

    $model = new Activity();
    $model->update($id, $_POST);

} catch (Throwable $ex) {

    Logger::log(
        'activities',
        'update_failed',
        "فشل تعديل نشاط (activity_id=$id)",
        null, null, 'danger'
    );

    die('تعذر حفظ التعديل');
}

/* ==================== التسجيل ==================== */
$label = ($oldTitle !== $title) ? "$oldTitle ← $title" : $title;

Logger::log(
    'activities',
    'update_activity',
    "تعديل نشاط ($label) - مقرر ({$old['course_name']})"
    . ($gradeChanged
        ? " | ⚠️ الدرجة العظمى: $oldMaxGrade ← $maxGrade"
          . ($submissions > 0 ? " (يوجد $submissions تسليماً متأثراً!)" : '')
        : ''),
    'course',
    (int)$old['course_id'],
    ($gradeChanged && $submissions > 0) ? 'danger' : 'warning'
);

header("Location: " . BASE_URL . "/teacher/activities/index.php");
exit;