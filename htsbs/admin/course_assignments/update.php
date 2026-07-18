<?php
/*
=====================================================================
admin/course_assignments/update.php — تعديل إسناد
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/admin/course_assignments/index.php");
    exit;
}

/* ==================== التحقق من المدخلات ==================== */
$id        = (int)($_GET['id'] ?? 0);
$teacherId = (int)($_POST['teacher_id'] ?? 0);
$courseId  = (int)($_POST['course_id'] ?? 0);
$classId   = (int)($_POST['class_id'] ?? 0);
$semester  = trim((string)($_POST['semester'] ?? ''));
$year      = trim((string)($_POST['academic_year'] ?? ''));

if ($id <= 0) {
    die('Assignment ID Not Found');
}

if ($teacherId <= 0 || $courseId <= 0 || $classId <= 0) {
    die('يرجى اختيار المعلم والمقرر والصف');
}

$model = new CourseAssignment();

/* التأكد أن الإسناد موجود */
if (!$model->getById($id)) {
    die('Assignment Not Found');
}

$database = new Database();
$db = $database->connect();

/* جلب الأسماء الجديدة للسجل */
$stmt = $db->prepare("
    SELECT
        (SELECT u.full_name FROM teachers t
          INNER JOIN users u ON t.user_id = u.id
          WHERE t.id = ?)                         AS teacher_name,
        (SELECT course_name FROM courses WHERE id = ?) AS course_name,
        (SELECT class_name  FROM classes WHERE id = ?) AS class_name
");
$stmt->execute([$teacherId, $courseId, $classId]);
$info = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$info || !$info['teacher_name'] || !$info['course_name'] || !$info['class_name']) {
    die('المعلم أو المقرر أو الصف المحدد غير موجود');
}

/* ==================== التحديث ==================== */
try {

    $model->update($id, $_POST);

    Logger::updated(
        'course_assignments',
        'إسناد: ' . $info['teacher_name']
        . ' ← ' . $info['course_name']
        . ' ← ' . $info['class_name']
        . ($semester !== '' ? " | $semester" : '')
        . ($year !== '' ? " | $year" : ''),
        $id
    );

    header("Location: " . BASE_URL . "/admin/course_assignments/index.php");
    exit;

} catch (PDOException $ex) {

    if ($ex->getCode() === '23000') {
        die('تعذر التعديل: هذا الإسناد موجود مسبقاً، أو أحد الحقول المحددة غير موجود');
    }

    throw $ex;
}