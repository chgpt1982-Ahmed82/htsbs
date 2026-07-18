<?php
/*
=====================================================================
admin/departments/update.php — تعديل قسم
=====================================================================
*/

session_start();

require_once '../../config/config.php';
require_once '../../core/Auth.php';
require_once '../../core/Logger.php';
require_once '../../app/models/Department.php';

/* ==================== الصلاحية: أدمن فقط ==================== */
if (!Auth::check() || (int)($_SESSION['role_id'] ?? 0) !== 1) {
    die('Access Denied');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/admin/departments/index.php");
    exit;
}

/* ==================== التحقق من المدخلات ==================== */
$id             = (int)($_GET['id'] ?? 0);
$departmentName = trim((string)($_POST['department_name'] ?? ''));

if ($id <= 0) {
    die('Department ID Not Found');
}

if ($departmentName === '') {
    die('اسم القسم مطلوب');
}

$department = new Department();

/* الاسم القديم — لتوثيق التغيير في السجل */
$old = $department->getById($id);

if (!$old) {
    die('Department Not Found');
}

$oldName = trim((string)($old['department_name'] ?? ''));

/* ==================== التحديث ==================== */
try {

    $result = $department->update($id, $_POST);

    if (!$result) {
        die('Update Failed');
    }

    /* نوثّق تغيّر الاسم (القديم ← الجديد) */
    $label = ($oldName !== '' && $oldName !== $departmentName)
        ? "$oldName ← $departmentName"
        : $departmentName;

    Logger::updated('departments', $label, $id);

    header("Location: " . BASE_URL . "/admin/departments/index.php");
    exit;

} catch (PDOException $ex) {

    if ($ex->getCode() === '23000') {
        die('تعذر التعديل: اسم القسم مكرر');
    }

    throw $ex;
}