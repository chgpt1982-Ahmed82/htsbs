<?php
/*
=====================================================================
teacher/question_bank/store.php — إضافة سؤال لبنك الأسئلة
=====================================================================
التعديلات:
  1. تسجيل الإضافة
  2. حماية صلاحيات + التأكد أن المقرر مسند للمعلم
  3. التحقق من صحة الإجابة الصحيحة (A/B/C/D) والدرجة
  4. teacher_id من الجلسة (getTeacherId قد تكون private)
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

/* المعلم من الجلسة (استعلام مباشر — أضمن من getTeacherId) */
$stmt = $db->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->execute([(int)$_SESSION['user_id']]);
$teacherId = (int)$stmt->fetchColumn();

if ($teacherId <= 0) {
    die('Teacher Not Found');
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

if ($courseId <= 0) {
    die('يرجى اختيار المقرر');
}

if ($category === '' || $questionText === '' || $optionA === '' || $optionB === '') {
    die('التصنيف ونص السؤال والخياران الأول والثاني مطلوبة');
}

/* الإجابة الصحيحة يجب أن تطابق خياراً موجوداً */
if (!in_array($correct, ['A', 'B', 'C', 'D'], true)) {
    die('يرجى تحديد الإجابة الصحيحة (A/B/C/D)');
}

if (($correct === 'C' && $optionC === '') || ($correct === 'D' && $optionD === '')) {
    die('الإجابة الصحيحة تشير إلى خيار فارغ');
}

if ($marks < 1 || $marks > 100) {
    die('درجة السؤال يجب أن تكون بين 1 و 100');
}

/* ==================== حماية: المقرر مسند للمعلم؟ ==================== */
$stmt = $db->prepare("
    SELECT COUNT(*) FROM course_assignments
    WHERE teacher_id = ? AND course_id = ?
");
$stmt->execute([$teacherId, $courseId]);

if ((int)$stmt->fetchColumn() === 0) {

    Logger::log(
        'question_bank',
        'store_denied',
        "محاولة إضافة سؤال في مقرر غير مسند للمعلم (course_id=$courseId)",
        'course', $courseId, 'danger'
    );

    die('غير مصرح لك بإضافة أسئلة في هذا المقرر');
}

$stmt = $db->prepare("SELECT course_name FROM courses WHERE id = ?");
$stmt->execute([$courseId]);
$courseName = (string)$stmt->fetchColumn();

/* ==================== الحفظ ==================== */
try {

    $questionBank = new QuestionBank();

    $questionBank->create([
        'teacher_id'     => $teacherId,
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
        'store_failed',
        "فشل إضافة سؤال - مقرر ($courseName)",
        'course', $courseId, 'danger'
    );

    die('تعذر حفظ السؤال');
}

/*
====================================================================
التسجيل
⚠️ لا نسجّل نص السؤال ولا الإجابة الصحيحة (السجل يُصدَّر — تسريب)
نكتفي بالتصنيف والمقرر والدرجة
====================================================================
*/
Logger::log(
    'question_bank',
    'create_question',
    "إضافة سؤال لبنك الأسئلة - مقرر ($courseName)"
    . " - التصنيف ($category)"
    . " - الدرجة ($marks)",
    'course',
    $courseId,
    'info'
);

header('Location: index.php?success=1');
exit;