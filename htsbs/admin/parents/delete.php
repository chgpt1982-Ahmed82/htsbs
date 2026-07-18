<?php
/*
=====================================================================
admin/parents/delete.php — حذف ولي أمر
🔴 كان مفتوحاً تماماً — أي زائر يحذف أي مستخدم!
⚠️ البيانات تُقرأ قبل الحذف — بعده تضيع نهائياً
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

$db = (new Database())->connect();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die('Parent ID Not Found');
}

/*
====================================================================
✅ نقرأ بيانات ولي الأمر قبل الحذف — مع التأكد أنه ولي أمر فعلاً
(role_id = 4، حماية من حذف مستخدم من دور آخر بتمرير id مزيّف)
====================================================================
*/
$stmt = $db->prepare("
    SELECT p.user_id, u.full_name, u.email, u.role_id,
           (SELECT COUNT(*) FROM parent_student ps WHERE ps.parent_id = p.id) AS links
    FROM parents p
    INNER JOIN users u ON p.user_id = u.id
    WHERE p.id = ?
");
$stmt->execute([$id]);
$parent = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$parent || (int)$parent['role_id'] !== 4) {
    die('ولي الأمر غير موجود');
}

$userId = (int)$parent['user_id'];
$label  = trim((string)($parent['full_name'] ?? ''));
$label  = ($label !== '' ? $label : ('#' . $id))
        . (!empty($parent['email']) ? ' | ' . $parent['email'] : '');
$links  = (int)$parent['links'];

/* ==================== الحذف داخل Transaction ==================== */
try {

    $db->beginTransaction();

    /* الروابط مع الطلاب */
    $stmt = $db->prepare("DELETE FROM parent_student WHERE parent_id = ?");
    $stmt->execute([$id]);

    /* سجل ولي الأمر */
    $stmt = $db->prepare("DELETE FROM parents WHERE id = ?");
    $stmt->execute([$id]);

    /* حساب المستخدم — مع شرط role_id = 4 للأمان */
    $stmt = $db->prepare("DELETE FROM users WHERE id = ? AND role_id = 4");
    $stmt->execute([$userId]);

    $db->commit();

} catch (Throwable $ex) {

    if ($db->inTransaction()) {
        $db->rollBack();
    }

    die('تعذر حذف ولي الأمر');
}

/* ==================== التسجيل ==================== */
Logger::deleted(
    'parents',
    $label . ($links > 0 ? " | كان مرتبطاً بـ $links طالباً" : ''),
    $id
);

header('Location: index.php');
exit;