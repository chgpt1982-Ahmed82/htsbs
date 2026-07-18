<?php
/*
=====================================================================
login_process.php — معالجة تسجيل الدخول
=====================================================================
التعديلات:
  1. تسجيل الدخول الناجح والفاشل في السجلات
  2. حماية من التخمين (Brute Force): 5 محاولات / 15 دقيقة
  3. تجديد معرّف الجلسة بعد الدخول (منع Session Fixation)
  4. رسالة خطأ موحّدة — لا تكشف أي بريد مسجّل وأيّ ليس كذلك
  5. منع الدخول للحسابات الموقوفة (status)
  6. إغلاق عرض الأخطاء (كان يكشف مسارات الملفات للمهاجمين)
=====================================================================
*/

session_start();

require_once 'config/config.php';
require_once 'core/Auth.php';
require_once 'core/Logger.php';
require_once 'app/models/User.php';

/* ==================== طلب POST فقط ==================== */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/login.php");
    exit;
}

$email    = trim((string)($_POST['email'] ?? ''));
$password = (string)($_POST['password'] ?? '');

if ($email === '' || $password === '') {
    header("Location: " . BASE_URL . "/login.php?error=user");
    exit;
}

/*
====================================================================
(1) حماية من التخمين — Brute Force Protection
5 محاولات فاشلة خلال 15 دقيقة ← حظر مؤقت
بدونها يستطيع المهاجم تجريب آلاف كلمات المرور بلا مانع
====================================================================
*/
$now = time();

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['login_first_try'] = $now;
}

/* إعادة تصفير العداد بعد مرور 15 دقيقة */
if (($now - (int)$_SESSION['login_first_try']) > 900) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['login_first_try'] = $now;
}

if ((int)$_SESSION['login_attempts'] >= 5) {

    $remaining = 900 - ($now - (int)$_SESSION['login_first_try']);
    $minutes   = max(1, (int)ceil($remaining / 60));

    Logger::log(
        'auth',
        'login_blocked',
        "حظر مؤقت بعد 5 محاولات فاشلة — البريد: $email",
        null,
        null,
        'danger'
    );

    die("تم حظر المحاولات مؤقتاً بسبب تكرار الفشل. حاول بعد $minutes دقيقة.");
}

/* ==================== البحث عن المستخدم ==================== */
$userModel = new User();

$user = $userModel->findByEmail($email);

/*
====================================================================
(2) رسالة خطأ موحّدة
الكود القديم كان يميّز: error=user (البريد غير موجود)
                    و error=password (كلمة المرور خاطئة)
وهذا يكشف للمهاجم أي البُرد مسجّلة في النظام (User Enumeration).
الآن: رسالة واحدة لكلتا الحالتين.
====================================================================
*/
if (!$user || !password_verify($password, $user['password'])) {

    $_SESSION['login_attempts'] = (int)$_SESSION['login_attempts'] + 1;

    /* نسجّل المحاولة الفاشلة — تظهر حمراء في لوحة السجلات */
    Logger::loginFailed($email);

    header("Location: " . BASE_URL . "/login.php?error=invalid");
    exit;
}

/*
====================================================================
(3) الحسابات الموقوفة
====================================================================
*/
if (isset($user['status']) && $user['status'] !== 'active') {

    Logger::log(
        'auth',
        'login_inactive',
        "محاولة دخول لحساب موقوف: $email",
        'user',
        (int)$user['id'],
        'warning'
    );

    header("Location: " . BASE_URL . "/login.php?error=inactive");
    exit;
}

/*
====================================================================
(4) نجاح الدخول
====================================================================
*/

/* تصفير عداد المحاولات */
unset($_SESSION['login_attempts'], $_SESSION['login_first_try']);

/*
تجديد معرّف الجلسة — يمنع هجوم Session Fixation
(مهاجم يزرع معرّف جلسة معروفاً لديه قبل الدخول ثم يستعمله بعده)
*/
session_regenerate_id(true);

Auth::login($user);

/* التسجيل بعد فتح الجلسة — ليلتقط Logger بيانات المستخدم */
Logger::login($user);

/* ==================== التوجيه حسب الدور ==================== */
switch ((int)$user['role_id']) {

    case 1:
        header("Location: " . BASE_URL . "/admin/dashboard.php");
        exit;

    case 2:
        header("Location: " . BASE_URL . "/teacher/dashboard.php");
        exit;

    case 3:
        header("Location: " . BASE_URL . "/student/dashboard.php");
        exit;

    case 4:
        header("Location: " . BASE_URL . "/parent/dashboard.php");
        exit;

    default:
        /* خطأ مطبعي في الأصل: "Location:" . BASE_URL . " login.php" */
        header("Location: " . BASE_URL . "/login.php?error=role");
        exit;
}