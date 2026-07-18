<?php
/*
=====================================================================
LMS Module - ملف التهيئة المشترك
يعتمد على ملفات الإعداد الحالية للمشروع دون أي تعديل عليها
=====================================================================
*/

if (session_status() === PHP_SESSION_NONE) {
    // إعدادات أمان الجلسة
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once __DIR__ . '/LmsHelper.php';

/*
====================================
اتصال قاعدة البيانات (PDO)
====================================
*/
$db  = (new Database())->connect();
$lms = new LmsHelper($db);

/*
====================================
CSRF Protection
====================================
*/
if (empty($_SESSION['lms_csrf'])) {
    $_SESSION['lms_csrf'] = bin2hex(random_bytes(32));
}

function lms_csrf_token()
{
    return $_SESSION['lms_csrf'];
}

function lms_csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="'
         . htmlspecialchars($_SESSION['lms_csrf']) . '">';
}

function lms_csrf_check()
{
    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['lms_csrf'], $_POST['csrf_token'])
    ) {
        http_response_code(403);
        exit(json_encode([
            'success' => false,
            'message' => 'انتهت صلاحية الجلسة، حدث الصفحة وحاول مجدداً'
        ], JSON_UNESCAPED_UNICODE));
    }
}

/*
====================================
Role-Based Access Control
role_id: 1=Admin, 2=Teacher, 3=Student
====================================
*/
function lms_require_role(int $roleId)
{
    if (
        !isset($_SESSION['user_id']) ||
        (int)($_SESSION['role_id'] ?? 0) !== $roleId
    ) {
        header("Location: " . BASE_URL . "/login.php");
        exit;
    }
}

/*
====================================
Output Escaping
====================================
*/
function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/*
====================================
تنسيق الوقت بالعربية
====================================
*/
function lms_format_seconds(int $seconds): string
{
    if ($seconds < 60)   return $seconds . ' ثانية';
    if ($seconds < 3600) return floor($seconds / 60) . ' دقيقة';
    $h = floor($seconds / 3600);
    $m = floor(($seconds % 3600) / 60);
    return $h . ' ساعة ' . ($m > 0 ? $m . ' دقيقة' : '');
}

/*
====================================
رفع ملفات آمن (File Upload Validation)
====================================
*/
function lms_upload_file(array $file, string $subDir, array $allowedExt, int $maxMB = 50)
{
    if (empty($file['name']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null; // لم يُرفع ملف
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('فشل رفع الملف (رمز الخطأ: ' . $file['error'] . ')');
    }

    if ($file['size'] > $maxMB * 1024 * 1024) {
        throw new Exception('حجم الملف يتجاوز ' . $maxMB . 'MB');
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedExt, true)) {
        throw new Exception('نوع الملف غير مسموح: ' . $ext);
    }

    // منع الملفات التنفيذية نهائياً
    $blocked = ['php','php3','php4','php5','phtml','exe','sh','bat','js','html','htm','svg'];
    if (in_array($ext, $blocked, true)) {
        throw new Exception('نوع الملف محظور لأسباب أمنية');
    }

    $dir = dirname(__DIR__) . '/uploads/' . $subDir;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    // اسم فريد آمن
    $newName = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;

    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $newName)) {
        throw new Exception('تعذر حفظ الملف على الخادم');
    }

    return $subDir . '/' . $newName;
}
