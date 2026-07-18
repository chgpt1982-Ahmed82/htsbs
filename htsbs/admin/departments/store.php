<?php
/*
=====================================================================
admin/departments/store.php — حفظ قسم جديد
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
$departmentName = trim((string)($_POST['department_name'] ?? ''));
$description    = trim((string)($_POST['description'] ?? ''));

if ($departmentName === '') {
    die('اسم القسم مطلوب');
}

/* ==================== الحفظ ==================== */
$department = new Department();

try {

    $result = $department->create($_POST);

    if (!$result) {
        die('Failed To Save Department');
    }

    /* التسجيل بعد نجاح الحفظ فقط */
    Logger::created('departments', $departmentName);

    header("Location: " . BASE_URL . "/admin/departments/index.php");
    exit;

} catch (PDOException $ex) {

    /* 23000 = اسم مكرر */
    if ($ex->getCode() === '23000') {
        die('تعذر الحفظ: اسم القسم مكرر');
    }

    throw $ex;
}