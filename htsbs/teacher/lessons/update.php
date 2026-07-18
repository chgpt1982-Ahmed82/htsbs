<?php
/*
=====================================================================
teacher/lessons/update.php — تعديل درس
=====================================================================
⚠️ النسخة السابقة كانت 12 سطراً بلا أي حماية:
   لا صلاحيات، لا تحقق من الملكية، لا تحقق من المدخلات.
   أي مستخدم يعدّل أي درس في النظام بتغيير ?id=
=====================================================================
*/

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../core/Auth.php';
require_once '../../core/Logger.php';
require_once '../../app/models/Lesson.php';

/* ==================== الصلاحية: معلم فقط ==================== */
if (!Auth::check() || (int)($_SESSION['role_id'] ?? 0) !== 2) {
    die('Access Denied');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/teacher/lessons/index.php");
    exit;
}

$db = (new Database())->connect();

$model     = new Lesson();
$teacherId = (int)$model->getTeacherId((int)$_SESSION['user_id']);

if ($teacherId <= 0) {
    die('Teacher Not Found');
}

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die('Lesson ID Not Found');
}

/* ==================== التحقق من المدخلات ==================== */
$title       = trim((string)($_POST['lesson_title'] ?? ''));
$description = trim((string)($_POST['lesson_description'] ?? ''));
$type        = trim((string)($_POST['lesson_type'] ?? ''));
$videoLink   = trim((string)($_POST['video_link'] ?? ''));

if ($title === '') {
    die('عنوان الدرس مطلوب');
}

$allowedTypes = ['pdf', 'ppt', 'video', 'link'];

if (!in_array($type, $allowedTypes, true)) {
    die('نوع الدرس غير صالح');
}

if ($videoLink !== '' && !filter_var($videoLink, FILTER_VALIDATE_URL)) {
    die('رابط الفيديو غير صالح');
}

/*
====================================================================
✅ الدرس القديم — يُقرأ قبل التعديل
مع التأكد أنه من إنشاء هذا المعلم (حماية من التلاعب بالمعرّف)
====================================================================
*/
$stmt = $db->prepare("
    SELECT l.*, c.course_name
    FROM lessons l
    INNER JOIN courses c ON l.course_id = c.id
    WHERE l.id = ? AND l.teacher_id = ?
");
$stmt->execute([$id, $teacherId]);
$old = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$old) {

    Logger::log(
        'lessons',
        'update_denied',
        "محاولة تعديل درس لا يملكه المعلم (lesson_id=$id)",
        null, null, 'danger'
    );

    die('الدرس غير موجود أو لا تملك صلاحية تعديله');
}

$oldTitle = (string)$old['lesson_title'];

/* ==================== التحديث ==================== */
try {

    $model->update($id, $_POST);

} catch (Throwable $ex) {

    Logger::log(
        'lessons',
        'update_failed',
        "فشل تعديل درس (lesson_id=$id)",
        null, null, 'danger'
    );

    die('تعذر حفظ التعديل');
}

/* ==================== التسجيل ==================== */
$label = ($oldTitle !== $title)
    ? "$oldTitle ← $title"
    : $title;

Logger::log(
    'lessons',
    'update_lesson',
    "تعديل درس ($label) - مقرر ({$old['course_name']})"
    . (((string)$old['lesson_type'] !== $type)
        ? " | النوع: {$old['lesson_type']} ← $type"
        : ''),
    'course',
    (int)$old['course_id'],
    'warning'
);

header("Location: " . BASE_URL . "/teacher/lessons/index.php");
exit;