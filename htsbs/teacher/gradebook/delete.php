<?php
/*
=====================================================================
teacher/gradebook/delete.php — حذف درجة
⚠️ البيانات تُقرأ قبل الحذف — بعده تضيع نهائياً
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

$db = (new Database())->connect();

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

/* ✅ نقرأ البيانات قبل الحذف */
$stmt = $db->prepare("
    SELECT g.*, u.full_name, s.user_id AS student_user_id, c.course_name
    FROM gradebook g
    INNER JOIN students s ON g.student_id = s.id
    INNER JOIN users u    ON s.user_id = u.id
    INNER JOIN courses c  ON g.course_id = c.id
    WHERE g.id = ? AND g.teacher_id = ?
");
$stmt->execute([$id, $teacherId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {

    Logger::log(
        'gradebook',
        'delete_denied',
        "محاولة حذف درجة لا يملكها المعلم (grade_id=$id)",
        null, null, 'danger'
    );

    die('الدرجة غير موجودة أو لا تملك صلاحية حذفها');
}

$courseId = (int)$row['course_id'];

/* ==================== الحذف ==================== */
$stmt = $db->prepare("DELETE FROM gradebook WHERE id = ? AND teacher_id = ?");
$stmt->execute([$id, $teacherId]);

if ($stmt->rowCount() > 0) {

    /* خطورة "خطر" — صف أحمر في لوحة السجلات */
    Logger::log(
        'gradebook',
        'delete_grade',
        "حذف درجة {$row['full_name']} في مقرر ({$row['course_name']}) - "
        . "التقييم ({$row['title']}): {$row['score']}/{$row['max_score']}",
        'student',
        (int)$row['student_id'],
        'danger'
    );

    /* إشعار الطالب */
    $notification = new Notification();

    $notification->create(
        (int)$row['student_user_id'],
        'حذف درجة',
        "تم حذف درجتك في ({$row['title']}) - مقرر {$row['course_name']}",
        'grade'
    );

    header("Location: report.php?course_id=" . $courseId);
    exit;

} else {
    die('Delete Failed');
}