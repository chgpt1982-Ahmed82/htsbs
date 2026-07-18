<?php
/*
=====================================================================
admin/students/update.php — تعديل طالب
=====================================================================
*/

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../core/Auth.php';
require_once '../../core/Logger.php';
require_once '../../app/models/Student.php';

/* ==================== الصلاحية: أدمن فقط ==================== */
if (!Auth::check() || (int)($_SESSION['role_id'] ?? 0) !== 1) {
    die('Access Denied');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/admin/students/index.php");
    exit;
}

/* ==================== التحقق من المدخلات ==================== */
$id           = (int)($_GET['id'] ?? 0);
$fullName     = trim((string)($_POST['full_name'] ?? ''));
$email        = trim((string)($_POST['email'] ?? ''));
$stuNumber    = trim((string)($_POST['student_number'] ?? ''));
$departmentId = (int)($_POST['department_id'] ?? 0);
$classId      = (int)($_POST['class_id'] ?? 0);

if ($id <= 0) {
    die('Student ID Not Found');
}

if ($fullName === '') {
    die('اسم الطالب مطلوب');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die('البريد الإلكتروني غير صالح');
}

if ($departmentId <= 0 || $classId <= 0) {
    die('يرجى اختيار القسم والصف');
}

$model = new Student();

/* البيانات القديمة — لتوثيق التغيير في السجل */
$old = $model->getById($id);

if (!$old) {
    die('Student Not Found');
}

$oldName  = trim((string)($old['full_name'] ?? ''));
$oldClass = (int)($old['class_id'] ?? 0);

/* ==================== التحديث ==================== */
try {

    $model->update($id, $_POST);

    /* بناء وصف السجل: نوثّق تغيّر الاسم أو نقل الطالب لصف آخر */
    $label = ($oldName !== '' && $oldName !== $fullName)
        ? "$oldName ← $fullName"
        : $fullName;

    $label .= ($stuNumber !== '' ? " ($stuNumber)" : '');

    if ($oldClass > 0 && $oldClass !== $classId) {

        /* نقل الطالب بين الصفوف — حدث مهم يستحق التوثيق */
        $database = new Database();
        $db = $database->connect();

        $stmt = $db->prepare("
            SELECT
                (SELECT class_name FROM classes WHERE id = ?) AS old_class,
                (SELECT class_name FROM classes WHERE id = ?) AS new_class
        ");
        $stmt->execute([$oldClass, $classId]);
        $cls = $stmt->fetch(PDO::FETCH_ASSOC);

        $label .= ' | نقل الصف: '
                . ($cls['old_class'] ?? '؟')
                . ' ← '
                . ($cls['new_class'] ?? '؟');
    }

    Logger::updated('students', $label, $id);

    header("Location: " . BASE_URL . "/admin/students/index.php");
    exit;

} catch (PDOException $ex) {

    if ($ex->getCode() === '23000') {
        die('تعذر التعديل: البريد الإلكتروني أو الرقم الأكاديمي مسجّل لطالب آخر');
    }

    throw $ex;
}