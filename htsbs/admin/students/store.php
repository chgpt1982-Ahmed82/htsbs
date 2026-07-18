<?php
/*
=====================================================================
admin/students/store.php — حفظ طالب جديد
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
$fullName     = trim((string)($_POST['full_name'] ?? ''));
$email        = trim((string)($_POST['email'] ?? ''));
$password     = (string)($_POST['password'] ?? '');
$stuNumber    = trim((string)($_POST['student_number'] ?? ''));
$departmentId = (int)($_POST['department_id'] ?? 0);
$classId      = (int)($_POST['class_id'] ?? 0);

if ($fullName === '') {
    die('اسم الطالب مطلوب');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die('البريد الإلكتروني غير صالح');
}

if (strlen($password) < 6) {
    die('كلمة المرور يجب ألا تقل عن 6 أحرف');
}

if ($departmentId <= 0 || $classId <= 0) {
    die('يرجى اختيار القسم والصف');
}

$database = new Database();
$db = $database->connect();

/* التأكد أن القسم والصف موجودان — يمنع خطأ Foreign Key */
$stmt = $db->prepare("
    SELECT
        (SELECT COUNT(*) FROM departments WHERE id = ?) AS dep,
        (SELECT COUNT(*) FROM classes     WHERE id = ?) AS cls
");
$stmt->execute([$departmentId, $classId]);
$check = $stmt->fetch(PDO::FETCH_ASSOC);

if ((int)$check['dep'] === 0) {
    die('القسم المحدد غير موجود');
}

if ((int)$check['cls'] === 0) {
    die('الصف المحدد غير موجود');
}

/* ==================== الحفظ ==================== */
$student = new Student();

try {

    $student->create($_POST);

    /* التسجيل بعد نجاح الحفظ فقط */
    Logger::created(
        'students',
        $fullName . ($stuNumber !== '' ? " ($stuNumber)" : '')
    );

    header("Location: " . BASE_URL . "/admin/students/index.php");
    exit;

} catch (PDOException $ex) {

    /* 23000 = بريد مكرر أو رقم أكاديمي مكرر أو مفتاح أجنبي مفقود */
    if ($ex->getCode() === '23000') {
        die('تعذر الحفظ: البريد الإلكتروني أو الرقم الأكاديمي مسجّل مسبقاً');
    }

    throw $ex;

} catch (Exception $ex) {

    /* الموديل يستخدم Transaction ويعيد رمي الاستثناء بعد rollBack */
    die('تعذر حفظ الطالب — لم يتم إدخال أي بيانات ناقصة');
}