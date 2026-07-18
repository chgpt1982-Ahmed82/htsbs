<?php
/*
=====================================================================
teacher/quizzes/delete.php — حذف اختبار
⚠️ يمحو معه الأسئلة ومحاولات الطلاب ودرجاتهم
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

/*
====================================================================
✅ نقرأ بيانات الاختبار قبل الحذف
مع عدد الأسئلة والمحاولات — لتوثيق حجم الفقد
====================================================================
*/
$stmt = $db->prepare("
    SELECT q.*, c.course_name,
           (SELECT COUNT(*) FROM quiz_questions qq WHERE qq.quiz_id = q.id) AS questions,
           (SELECT COUNT(*) FROM quiz_attempts qa WHERE qa.quiz_id = q.id)  AS attempts
    FROM quizzes q
    INNER JOIN courses c ON q.course_id = c.id
    WHERE q.id = ? AND q.teacher_id = ?
");
$stmt->execute([$id, $teacherId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {

    Logger::log(
        'quizzes',
        'delete_denied',
        "محاولة حذف اختبار لا يملكه المعلم (quiz_id=$id)",
        null, null, 'danger'
    );

    die('الاختبار غير موجود أو لا تملك صلاحية حذفه');
}

$title     = (string)$row['title'];
$questions = (int)$row['questions'];
$attempts  = (int)$row['attempts'];

/*
==================== حماية: محاولات الطلاب ====================
حذف اختبار حلّه طلاب يمحو نتائجهم نهائياً
*/
if ($attempts > 0 && !isset($_GET['confirm'])) {

    Logger::log(
        'quizzes',
        'delete_blocked',
        "محاولة حذف اختبار ($title) وبه $attempts محاولة طالب — رُفضت",
        'quiz', $id, 'warning'
    );

    die(
        "⛔ لا يمكن حذف الاختبار: حلّه <strong>$attempts طالباً</strong> — "
        . "حذفه يمحو نتائجهم نهائياً.<br><br>"
        . "<a href='delete.php?id=$id&confirm=1' "
        . "onclick=\"return confirm('سيتم محو $attempts محاولة نهائياً. متأكد؟');\">"
        . "تأكيد الحذف</a> — <a href='index.php'>رجوع</a>"
    );
}

/* ==================== الحذف ==================== */
try {

    $model  = new Quiz();
    $result = $model->delete($id);

    if (!$result) {
        die('Delete Failed');
    }

} catch (Throwable $ex) {

    Logger::log(
        'quizzes',
        'delete_failed',
        "فشل حذف اختبار (quiz_id=$id)",
        null, null, 'danger'
    );

    die('تعذر حذف الاختبار');
}

/* ==================== التسجيل ==================== */
Logger::log(
    'quizzes',
    'delete_quiz',
    "حذف اختبار ($title) - مقرر ({$row['course_name']})"
    . " | فُقد معه: $questions سؤالاً، $attempts محاولة طالب",
    'quiz',
    $id,
    'danger'
);

header("Location: " . BASE_URL . "/teacher/quizzes/index.php");
exit;