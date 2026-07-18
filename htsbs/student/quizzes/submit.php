<?php
/*
=====================================================================
student/quizzes/submit.php — تسليم إجابات اختبار قصير
=====================================================================
🔴 مشاكل خطيرة في النسخة السابقة:
   - لا session_start ولا فحص دور الطالب إطلاقاً!
   - لا تحقق أن الاختبار معيَّن لصف الطالب
   - لا حد لعدد المحاولات (attempts_allowed في quizzes غير مستخدم)
   - لا تحقق من انتهاء موعد الاختبار (end_date)
   - لا Transaction (فشل جزئي يترك attempt بلا إجابات كاملة)

التعديلات: كل ما سبق + تسجيل + تعريب الواجهة
=====================================================================
*/

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../core/Auth.php';
require_once '../../core/Logger.php';
require_once '../../app/models/Quiz.php';
require_once '../../app/models/QuizQuestion.php';

/* ==================== الصلاحية: طالب فقط ==================== */
if (!Auth::check() || (int)($_SESSION['role_id'] ?? 0) !== 3) {
    die('Access Denied');
}

if (!function_exists('e')) {
    function e($v): string {
        return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

if (empty($_POST['quiz_id']) || empty($_POST['answers']) || !is_array($_POST['answers'])) {
    die('لم تُرسَل أي إجابات');
}

$quizId = (int)$_POST['quiz_id'];

$db = (new Database())->connect();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$quizModel     = new Quiz();
$questionModel = new QuizQuestion();

/* ==================== سجل الطالب ==================== */
$stmt = $db->prepare("SELECT id, class_id FROM students WHERE user_id = ?");
$stmt->execute([(int)$_SESSION['user_id']]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    die('Student Not Found');
}

$studentId = (int)$student['id'];
$classId   = (int)$student['class_id'];

/*
====================================================================
🔴 الاختبار — مع التأكد أنه معيَّن فعلاً لصف الطالب ومنشور
النسخة السابقة كانت تجلب أي اختبار بمعرّفه بلا أي تحقق
====================================================================
*/
$stmt = $db->prepare("
    SELECT q.*, c.course_name
    FROM quizzes q
    INNER JOIN courses c ON q.course_id = c.id
    WHERE q.id = ?
      AND q.is_published = 1
      AND EXISTS (
          SELECT 1 FROM quiz_assignments qa
          WHERE qa.quiz_id = q.id AND qa.class_id = ?
      )
");
$stmt->execute([$quizId, $classId]);
$quiz = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$quiz) {

    Logger::log(
        'quizzes',
        'submit_denied',
        "محاولة تسليم اختبار غير معيَّن لصف الطالب (quiz_id=$quizId)",
        'quiz', $quizId, 'warning'
    );

    die('الاختبار غير موجود أو غير متاح لصفك');
}

/*
====================================================================
🔴 التحقق من نافذة الاختبار الزمنية
النسخة السابقة كانت تقبل التسليم في أي وقت
====================================================================
*/
$now = time();

if (!empty($quiz['start_date']) && $now < strtotime($quiz['start_date'])) {
    die('لم يبدأ الاختبار بعد');
}

if (!empty($quiz['end_date']) && $now > strtotime($quiz['end_date'])) {

    Logger::log(
        'quizzes',
        'submit_late_denied',
        "محاولة تسليم اختبار ({$quiz['title']}) بعد انتهاء موعده",
        'quiz', $quizId, 'warning'
    );

    die('انتهى موعد هذا الاختبار');
}

/*
====================================================================
🔴 التحقق من عدد المحاولات المسموحة
attempts_allowed في جدول quizzes كان غير مستخدم إطلاقاً —
فالطالب يستطيع حل نفس الاختبار عدد لا نهائي من المرات
====================================================================
*/
$stmt = $db->prepare("
    SELECT COUNT(*) FROM quiz_results WHERE quiz_id = ? AND student_id = ?
");
$stmt->execute([$quizId, $studentId]);
$previousAttempts = (int)$stmt->fetchColumn();

$attemptsAllowed = (int)($quiz['attempts_allowed'] ?? 1);

if ($attemptsAllowed > 0 && $previousAttempts >= $attemptsAllowed) {

    Logger::log(
        'quizzes',
        'submit_attempts_exceeded',
        "محاولة تجاوز عدد المحاولات المسموحة لاختبار ({$quiz['title']}) "
        . "- المسموح: $attemptsAllowed، المُنفَّذ: $previousAttempts",
        'quiz', $quizId, 'warning'
    );

    die("استنفدت عدد المحاولات المسموحة ($attemptsAllowed)");
}

/* ==================== الأسئلة ==================== */
$questions      = $questionModel->getQuestions($quizId);
$totalQuestions = count($questions);

if ($totalQuestions === 0) {
    die('لا توجد أسئلة في هذا الاختبار');
}

$attemptNumber = $previousAttempts + 1;

/*
====================================================================
الحفظ داخل Transaction
النسخة السابقة بلا Transaction: فشل منتصف حلقة الإجابات
يترك attempt بدرجة جزئية بلا إجابات كاملة — نتيجة غير موثوقة
====================================================================
*/
$score = 0;

try {

    $db->beginTransaction();

    $attemptId = (int)$quizModel->createAttempt($quizId, $studentId, $totalQuestions);

    if ($attemptId <= 0) {
        throw new Exception('فشل إنشاء المحاولة');
    }

    foreach ($questions as $question) {

        $questionId    = (int)$question['id'];
        $studentAnswer = trim((string)($_POST['answers'][$questionId] ?? ''));

        $isCorrect = 0;

        if ($studentAnswer !== ''
            && strcasecmp($studentAnswer, (string)$question['correct_answer']) === 0) {
            $isCorrect = 1;
            $score += (float)$question['marks'];
        }

        $quizModel->saveAnswer($attemptId, $questionId, $studentAnswer, $isCorrect);
    }

    $stmt = $db->prepare("UPDATE quiz_attempts SET score = ? WHERE id = ?");
    $stmt->execute([$score, $attemptId]);

    $stmt = $db->prepare("
        INSERT INTO quiz_results
            (quiz_id, student_id, score, attempt_number, started_at, completed_at)
        VALUES (?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([$quizId, $studentId, $score, $attemptNumber]);

    $db->commit();

} catch (Throwable $ex) {

    if ($db->inTransaction()) {
        $db->rollBack();
    }

    Logger::log(
        'quizzes',
        'submit_failed',
        "فشل تسليم اختبار ({$quiz['title']})",
        'quiz', $quizId, 'danger'
    );

    die('تعذر حفظ الإجابات — لم يُسجَّل أي شيء');
}

/* ==================== التسجيل ==================== */
Logger::log(
    'quizzes',
    'submit_quiz',
    "تسليم اختبار ({$quiz['title']}) - مقرر ({$quiz['course_name']})"
    . " - المحاولة رقم ($attemptNumber)"
    . " - الدرجة ($score / {$quiz['total_marks']})",
    'quiz',
    $quizId,
    'info'
);

include '../../app/views/layouts/header.php';
?>
<div class="main-content">
<div class="container">

<div class="row">

<div class="col-md-8 offset-md-2 mt-5">

<div class="card">

<div class="card-header bg-success text-white">
    تم تسليم الاختبار بنجاح
</div>

<div class="card-body text-center">

<h2>🎉 درجتك</h2>

<h1 class="display-3"><?= e($score); ?></h1>

<p>من أصل <?= e($quiz['total_marks']); ?> درجة</p>

<a href="index.php" class="btn btn-primary">العودة إلى الاختبارات</a>

</div>

</div>

</div>

</div>

</div>
</div>
<?php include '../../app/views/layouts/footer.php'; ?>