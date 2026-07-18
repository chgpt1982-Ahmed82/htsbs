<?php
/*
=====================================================================
admin/teachers/store.php — حفظ معلم جديد
=====================================================================
*/

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
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
$fullName     = trim((string)($_POST['full_name'] ?? ''));
$email        = trim((string)($_POST['email'] ?? ''));
$password     = (string)($_POST['password'] ?? '');
$specialization = trim((string)($_POST['specialization'] ?? ''));
$departmentId = (int)($_POST['department_id'] ?? 0);

if ($fullName === '') {
    die('اسم المعلم مطلوب');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die('البريد الإلكتروني غير صالح');
}

if (strlen($password) < 6) {
    die('كلمة المرور يجب ألا تقل عن 6 أحرف');
}

if ($departmentId <= 0) {
    die('يرجى اختيار القسم');
}

/* التأكد أن القسم موجود — يمنع خطأ Foreign Key */
$db = (new Database())->connect();

$stmt = $db->prepare("SELECT COUNT(*) FROM departments WHERE id = ?");
$stmt->execute([$departmentId]);

if ((int)$stmt->fetchColumn() === 0) {
    die('القسم المحدد غير موجود');
}

/* ==================== الحفظ ==================== */
$teacher = new Teacher();

try {

    $teacher->create($_POST);

    /* التسجيل بعد نجاح الحفظ فقط */
    Logger::created(
        'teachers',
        $fullName
        . ($specialization !== '' ? " - $specialization" : '')
        . " | $email"
    );

    header("Location: " . BASE_URL . "/admin/teachers/index.php");
    exit;

} catch (PDOException $ex) {

    /* 23000 = بريد مكرر أو مفتاح أجنبي مفقود */
    if ($ex->getCode() === '23000') {
        die('تعذر الحفظ: البريد الإلكتروني مسجّل مسبقاً');
    }

    throw $ex;

} catch (Exception $ex) {

    /* الموديل يستخدم Transaction ويعيد رمي الاستثناء بعد rollBack */
    die('تعذر حفظ المعلم — لم يتم إدخال أي بيانات ناقصة');
}