<?php
/*
=====================================================================
admin/classes/store.php — حفظ صف جديد
=====================================================================
*/

session_start();

require_once '../../config/config.php';
require_once '../../core/Auth.php';
require_once '../../core/Logger.php';
require_once '../../app/models/ClassModel.php';

/* ==================== الصلاحية: أدمن فقط ==================== */
if (!Auth::check() || (int)($_SESSION['role_id'] ?? 0) !== 1) {
    die('Access Denied');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/admin/classes/index.php");
    exit;
}

/* ==================== التحقق من المدخلات ==================== */
$className = trim((string)($_POST['class_name'] ?? ''));
$year      = trim((string)($_POST['academic_year'] ?? ''));
$semester  = trim((string)($_POST['semester'] ?? ''));

if ($className === '') {
    die('اسم الصف مطلوب');
}

if ($year === '') {
    die('العام الدراسي مطلوب');
}

/* ==================== الحفظ ==================== */
$class = new ClassModel();

try {

    $class->create($_POST);

    /* التسجيل بعد نجاح الحفظ فقط */
    Logger::created(
        'classes',
        $className
        . ($year !== '' ? " | $year" : '')
        . ($semester !== '' ? " | $semester" : '')
    );

    header("Location: " . BASE_URL . "/admin/classes/index.php");
    exit;

} catch (PDOException $ex) {

    /* 23000 = قيمة مكررة أو انتهاك قيد */
    if ($ex->getCode() === '23000') {
        die('تعذر الحفظ: اسم الصف مكرر');
    }

    throw $ex;
}