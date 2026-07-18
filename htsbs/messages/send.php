<?php
/*
=====================================================================
messages/send.php — إرسال رسالة (فردية أو جماعية)
=====================================================================
التعديلات:
  1. تسجيل الإرسال (خصوصاً الجماعي — يصل لعشرات المستخدمين)
  2. حماية: Auth::check() بدل الاعتماد على $_SESSION مباشرة
  3. Transaction للإرسال الجماعي — لا رسائل جزئية عند فشل منتصف القائمة
  4. حد أقصى لعدد المستلمين (حماية من إساءة الاستخدام / إغراق النظام)
  5. حذف كتلة echo/print_r التشخيصية المعلّقة
=====================================================================
*/

session_start();

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../core/Auth.php';
require_once '../core/Logger.php';
require_once '../app/models/Message.php';
require_once '../app/models/Notification.php';

/* ==================== الصلاحية ==================== */
if (!Auth::check()) {
    die('Access Denied');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: inbox.php");
    exit;
}

$db = (new Database())->connect();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$messageModel      = new Message();
$notificationModel = new Notification();

$senderId = (int)$_SESSION['user_id'];
$roleId   = (int)($_SESSION['role_id'] ?? 0);

$subject  = trim((string)($_POST['subject'] ?? ''));
$message  = trim((string)($_POST['message'] ?? ''));
$sendType = trim((string)($_POST['send_type'] ?? 'single'));

if ($subject === '' || $message === '') {
    header("Location: compose.php?error=1");
    exit;
}

/* ==================== قائمة المستلمين ==================== */
$receivers = [];

/*
====================================================================
مستلم فردي واحد — يشمل:
  - الطالب / ولي الأمر: يملكان مستلماً واحداً ثابتاً دائماً
  - المعلم: عند send_type يشير لمستلم فردي (student/teacher/parent/admin)
  - 🔴 الأدمن: نفس الحالة — كان مفقوداً تماماً، وهذا سبب "error=2"
    عند اختيار "معلم محدد" أو "طالب محدد" أو "ولي أمر محدد" أو "إداري محدد"
====================================================================
*/
$individualTypes = ['student', 'teacher', 'parent', 'admin'];

if (in_array($roleId, [3, 4], true)
    || (in_array($roleId, [1, 2], true) && in_array($sendType, $individualTypes, true))) {

    $receiverId = (int)($_POST['receiver_id'] ?? 0);

    if ($receiverId > 0) {
        $receivers[] = $receiverId;
    }
}
/* ==================== المعلم ==================== */
if ($roleId === 2) {

    $teacherStmt = $db->prepare("SELECT id FROM teachers WHERE user_id = ?");
    $teacherStmt->execute([$senderId]);
    $teacher   = $teacherStmt->fetch(PDO::FETCH_ASSOC);
    $teacherId = (int)($teacher['id'] ?? 0);

    switch ($sendType) {

        case 'class':

            $classId = (int)($_POST['class_id'] ?? 0);

            /*
            حماية: الصف يجب أن يكون مسنداً للمعلم فعلاً
            النسخة السابقة كانت تقبل أي class_id بلا تحقق
            */
            $check = $db->prepare("
                SELECT COUNT(*) FROM course_assignments
                WHERE teacher_id = ? AND class_id = ?
            ");
            $check->execute([$teacherId, $classId]);

            if ((int)$check->fetchColumn() === 0) {

                Logger::log(
                    'messages', 'send_denied',
                    "محاولة إرسال رسالة لصف غير مسند للمعلم (class_id=$classId)",
                    'class', $classId, 'danger'
                );

                die('غير مصرح لك بإرسال رسائل لهذا الصف');
            }

            $stmt = $db->prepare("
                SELECT u.id FROM students s
                INNER JOIN users u ON s.user_id = u.id
                WHERE s.class_id = ?
            ");
            $stmt->execute([$classId]);
            $receivers = $stmt->fetchAll(PDO::FETCH_COLUMN);
            break;

        case 'all_my_students':

            $stmt = $db->prepare("
                SELECT DISTINCT u.id FROM students s
                INNER JOIN users u ON s.user_id = u.id
                INNER JOIN course_assignments ca ON s.class_id = ca.class_id
                WHERE ca.teacher_id = ?
            ");
            $stmt->execute([$teacherId]);
            $receivers = $stmt->fetchAll(PDO::FETCH_COLUMN);
            break;

        case 'teachers':
            $receivers = $db->query("SELECT id FROM users WHERE role_id = 2")
                             ->fetchAll(PDO::FETCH_COLUMN);
            break;

        case 'admins':
            $receivers = $db->query("SELECT id FROM users WHERE role_id = 1")
                             ->fetchAll(PDO::FETCH_COLUMN);
            break;
    }
}

/* ==================== الإدارة ==================== */
if ($roleId === 1) {

    switch ($sendType) {

        case 'all_students':
            $receivers = $db->query("SELECT id FROM users WHERE role_id = 3")
                             ->fetchAll(PDO::FETCH_COLUMN);
            break;

        case 'all_teachers':
            $receivers = $db->query("SELECT id FROM users WHERE role_id = 2")
                             ->fetchAll(PDO::FETCH_COLUMN);
            break;

        case 'all_parents':
            $receivers = $db->query("SELECT id FROM users WHERE role_id = 4")
                             ->fetchAll(PDO::FETCH_COLUMN);
            break;

        case 'admins':
            $receivers = $db->query("SELECT id FROM users WHERE role_id = 1")
                             ->fetchAll(PDO::FETCH_COLUMN);
            break;
    }
}

/* حذف التكرار + استبعاد المرسل نفسه من قائمة المستلمين */
$receivers = array_values(array_unique(array_diff(
    array_map('intval', $receivers),
    [$senderId]
)));

if (!$receivers) {
    header("Location: compose.php?error=2");
    exit;
}

/*
====================================================================
حد أقصى لعدد المستلمين
حماية من إرسال جماعي مفرط يُبطئ الخادم أو يُساء استخدامه
(بث فعلي لآلاف المستخدمين دفعة واحدة على استضافة مشتركة)
====================================================================
*/
$maxRecipients = 1000;

if (count($receivers) > $maxRecipients) {
    die("عدد المستلمين ({$receivers[0]}...) يتجاوز الحد المسموح ($maxRecipients)");
}

/* ==================== الإرسال داخل Transaction ==================== */
try {

    $db->beginTransaction();

    foreach ($receivers as $receiverId) {

        $messageModel->send($senderId, $receiverId, $subject, $message);

        $notificationModel->create(
            $receiverId,
            'رسالة جديدة',
            $subject,
            'message'
        );
    }

    $db->commit();

} catch (Throwable $ex) {

    if ($db->inTransaction()) {
        $db->rollBack();
    }

    Logger::log(
        'messages', 'send_failed',
        "فشل إرسال رسالة ($subject) لـ " . count($receivers) . " مستلماً",
        null, null, 'danger'
    );

    die('تعذر إرسال الرسالة');
}

/*
====================================================================
التسجيل
الإرسال الجماعي (سند_type غير single) يستحق تنبيهاً أعلى
لأنه يصل لعدد كبير من المستخدمين دفعة واحدة
====================================================================
*/
$isBulk = count($receivers) > 1;

Logger::log(
    'messages',
    'send_message',
    "إرسال رسالة ($subject)"
    . " - النوع ($sendType)"
    . " - عدد المستلمين (" . count($receivers) . ")",
    'message',
    null,
    $isBulk ? 'warning' : 'info'
);

header("Location: inbox.php?sent=1");
exit;