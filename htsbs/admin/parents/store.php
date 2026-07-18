<?php
/*
=====================================================================
admin/parents/store.php — إضافة ولي أمر جديد
=====================================================================
🔴 كان مفتوحاً تماماً: لا session_start، لا Auth — أي زائر ينشئ حسابات!
التعديلات: حماية + تحقق مدخلات + Transaction + تسجيل
=====================================================================
*/

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../core/Auth.php';
require_once '../../core/Logger.php';

/* ==================== الصلاحية: أدمن فقط ==================== */
if (!Auth::check() || (int)($_SESSION['role_id'] ?? 0) !== 1) {
    die('Access Denied');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$db = (new Database())->connect();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* ==================== التحقق من المدخلات ==================== */
$fullName = trim((string)($_POST['full_name'] ?? ''));
$email    = trim((string)($_POST['email'] ?? ''));
$password = (string)($_POST['password'] ?? '');
$phone    = trim((string)($_POST['phone'] ?? ''));

if ($fullName === '') {
    die('اسم ولي الأمر مطلوب');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die('البريد الإلكتروني غير صالح');
}

if (strlen($password) < 6) {
    die('كلمة المرور يجب ألا تقل عن 6 أحرف');
}

/* ==================== الحفظ داخل Transaction ==================== */
try {

    $db->beginTransaction();

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $db->prepare("
        INSERT INTO users (role_id, full_name, email, password, phone, status)
        VALUES (4, ?, ?, ?, ?, 'active')
    ");
    $stmt->execute([$fullName, $email, $hash, $phone]);

    $userId = (int)$db->lastInsertId();

    $stmt = $db->prepare("INSERT INTO parents (user_id, phone) VALUES (?, ?)");
    $stmt->execute([$userId, $phone]);

    $db->commit();

} catch (PDOException $ex) {

    if ($db->inTransaction()) {
        $db->rollBack();
    }

    if ($ex->getCode() === '23000') {
        die('تعذر الحفظ: البريد الإلكتروني مسجّل مسبقاً');
    }

    throw $ex;

} catch (Throwable $ex) {

    if ($db->inTransaction()) {
        $db->rollBack();
    }

    die('تعذر حفظ ولي الأمر');
}

/* ==================== التسجيل ==================== */
Logger::created('parents', $fullName . ' | ' . $email);

header('Location: index.php');
exit;