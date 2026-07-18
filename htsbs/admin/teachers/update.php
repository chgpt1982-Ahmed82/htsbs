<?php
/*
=====================================================================
admin/teachers/update.php — تعديل معلم
=====================================================================
*/

session_start();

require_once '../../config/config.php';
require_once '../../core/Auth.php';
require_once '../../core/Logger.php';
require_once '../../app/models/Teacher.php';

/* ==================== الصلاحية: أدمن فقط ==================== */
if (!Auth::check() || (int)($_SESSION['role_id'] ?? 0) !== 1) {
    die('Access Denied');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/admin/teachers/index.php");
    exit;
}

/* ==================== التحقق من المدخلات ==================== */
$id             = (int)($_GET['id'] ?? 0);
$fullName       = trim((string)($_POST['full_name'] ?? ''));
$email          = trim((string)($_POST['email'] ?? ''));
$specialization = trim((string)($_POST['specialization'] ?? ''));
$departmentId   = (int)($_POST['department_id'] ?? 0);

if ($id <= 0) {
    die('Teacher ID Not Found');
}

if ($fullName === '') {
    die('اسم المعلم مطلوب');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die('البريد الإلكتروني غير صالح');
}

if ($departmentId <= 0) {
    die('يرجى اختيار القسم');
}

$teacher = new Teacher();

/* البيانات القديمة — لتوثيق التغيير في السجل */
$old = $teacher->getById($id);

if (!$old) {
    die('Teacher Not Found');
}

$oldName = trim((string)($old['full_name'] ?? ''));

/* ==================== التحديث ==================== */
try {

    $result = $teacher->update($id, $_POST);

    if (!$result) {
        die('Update Failed');
    }

    /* نوثّق تغيّر الاسم إن حدث */
    $label = ($oldName !== '' && $oldName !== $fullName)
        ? "$oldName ← $fullName"
        : $fullName;

    $label .= ($specialization !== '' ? " - $specialization" : '');

    Logger::updated('teachers', $label, $id);

    header("Location: " . BASE_URL . "/admin/teachers/index.php");
    exit;

} catch (PDOException $ex) {

    if ($ex->getCode() === '23000') {
        die('تعذر التعديل: البريد الإلكتروني مسجّل لمستخدم آخر');
    }

    throw $ex;
}