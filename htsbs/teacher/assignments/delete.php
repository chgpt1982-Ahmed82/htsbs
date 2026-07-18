<?php
/*
=====================================================================
teacher/assignments/delete.php — حذف واجب
⚠️ حذف الواجب يمحو معه كل تسليمات الطلاب ودرجاتهم
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

/*
====================================================================
✅ نقرأ بيانات الواجب قبل الحذف
مع عدد التسليمات — لتوثيق حجم الفقد
====================================================================
*/
$stmt = $db->prepare("
    SELECT a.*, c.course_name,
           (SELECT COUNT(*) FROM assignment_submissions s
             WHERE s.assignment_id = a.id) AS submissions,
           (SELECT COUNT(*) FROM assignment_submissions s
             WHERE s.assignment_id = a.id AND s.score IS NOT NULL) AS graded
    FROM assignments a
    INNER JOIN courses c ON a.course_id = c.id
    WHERE a.id = ? AND a.teacher_id = ?
");
$stmt->execute([$assignmentId, $teacherId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {

    Logger::log(
        'assignments',
        'delete_denied',
        "محاولة حذف واجب لا يملكه المعلم (assignment_id=$assignmentId)",
        null, null, 'danger'
    );

    die('الواجب غير موجود أو لا تملك صلاحية حذفه');
}

$title       = (string)$row['title'];
$filePath    = (string)($row['file_path'] ?? '');
$submissions = (int)$row['submissions'];
$graded      = (int)$row['graded'];

/*
==================== حماية: تسليمات مصححة ====================
*/
if ($graded > 0 && !isset($_GET['confirm'])) {

    Logger::log(
        'assignments',
        'delete_blocked',
        "محاولة حذف واجب ($title) وبه $graded تسليماً مصححاً — رُفضت",
        'assignment', $assignmentId, 'warning'
    );

    die(
        "⛔ لا يمكن حذف الواجب: يحتوي على <strong>$graded تسليماً مصححاً</strong>"
        . " من أصل $submissions — حذفه يمحو درجات الطلاب نهائياً.<br><br>"
        . "<a href='delete.php?id=$assignmentId&confirm=1' "
        . "onclick=\"return confirm('سيتم محو $graded درجة نهائياً. متأكد؟');\">"
        . "تأكيد الحذف</a> — "
        . "<a href='index.php'>رجوع</a>"
    );
}

/* ==================== الحذف داخل Transaction ==================== */
try {

    $db->beginTransaction();

    /* التعيينات أولاً */
    $stmt = $db->prepare("DELETE FROM assignment_assignments WHERE assignment_id = ?");
    $stmt->execute([$assignmentId]);

    /* الواجب — مع teacher_id للأمان */
    $stmt = $db->prepare("DELETE FROM assignments WHERE id = ? AND teacher_id = ?");
    $stmt->execute([$assignmentId, $teacherId]);

    $db->commit();

} catch (Throwable $ex) {

    if ($db->inTransaction()) {
        $db->rollBack();
    }

    Logger::log(
        'assignments',
        'delete_failed',
        "فشل حذف واجب (assignment_id=$assignmentId)",
        null, null, 'danger'
    );

    die('تعذر حذف الواجب');
}

/* حذف الملف من القرص */
$fileDeleted = false;

if ($filePath !== '') {

    $fullPath = '../../' . $filePath;

    if (strpos($filePath, 'uploads/assignments/') === 0 && is_file($fullPath)) {
        $fileDeleted = @unlink($fullPath);
    }
}

/* ==================== التسجيل ==================== */
Logger::log(
    'assignments',
    'delete_assignment',
    "حذف واجب ($title) - مقرر ({$row['course_name']})"
    . " | فُقد معه: $submissions تسليماً ($graded مصححاً)"
    . ($filePath !== ''
        ? ' | الملف: ' . ($fileDeleted ? 'حُذف' : 'تعذر حذفه')
        : ''),
    'assignment',
    $assignmentId,
    'danger'
);

$_SESSION['success'] = 'تم حذف الواجب بنجاح';

header('Location: ' . BASE_URL . '/teacher/assignments/index.php');
exit;