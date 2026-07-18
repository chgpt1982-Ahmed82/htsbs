<?php
/*
=====================================================================
admin/parents/update.php — تعديل ولي أمر
🔴 كان مفتوحاً تماماً + خطأ: يحدّث users.id بـ user_id
   لكن parents بـ id — تأكد من التطابق
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
$parentId = (int)($_POST['id'] ?? 0);
$userId   = (int)($_POST['user_id'] ?? 0);
$fullName = trim((string)($_POST['full_name'] ?? ''));
$email    = trim((string)($_POST['email'] ?? ''));
$phone    = trim((string)($_POST['phone'] ?? ''));
$password = (string)($_POST['password'] ?? '');

if ($parentId <= 0 || $userId <= 0) {
    die('معرّف ولي الأمر مفقود');
}

if ($fullName === '') {
    die('الاسم مطلوب');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die('البريد الإلكتروني غير صالح');
}

if ($password !== '' && strlen($password) < 6) {
    die('كلمة المرور يجب ألا تقل عن 6 أحرف');
}

/*
====================================================================
التأكد أن (parent_id, user_id) يخصّان ولي أمر فعلاً
حماية من تعديل مستخدم من دور آخر (أدمن مثلاً) بتمرير user_id مزيّف
====================================================================
*/
$stmt = $db->prepare("
    SELECT p.id, u.full_name, u.role_id
    FROM parents p
    INNER JOIN users u ON p.user_id = u.id
    WHERE p.id = ? AND p.user_id = ?
");
$stmt->execute([$parentId, $userId]);
$old = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$old || (int)$old['role_id'] !== 4) {
    die('ولي الأمر غير موجود');
}

$oldName = trim((string)($old['full_name'] ?? ''));

/* ==================== التحديث داخل Transaction ==================== */
try {

    $db->beginTransaction();

    if ($password !== '') {

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $db->prepare("
            UPDATE users SET full_name = ?, email = ?, phone = ?, password = ?
            WHERE id = ? AND role_id = 4
        ");
        $stmt->execute([$fullName, $email, $phone, $hash, $userId]);

    } else {

        $stmt =