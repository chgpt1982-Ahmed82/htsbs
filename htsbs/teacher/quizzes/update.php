<?php
/*
=====================================================================
teacher/quizzes/update.php — تعديل اختبار
⚠️ كان 10 أسطر بلا أي حماية: أي مستخدم يعدّل أي اختبار بتغيير ?id=
=====================================================================
*/

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../core/Auth.php';
require_once '../../core/Logger.php';
require_once '../../app/models/Quiz.php';

/* ==================== الصلاحية: معلم فقط ==================== */
if (!Auth::check() || (int)($_SESSION['role_id'] ?? 0) !== 2) {
    die('Access Denied');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/teacher/quizzes/index.php");
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
    die('Quiz ID Not Found');
}

$title = trim((string)($_POST['title'] ?? ''));

if ($title === '') {
    die('عنوان الاختبار مطلوب');
}

/*
====================================================================
✅ الاختبار القديم — يُقرأ قبل التعديل، مع التأكد من الملكية
====================================================================
*/
$stmt = $db->prepare("
    SELECT q.*, c.course_name
    FROM quizzes q
    INNER JOIN courses c ON q.course_id = c.id
    WHERE q.id = ? AND q.teacher_id = ?
");
$stmt->execute([$id, $teacherId]);
$old = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$old) {

    Logger::log(
        'quizzes',
        'update_denied',
        "محاولة تعديل اختبار لا يملكه المعلم (quiz_id=$id)",
        null, null, 'danger'
    );

    die('الاختبار غير موجود أو لا تملك صلاحية تعديله');
}

$oldTitle     = (string)$old['title'];
$oldPublished = (int)$old['is_published'];
$newPublished = isset($_POST['is_published']) ? 1 : 0;

/* ==================== التحديث ==================== */
try {

    $model = new Quiz();
    $model->update($id, $_POST);

} catch (Throwable $ex) {

    Logger::log(
        'quizzes',
        'update_failed',
        "فشل تعديل اختبار (quiz_id=$id)",
        null, null, 'danger'
    );

    die('تعذر حفظ التعديل');
}

/* ==================== التسجيل ==================== */
$label = ($oldTitle !== $title) ? "$oldTitle ← $title" : $title;

/* تغيير حالة النشر حدث مهم (نشر اختبار = إتاحته للطلاب) */
$publishNote = '';
if ($oldPublished !== $newPublished) {
    $publishNote = $newPublished
        ? ' | ⚠️ تم نشره (أصبح متاحاً للطلاب)'
        : ' | تم إخفاؤه (مسودة)';
}

Logger::log(
    'quizzes',
    'update_quiz',
    "تعديل اختبار ($label) - مقرر ({$old['course_name']})" . $publishNote,
    'quiz',
    $id,
    'warning'
);

header("Location: " . BASE_URL . "/teacher/quizzes/index.php");
exit;