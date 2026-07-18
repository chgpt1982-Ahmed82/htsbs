<?php
/*
=====================================================================
teacher/lessons/delete.php — حذف درس
⚠️ البيانات تُقرأ قبل الحذف — بعده تضيع نهائياً
⚠️ النسخة السابقة كانت مفتوحة: أي مستخدم يحذف أي درس بتغيير ?id=
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

/*
====================================================================
✅ نقرأ بيانات الدرس قبل الحذف
مع التأكد أنه من إنشاء هذا المعلم
====================================================================
*/
$stmt = $db->prepare("
    SELECT l.*, c.course_name
    FROM lessons l
    INNER JOIN courses c ON l.course_id = c.id
    WHERE l.id = ? AND l.teacher_id = ?
");
$stmt->execute([$id, $teacherId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {

    Logger::log(
        'lessons',
        'delete_denied',
        "محاولة حذف درس لا يملكه المعلم (lesson_id=$id)",
        null, null, 'danger'
    );

    die('الدرس غير موجود أو لا تملك صلاحية حذفه');
}

$title    = (string)$row['lesson_title'];
$filePath = (string)($row['file_path'] ?? '');

/* ==================== الحذف ==================== */
$result = $model->delete($id);

if (!$result) {
    die('Delete Failed');
}

/*
====================================================================
حذف الملف المرفق من القرص
النسخة السابقة كانت تحذف السجل فقط، فتتراكم ملفات يتيمة
تستهلك مساحة الاستضافة إلى الأبد
====================================================================
*/
$fileDeleted = false;

if ($filePath !== '') {

    $fullPath = '../../' . $filePath;

    /* حماية: لا نحذف إلا داخل مجلد الرفع */
    if (strpos($filePath, 'uploads/lessons/') === 0 && is_file($fullPath)) {
        $fileDeleted = @unlink($fullPath);
    }
}

/* ==================== التسجيل ==================== */
Logger::log(
    'lessons',
    'delete_lesson',
    "حذف درس ($title) - مقرر ({$row['course_name']}) - النوع ({$row['lesson_type']})"
    . ($filePath !== ''
        ? ' | الملف: ' . ($fileDeleted ? 'حُذف من القرص' : 'تعذر حذفه')
        : ''),
    'course',
    (int)$row['course_id'],
    'danger'   /* حذف — صف أحمر */
);

header("Location: " . BASE_URL . "/teacher/lessons/index.php");
exit;