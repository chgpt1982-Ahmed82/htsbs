<?php
/*
=====================================================================
admin/parents/save_link.php — ربط طالب بولي أمر
🔴 كان مفتوحاً تماماً — أي زائر يربط أي طالب بأي ولي أمر
   (تسريب بيانات: ولي أمر يرى درجات وحضور طفل ليس له!)
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

$parentId  = (int)($_POST['parent_id'] ?? 0);
$studentId = (int)($_POST['student_id'] ?? 0);

if ($parentId <= 0 || $studentId <= 0) {
    die('يرجى اختيار ولي الأمر والطالب');
}

/*
====================================================================
التأكد أن ولي الأمر والطالب موجودان فعلاً
مع جلب أسمائهما للسجل
====================================================================
*/
$stmt = $db->prepare("
    SELECT u.full_name FROM parents p
    INNER JOIN users u ON p.user_id = u.id
    WHERE p.id = ?
");
$stmt->execute([$parentId]);
$parentName = $stmt->fetchColumn();

if ($parentName === false) {
    die('ولي الأمر غير موجود');
}

$stmt = $db->prepare("
    SELECT u.full_name, s.student_number FROM students s
    INNER JOIN users u ON s.user_id = u.id
    WHERE s.id = ?
");
$stmt->execute([$studentId]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    die('الطالب غير موجود');
}

/* ==================== الربط (إن لم يكن موجوداً) ==================== */
$check = $db->prepare("
    SELECT id FROM parent_student WHERE parent_id = ? AND student_id = ?
");
$check->execute([$parentId, $studentId]);

if ($check->fetch()) {

    /* مرتبط مسبقاً — لا نكرر ولا نسجّل */
    header('Location: index.php?already=1');
    exit;
}

$stmt = $db->prepare("
    INSERT INTO parent_student (parent_id, student_id) VALUES (?, ?)
");
$stmt->execute([$parentId, $studentId]);

/* ==================== التسجيل ==================== */
Logger::log(
    'parents',
    'link_student',
    "ربط الطالب ({$student['full_name']}"
    . (!empty($student['student_number']) ? ' - ' . $student['student_number'] : '')
    . ") بولي الأمر ($parentName)",
    'student',
    $studentId,
    'warning'
);

header('Location: index.php?linked=1');
exit;