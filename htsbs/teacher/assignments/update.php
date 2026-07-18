<?php
/*
=====================================================================
teacher/assignments/update.php — تعديل واجب
🔴 كان بلا تحقق من ملكية الواجب:
   UPDATE assignments SET ... WHERE id = ?  (بلا teacher_id!)
   أي معلم يعدّل واجب أي معلم آخر بتغيير ?id=
=====================================================================
*/

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../core/Auth.php';
require_once '../../core/Logger.php';

/* ==================== الصلاحية: معلم فقط ==================== */
if (!Auth::check() || (int)($_SESSION['role_id'] ?? 0) !== 2) {
    exit('Unauthorized Access');
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

$assignmentId = (int)($_GET['id'] ?? 0);

if ($assignmentId <= 0) {
    die('Assignment ID Not Found');
}

/* ==================== التحقق من المدخلات ==================== */
$courseId    = (int)($_POST['course_id'] ?? 0);
$title       = trim((string)($_POST['title'] ?? ''));
$description = trim((string)($_POST['description'] ?? ''));
$dueDate     = !empty($_POST['due_date']) ? (string)$_POST['due_date'] : null;

if ($title === '') {
    die('عنوان الواجب مطلوب');
}

if ($courseId <= 0) {
    die('يرجى اختيار المقرر');
}

/*
====================================================================
✅ الواجب القديم — يُقرأ قبل التعديل
مع التأكد أنه من إنشاء هذا المعلم (كان مفقوداً في UPDATE!)
====================================================================
*/
$stmt = $db->prepare("
    SELECT a.*, c.course_name
    FROM assignments a
    INNER JOIN courses c ON a.course_id = c.id
    WHERE a.id = ? AND a.teacher_id = ?
");
$stmt->execute([$assignmentId, $teacherId]);
$old = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$old) {

    Logger::log(
        'assignments',
        'update_denied',
        "محاولة تعديل واجب لا يملكه المعلم (assignment_id=$assignmentId)",
        null, null, 'danger'
    );

    die('الواجب غير موجود أو لا تملك صلاحية تعديله');
}

/* التأكد أن المقرر الجديد مسند للمعلم أيضاً */
$stmt = $db->prepare("
    SELECT COUNT(*) FROM course_assignments
    WHERE teacher_id = ? AND course_id = ?
");
$stmt->execute([$teacherId, $courseId]);

if ((int)$stmt->fetchColumn() === 0) {
    die('المقرر المحدد غير مسند إليك');
}

$oldTitle = (string)$old['title'];

/* ==================== التحديث (مع teacher_id للأمان) ==================== */
try {

    $stmt = $db->prepare("
        UPDATE assignments
        SET course_id = ?, title = ?, description = ?, due_date = ?
        WHERE id = ? AND teacher_id = ?
    ");
    $stmt->execute([
        $courseId, $title, $description, $dueDate,
        $assignmentId, $teacherId,
    ]);

} catch (Throwable $ex) {

    Logger::log(
        'assignments',
        'update_failed',
        "فشل تعديل واجب (assignment_id=$assignmentId)",
        null, null, 'danger'
    );

    die('تعذر حفظ التعديل');
}

/* ==================== التسجيل ==================== */
$label = ($oldTitle !== $title) ? "$oldTitle ← $title" : $title;

Logger::log(
    'assignments',
    'update_assignment',
    "تعديل واجب ($label) - مقرر ({$old['course_name']})"
    . (((string)($old['due_date'] ?? '') !== (string)($dueDate ?? ''))
        ? " | التسليم: " . ($old['due_date'] ?? '—') . " ← " . ($dueDate ?? '—')
        : ''),
    'assignment',
    $assignmentId,
    'warning'
);

$_SESSION['success'] = 'تم تعديل الواجب بنجاح';

header('Location: ' . BASE_URL . '/teacher/assignments/index.php');
exit;