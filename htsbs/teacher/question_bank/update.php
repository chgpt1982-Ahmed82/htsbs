<?php
/*
=====================================================================
teacher/question_bank/update.php — تعديل سؤال في بنك الأسئلة
⚠️ كان مفتوحاً تماماً: لا صلاحيات، لا تحقق ملكية
   UPDATE بلا teacher_id → أي معلم يعدّل أسئلة زملائه بتغيير id
=====================================================================
*/

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../core/Auth.php';
require_once '../../core/Logger.php';
require_once '../../app/models/QuestionBank.php';

/* ==================== الصلاحية: معلم فقط ==================== */
if (!Auth::check() || (int)($_SESSION['role_id'] ?? 0) !== 2) {
    die('Access Denied');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$db = (new Database())->connect();

$stmt = $db->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->execute([(int)$_SESSION['user_id']]);
$teacherId = (int)$stmt->fetchColumn();

if ($teacherId <= 0) {
    die('Teacher Not Found');
}

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    die('Question ID Missing');
}

/* ==================== التحقق من المدخلات ==================== */
$courseId     = (int)($_POST['course_id'] ?? 0);
$category     = trim((string)($_POST['category'] ?? ''));
$questionText = trim((string)($_POST['question_text'] ?? ''));
$optionA      = trim((string)($_POST['option_a'] ?? ''));
$optionB      = trim((string)($_POST['option_b'] ?? ''));
$optionC      = trim((string)($_POST['option_c'] ?? ''));
$optionD      = trim((string)($_POST['option_d'] ?? ''));
$correct      = strtoupper(trim((string)($_POST['correct_answer'] ?? '')));
$marks        = (int)($_POST['marks'] ?? 1);

if ($questionText === '' || $optionA === '' || $optionB === '') {
    die('نص السؤال والخياران الأول والثاني مطلوبة');
}

if (!in_array($correct, ['A', 'B', 'C', 'D'], true)) {
    die('يرجى تحديد الإجابة الصحيحة (A/B/C/D)');
}

if ($marks < 1 || $marks > 100) {
    die('درجة السؤال يجب أن تكون بين 1 و 100');
}

/*
====================================================================
✅ السؤال القديم — يُقرأ قبل التعديل، مع التأكد من الملكية
====================================================================
*/
$stmt = $db->prepare("
    SELECT qb.*, c.course_name
    FROM question_bank qb
    LEFT JOIN courses c ON qb.course_id = c.id
    WHERE qb.id = ? AND qb.teacher_id = ?
");
$stmt->execute([$id, $teacherId]);
$old = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$old) {

    Logger::log(
        'question_bank',
        'update_denied',
        "محاولة تعديل سؤال لا يملكه المعلم (question_id=$id)",
        null, null, 'danger'
    );

    die('السؤال غير موجود أو لا تملك صلاحية تعديله');
}

/* إن غُيّر المقرر، تأكد أنه مسند للمعلم أيضاً */
if ($courseId > 0 && (int)$old['course_id'] !== $courseId) {

    $stmt = $db->prepare("
        SELECT COUNT(*) FROM course_assignments
        WHERE teacher_id = ? AND course_id = ?
    ");
    $stmt->execute([$teacherId, $courseId]);

    if ((int)$stmt->fetchColumn() === 0) {
        die('المقرر المحدد غير مسند إليك');
    }
} else {
    $courseId = (int)$old['course_id'];
}

$oldCorrect = (string)($old['correct_answer'] ?? '');

/* ==================== التحديث ==================== */
try {

    $questionBank = new QuestionBank();

    $questionBank->update($id, [
        'course_id'      => $courseId,
        'category'       => $category,
        'question_text'  => $questionText,
        'option_a'       => $optionA,
        'option_b'       => $optionB,
        'option_c'       => $optionC !== '' ? $optionC : null,
        'option_d'       => $optionD !== '' ? $optionD : null,
        'correct_answer' => $correct,
        'marks'          => $marks,
    ]);

} catch (Throwable $ex) {

    Logger::log(
        'question_bank',
        'update_failed',
        "فشل تعديل سؤال (question_id=$id)",
        null, null, 'danger'
    );

    die('تعذر حفظ التعديل');
}

/*
====================================================================
التسجيل
تغيير الإجابة الصحيحة حدث مهم (قد يعني تصحيح خطأ أو تلاعباً)
لكن لا نكشف قيمة الإجابة في التفاصيل — نكتفي بالإشارة للتغيير
====================================================================
*/
$answerChanged = ($oldCorrect !== $correct);

Logger::log(
    'question_bank',
    'update_question',
    "تعديل سؤال في بنك الأسئلة (id=$id) - مقرر ({$old['course_name']})"
    . " - التصنيف ($category)"
    . ($answerChanged ? ' | ⚠️ تم تغيير الإجابة الصحيحة' : ''),
    'course',
    $courseId,
    'warning'
);

header('Location: index.php?updated=1');
exit;