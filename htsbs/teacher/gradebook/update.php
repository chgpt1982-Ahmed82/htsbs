<?php
/*
=====================================================================
teacher/gradebook/update.php — تعديل درجة
⚠️ الدرجة القديمة تُقرأ قبل التحديث — بعده تضيع نهائياً
=====================================================================
*/

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../core/Auth.php';
require_once '../../core/Logger.php';
require_once '../../app/models/Notification.php';

/* ==================== الصلاحية: معلم فقط ==================== */
if (!Auth::check() || (int)($_SESSION['role_id'] ?? 0) !== 2) {
    die('Access Denied');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/teacher/gradebook/index.php");
    exit;
}

$db = (new Database())->connect();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$notification = new Notification();

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die('Grade ID Not Found');
}

/* سجل المعلم */
$stmt = $db->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->execute([(int)$_SESSION['user_id']]);
$teacherId = (int)$stmt->fetchColumn();

if ($teacherId <= 0) {
    die('Teacher Not Found');
}

/*
====================================================================
✅ نقرأ الدرجة القديمة قبل التحديث
(مع التأكد أنها من رصد هذا المعلم — حماية من التلاعب بالمعرّف)
====================================================================
*/
$stmt = $db->prepare("
    SELECT g.*, u.full_name, s.user_id AS student_user_id, c.course_name
    FROM gradebook g
    INNER JOIN students s ON g.student_id = s.id
    INNER JOIN users u    ON s.user_id = u.id
    INNER JOIN courses c  ON g.course_id = c.id
    WHERE g.id = ? AND g.teacher_id = ?
");
$stmt->execute([$id, $teacherId]);
$old = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$old) {

    Logger::log(
        'gradebook',
        'update_denied',
        "محاولة تعديل درجة لا يملكها المعلم (grade_id=$id)",
        null,
        null,
        'danger'
    );

    die('الدرجة غير موجودة أو لا تملك صلاحية تعديلها');
}

/* ==================== التحقق من المدخلات ==================== */
$assessmentType = trim((string)($_POST['assessment_type'] ?? ''));
$title          = trim((string)($_POST['title'] ?? ''));
$score          = (float)($_POST['score'] ?? -1);
$maxScore       = (float)($_POST['max_score'] ?? 0);
$weight         = (float)($_POST['weight'] ?? 0);

$allowedTypes = ['Quiz', 'Assignment', 'Activity', 'Midterm', 'Final', 'Participation'];

if (!in_array($assessmentType, $allowedTypes, true)) {
    die('نوع التقييم غير صالح');
}

if ($title === '') {
    die('عنوان التقييم مطلوب');
}

if ($maxScore <= 0) {
    die('الدرجة العظمى يجب أن تكون أكبر من صفر');
}

if ($score < 0 || $score > $maxScore) {
    die("الدرجة يجب أن تكون بين 0 و $maxScore");
}

$oldScore = (float)$old['score'];
$oldMax   = (float)$old['max_score'];

/* ==================== التحديث ==================== */
try {

    $stmt = $db->prepare("
        UPDATE gradebook
        SET assessment_type = ?, title = ?, score = ?, max_score = ?, weight = ?
        WHERE id = ? AND teacher_id = ?
    ");
    $stmt->execute([
        $assessmentType, $title, $score, $maxScore, $weight,
        $id, $teacherId
    ]);

} catch (Throwable $ex) {

    Logger::log(
        'gradebook',
        'update_failed',
        "فشل تعديل درجة (grade_id=$id)",
        null, null, 'danger'
    );

    die('تعذر حفظ التعديل');
}

/*
====================================================================
التسجيل: نوثّق الدرجة القديمة والجديدة معاً
هذا هو جوهر سجل الدرجات — بلا القيمة القديمة لا قيمة للسجل
====================================================================
*/
$scoreChanged = (abs($oldScore - $score) > 0.001) || (abs($oldMax - $maxScore) > 0.001);

Logger::log(
    'gradebook',
    'update_grade',
    "تعديل درجة {$old['full_name']} في مقرر ({$old['course_name']}) - التقييم ($title): "
    . ($scoreChanged
        ? "$oldScore/$oldMax ← $score/$maxScore"
        : "بلا تغيير في الدرجة ($score/$maxScore)"),
    'student',
    (int)$old['student_id'],
    'warning'
);

/* ==================== إشعار الطالب وولي الأمر ==================== */
if ($scoreChanged) {

    $notification->create(
        (int)$old['student_user_id'],
        'تعديل درجة',
        "تم تعديل درجتك في ($title) - مقرر {$old['course_name']}: "
        . "من $oldScore/$oldMax إلى $score/$maxScore",
        'grade'
    );

    $parentStmt = $db->prepare("
        SELECT p.user_id
        FROM parent_student ps
        INNER JOIN parents p ON ps.parent_id = p.id
        WHERE ps.student_id = ?
    ");
    $parentStmt->execute([(int)$old['student_id']]);

    foreach ($parentStmt->fetchAll(PDO::FETCH_ASSOC) as $parent) {

        $notification->create(
            (int)$parent['user_id'],
            'تعديل درجة الطالب',
            "{$old['full_name']} - تعديل درجة ($title) في مقرر {$old['course_name']}: "
            . "من $oldScore/$oldMax إلى $score/$maxScore",
            'grade'
        );
    }
}

header("Location: report.php?course_id=" . (int)$old['course_id']);
exit;