<?php
/*
=====================================================================
teacher/quizzes/store.php — إنشاء اختبار قصير جديد
=====================================================================
🔴 Quiz::getTeacherId() قد تكون private (مثل Activity) — نتحقق باستعلام مباشر
التعديلات: تسجيل + حماية + التأكد أن المقرر مسند للمعلم + التحقق من التواريخ
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
    header('Location: create.php');
    exit;
}

$db = (new Database())->connect();

/* المعلم من الجلسة (استعلام مباشر — أضمن) */
$stmt = $db->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->execute([(int)$_SESSION['user_id']]);
$teacherId = (int)$stmt->fetchColumn();

if ($teacherId <= 0) {
    die('Teacher Not Found');
}

/* ==================== التحقق من المدخلات ==================== */
$courseId        = (int)($_POST['course_id'] ?? 0);
$title           = trim((string)($_POST['title'] ?? ''));
$duration        = (int)($_POST['duration_minutes'] ?? 30);
$totalMarks      = (float)($_POST['total_marks'] ?? 100);
$startDate       = !empty($_POST['start_date']) ? (string)$_POST['start_date'] : null;
$endDate         = !empty($_POST['end_date']) ? (string)$_POST['end_date'] : null;
$attemptsAllowed = (int)($_POST['attempts_allowed'] ?? 1);
$isPublished     = isset($_POST['is_published']) ? 1 : 0;

if ($title === '') {
    die('عنوان الاختبار مطلوب');
}

if ($courseId <= 0) {
    die('يرجى اختيار المقرر');
}

if ($duration < 1 || $duration > 600) {
    die('المدة يجب أن تكون بين 1 و 600 دقيقة');
}

if ($attemptsAllowed < 1 || $attemptsAllowed > 20) {
    die('عدد المحاولات يجب أن يكون بين 1 و 20');
}

/* التواريخ: النهاية بعد البداية */
if ($startDate && $endDate && strtotime($endDate) <= strtotime($startDate)) {
    die('تاريخ النهاية يجب أن يكون بعد تاريخ البداية');
}

/* ==================== حماية: المقرر مسند للمعلم؟ ==================== */
$stmt = $db->prepare("
    SELECT COUNT(*) FROM course_assignments
    WHERE teacher_id = ? AND course_id = ?
");
$stmt->execute([$teacherId, $courseId]);

if ((int)$stmt->fetchColumn() === 0) {

    Logger::log(
        'quizzes',
        'store_denied',
        "محاولة إنشاء اختبار في مقرر غير مسند للمعلم (course_id=$courseId)",
        'course', $courseId, 'danger'
    );

    die('غير مصرح لك بإنشاء اختبار في هذا المقرر');
}

$stmt = $db->prepare("SELECT course_name FROM courses WHERE id = ?");
$stmt->execute([$courseId]);
$courseName = (string)$stmt->fetchColumn();

/* ==================== الحفظ ==================== */
$model = new Quiz();

try {

    $quizId = (int)$model->create([
        'teacher_id'       => $teacherId,
        'course_id'        => $courseId,
        'title'            => $title,
        'duration_minutes' => $duration,
        'total_marks'      => $totalMarks,
        'start_date'       => $startDate,
        'end_date'         => $endDate,
        'attempts_allowed' => $attemptsAllowed,
        'is_published'     => $isPublished,
    ]);

} catch (Throwable $ex) {
    $quizId = 0;
}

if ($quizId <= 0) {

    Logger::log(
        'quizzes',
        'store_failed',
        "فشل إنشاء اختبار ($title) - مقرر ($courseName)",
        'course', $courseId, 'danger'
    );

    die('تعذر إنشاء الاختبار');
}

/* ==================== التسجيل ==================== */
Logger::log(
    'quizzes',
    'create_quiz',
    "إنشاء اختبار ($title) - مقرر ($courseName)"
    . " - المدة ($duration دقيقة)"
    . " - الدرجة ($totalMarks)"
    . " - المحاولات ($attemptsAllowed)"
    . ' - الحالة: ' . ($isPublished ? 'منشور' : 'مسودة'),
    'quiz',
    $quizId,
    'info'
);

header('Location: questions.php?id=' . $quizId);
exit;