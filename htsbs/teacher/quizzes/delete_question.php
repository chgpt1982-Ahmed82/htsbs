<?php
/*
=====================================================================
teacher/quizzes/delete_question.php — حذف سؤال من اختبار
=====================================================================
⚠️ كان مفتوحاً تماماً: أي مستخدم يحذف أي سؤال بتغيير ?id=
=====================================================================
*/

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../core/Auth.php';
require_once '../../core/Logger.php';
require_once '../../app/models/QuizQuestion.php';

/* ==================== الصلاحية: معلم فقط ==================== */
if (!Auth::check() || (int)($_SESSION['role_id'] ?? 0) !== 2) {
    die('Access Denied');
}

$db = (new Database())->connect();

/* سجل المعلم */
$stmt = $db->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->execute([(int)$_SESSION['user_id']]);
$teacherId = (int)$stmt->fetchColumn();

if ($teacherId <= 0) {
    die('Teacher Not Found');
}

$questionId = (int)($_GET['id'] ?? 0);

if ($questionId <= 0) {
    die('Question ID Not Found');
}

/*
====================================================================
✅ نقرأ السؤال قبل الحذف — مع التأكد أن اختباره يملكه المعلم
(الربط: السؤال → الاختبار → المعلم)
====================================================================
*/
$stmt = $db->prepare("
    SELECT qq.id, qq.quiz_id, qq.question_type, qq.marks,
           q.title AS quiz_title
    FROM quiz_questions qq
    INNER JOIN quizzes q ON qq.quiz_id = q.id
    WHERE qq.id = ? AND q.teacher_id = ?
");
$stmt->execute([$questionId, $teacherId]);
$question = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$question) {

    Logger::log(
        'quizzes',
        'delete_question_denied',
        "محاولة حذف سؤال لا يملكه المعلم (question_id=$questionId)",
        null, null, 'danger'
    );

    die('السؤال غير موجود أو لا تملك صلاحية حذفه');
}

/* quiz_id الموثوق — من قاعدة البيانات لا من $_GET */
$quizId = (int)$question['quiz_id'];

/* ==================== الحذف ==================== */
$result = $model = new QuizQuestion();
$deleted = $model->delete($questionId);

if (!$deleted) {
    die('Delete Failed');
}

/* ==================== التسجيل ==================== */
$typeLabels = [
    'multiple_choice' => 'اختيار من متعدد',
    'true_false'      => 'صح أو خطأ',
    'short_answer'    => 'إجابة قصيرة',
];

Logger::log(
    'quizzes',
    'delete_question',
    "حذف سؤال من الاختبار ({$question['quiz_title']})"
    . " - النوع (" . ($typeLabels[$question['question_type']] ?? $question['question_type']) . ")"
    . " - الدرجة ({$question['marks']})",
    'quiz',
    $quizId,
    'warning'
);

/* quiz_id من قاعدة البيانات — آمن للاستخدام في التوجيه */
header("Location: questions.php?id=" . $quizId);
exit;