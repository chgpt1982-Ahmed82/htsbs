<?php
/*
=====================================================================
teacher/quizzes/store_question.php — إضافة سؤال لاختبار
=====================================================================
🔴 ثغرة خطيرة: النسخة السابقة لم تتحقق أن الاختبار يملكه المعلم
   أي معلم يضيف أسئلة لاختبار معلم آخر بتغيير quiz_id
⚠️ خطأ محتمل: النموذج يرسل 'question' لكن العمود اسمه 'question_text'

التعديلات:
  1. تسجيل الإضافة
  2. التحقق من ملكية الاختبار
  3. التحقق من صحة نوع السؤال والإجابة الصحيحة
  4. توحيد اسم الحقل (question / question_text)
=====================================================================
*/

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../core/Auth.php';
require_once '../../core/Logger.php';

/* ==================== الصلاحية: معلم فقط ==================== */
if (!Auth::check() || (int)($_SESSION['role_id'] ?? 0) !== 2) {
    die('Access Denied');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$db = (new Database())->connect();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $db->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->execute([(int)$_SESSION['user_id']]);
$teacherId = (int)$stmt->fetchColumn();

if ($teacherId <= 0) {
    die('Teacher Not Found');
}

/* ==================== المدخلات ==================== */
$quizId = (int)($_POST['quiz_id'] ?? 0);

/* النموذج قد يرسل question أو question_text — نقبل الاثنين */
$questionText = trim((string)($_POST['question_text'] ?? $_POST['question'] ?? ''));
$questionType = trim((string)($_POST['question_type'] ?? ''));
$marks        = (float)($_POST['marks'] ?? 1);

$optionA      = trim((string)($_POST['option_a'] ?? ''));
$optionB      = trim((string)($_POST['option_b'] ?? ''));
$optionC      = trim((string)($_POST['option_c'] ?? ''));
$optionD      = trim((string)($_POST['option_d'] ?? ''));
$correct      = strtoupper(trim((string)($_POST['correct_answer'] ?? '')));

if ($quizId <= 0 || $questionText === '') {
    die('بيانات السؤال ناقصة');
}

/* نوع السؤال — مطابق لـ ENUM */
$allowedTypes = ['multiple_choice', 'true_false', 'short_answer'];

if (!in_array($questionType, $allowedTypes, true)) {
    die('نوع السؤال غير صالح');
}

if ($marks <= 0 || $marks > 100) {
    die('درجة السؤال يجب أن تكون بين 0 و 100');
}

/*
====================================================================
🔴 حماية جوهرية: الاختبار يجب أن يكون من إنشاء هذا المعلم
النسخة السابقة كانت تُدرج السؤال مباشرة بلا أي تحقق
====================================================================
*/
$stmt = $db->prepare("
    SELECT q.id, q.title
    FROM quizzes q
    WHERE q.id = ? AND q.teacher_id = ?
");
$stmt->execute([$quizId, $teacherId]);
$quiz = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$quiz) {

    Logger::log(
        'quizzes',
        'add_question_denied',
        "محاولة إضافة سؤال لاختبار لا يملكه المعلم (quiz_id=$quizId)",
        null, null, 'danger'
    );

    die('غير مصرح لك بإضافة أسئلة لهذا الاختبار');
}

/*
====================================================================
التحقق حسب نوع السؤال
====================================================================
*/
if ($questionType === 'multiple_choice') {

    if ($optionA === '' || $optionB === '') {
        die('الاختيار من متعدد يتطلب خيارين على الأقل');
    }

    if (!in_array($correct, ['A', 'B', 'C', 'D'], true)) {
        die('يرجى تحديد الإجابة الصحيحة (A/B/C/D)');
    }

} elseif ($questionType === 'true_false') {

    /* نخزّن صح/خطأ في العمودين A/B و correct = A أو B */
    $optionA = $optionA !== '' ? $optionA : 'صح';
    $optionB = $optionB !== '' ? $optionB : 'خطأ';
    $optionC = null;
    $optionD = null;

    if (!in_array($correct, ['A', 'B'], true)) {
        die('يرجى تحديد الإجابة الصحيحة (صح=A / خطأ=B)');
    }

} else {
    /* short_answer — لا خيارات ولا إجابة صحيحة تلقائية */
    $optionA = $optionB = $optionC = $optionD = null;
    $correct = null;
}

/* ==================== الإدراج ==================== */
try {

    $stmt = $db->prepare("
        INSERT INTO quiz_questions
            (quiz_id, question_text, question_type, marks,
             option_a, option_b, option_c, option_d, correct_answer)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $quizId, $questionText, $questionType, $marks,
        $optionA ?: null, $optionB ?: null,
        $optionC ?: null, $optionD ?: null,
        $correct ?: null,
    ]);

    $questionId = (int)$db->lastInsertId();

} catch (Throwable $ex) {

    Logger::log(
        'quizzes',
        'add_question_failed',
        "فشل إضافة سؤال للاختبار ({$quiz['title']})",
        'quiz', $quizId, 'danger'
    );

    die('تعذر حفظ السؤال');
}

/*
====================================================================
التسجيل
⚠️ لا نسجّل نص السؤال أو الإجابة الصحيحة كاملاً في التفاصيل
(السجل يُقرأ ويُصدَّر — تسريب الإجابات مخاطرة)
نكتفي بالنوع والدرجة
====================================================================
*/
$typeLabels = [
    'multiple_choice' => 'اختيار من متعدد',
    'true_false'      => 'صح أو خطأ',
    'short_answer'    => 'إجابة قصيرة',
];

Logger::log(
    'quizzes',
    'add_question',
    "إضافة سؤال للاختبار ({$quiz['title']})"
    . " - النوع (" . ($typeLabels[$questionType] ?? $questionType) . ")"
    . " - الدرجة ($marks)",
    'quiz',
    $quizId,
    'info'
);

header('Location: questions.php?id=' . $quizId);
exit;