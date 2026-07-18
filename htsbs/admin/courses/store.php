<?php
/*
=====================================================================
admin/courses/store.php — حفظ مقرر جديد
=====================================================================
*/

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../core/Auth.php';
require_once '../../core/Logger.php';
require_once '../../app/models/course.php';

/* ==================== الصلاحية: أدمن فقط ==================== */
if (!Auth::check() || (int)($_SESSION['role_id'] ?? 0) !== 1) {
    die('Access Denied');
}

/* ==================== طلب POST فقط ==================== */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/admin/courses/index.php");
    exit;
}

/* ==================== التحقق من المدخلات ==================== */
$courseName   = trim((string)($_POST['course_name'] ?? ''));
$courseCode   = trim((string)($_POST['course_code'] ?? ''));
$departmentId = (int)($_POST['department_id'] ?? 0);

if ($courseName === '') {
    die('اسم المقرر مطلوب');
}

if ($departmentId <= 0) {
    die('يرجى اختيار القسم');
}

/* التأكد أن القسم موجود فعلاً — يمنع خطأ Foreign Key */
$database = new Database();
$db = $database->connect();

$stmt = $db->prepare("SELECT COUNT(*) FROM departments WHERE id = ?");
$stmt->execute([$departmentId]);

if ((int)$stmt->fetchColumn() === 0) {
    die('القسم المحدد غير موجود — يرجى اختيار قسم صحيح');
}

/* ==================== الحفظ ==================== */
$courseModel = new Course();

try {

    $courseModel->create($_POST);

    /* التسجيل بعد نجاح الحفظ فقط */
    Logger::created(
        'courses',
        $courseName . ($courseCode !== '' ? " ($courseCode)" : '')
    );

    header("Location: " . BASE_URL . "/admin/courses/index.php");
    exit;

} catch (PDOException $ex) {

    /* 23000 = انتهاك قيد (مفتاح أجنبي أو قيمة مكررة) */
    if ($ex->getCode() === '23000') {
        die('تعذر الحفظ: القسم غير موجود أو رمز المقرر مكرر');
    }

    throw $ex;
}