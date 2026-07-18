<?php
/*
=====================================================================
admin/classes/update.php — تعديل صف
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
$id        = (int)($_GET['id'] ?? 0);
$className = trim((string)($_POST['class_name'] ?? ''));
$year      = trim((string)($_POST['academic_year'] ?? ''));
$semester  = trim((string)($_POST['semester'] ?? ''));

if ($id <= 0) {
    die('Class ID Not Found');
}

if ($className === '') {
    die('اسم الصف مطلوب');
}

$class = new ClassModel();

/* الاسم القديم — لتوثيق التغيير في السجل */
$old = $class->getById($id);

if (!$old) {
    die('Class Not Found');
}

$oldName = trim((string)($old['class_name'] ?? ''));

/* ==================== التحديث ==================== */
try {

    $result = $class->update($id, $_POST);

    if (!$result) {
        die('Update Failed');
    }

    /*
    إذا تغيّر الاسم نوثّق التغيير (القديم ← الجديد)
    وإلا نكتفي بالاسم الحالي
    */
    $label = ($oldName !== '' && $oldName !== $className)
        ? "$oldName ← $className"
        : $className;

    Logger::updated(
        'classes',
        $label
        . ($year !== '' ? " | $year" : '')
        . ($semester !== '' ? " | $semester" : ''),
        $id
    );

    header("Location: " . BASE_URL . "/admin/classes/index.php");
    exit;

} catch (PDOException $ex) {

    if ($ex->getCode() === '23000') {
        die('تعذر التعديل: اسم الصف مكرر');
    }

    throw $ex;
}