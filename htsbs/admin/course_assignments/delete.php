<?php
/*
=====================================================================
admin/course_assignments/delete.php — حذف إسناد
⚠️ الأسماء تُقرأ قبل الحذف — بعده تضيع نهائياً
=====================================================================
*/

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../core/Auth.php';
require_once '../../core/Logger.php';
require_once '../../app/models/CourseAssignment.php';

/* ==================== الصلاحية: أدمن فقط ==================== */
if (!Auth::check() || (int)($_SESSION['role_id'] ?? 0) !== 1) {
    die('Access Denied');
}

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die('Assignment ID Not Found');
}

$database = new Database();
$db = $database->connect();

/*
✅ نقرأ الأسماء قبل الحذف
(بعد الحذف لا يبقى شيء نقرأ منه — والسجل بلا أسماء عديم القيمة)
*/
$stmt = $db->prepare("
    SELECT u.full_name AS teacher_name,
           c.course_name,
           cl.class_name,
           ca.semester,
           ca.academic_year
    FROM course_assignments ca
    INNER JOIN teachers t ON ca.teacher_id = t.id
    INNER JOIN users u    ON t.user_id = u.id
    INNER JOIN courses c  ON ca.course_id = c.id
    INNER JOIN classes cl ON ca.class_id = cl.id
    WHERE ca.id = ?
");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    die('Assignment Not Found');
}

$label = 'إسناد: ' . $row['teacher_name']
       . ' ← ' . $row['course_name']
       . ' ← ' . $row['class_name']
       . (!empty($row['semester']) ? ' | ' . $row['semester'] : '')
       . (!empty($row['academic_year']) ? ' | ' . $row['academic_year'] : '');

/* ==================== الحذف ==================== */
$model = new CourseAssignment();

$result = $model->delete($id);

if ($result) {

    /* خطورة "خطر" تلقائياً — يظهر الصف أحمر في لوحة السجلات */
    Logger::deleted('course_assignments', $label, $id);

    header("Location: " . BASE_URL . "/admin/course_assignments/index.php");
    exit;

} else {
    die('Delete Failed');
}