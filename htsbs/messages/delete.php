<?php
/*
=====================================================================
messages/delete.php — حذف رسالة من صندوق الوارد
=====================================================================
✅ الآن يستخدم حذفاً منطقياً (deleteForReceiver):
   الرسالة تختفي من وارد هذا المستخدم فقط،
   وتبقى ظاهرة في صادر المرسل كما هي — لا فقدان بيانات للطرف الآخر
=====================================================================
*/

session_start();

require_once '../config/config.php';
require_once '../core/Auth.php';
require_once '../core/Logger.php';
require_once '../app/models/Message.php';

/* ==================== الصلاحية ==================== */
if (!Auth::check()) {
    die('Access Denied');
}

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: inbox.php");
    exit;
}

$messageModel = new Message();

$message = $messageModel->find($id);

if (!$message) {
    header("Location: inbox.php");
    exit;
}

$userId = (int)$_SESSION['user_id'];

/*
====================================================================
السماح بالحذف فقط لمن كان مستلماً فعلياً لهذه الرسالة
(delete.php مخصص لصندوق الوارد حصراً — المرسل يستخدم delete_sent.php)
====================================================================
*/
if ((int)$message['receiver_id'] !== $userId) {

    Logger::log(
        'messages',
        'delete_denied',
        "محاولة حذف رسالة من الوارد لا تخص المستخدم (message_id=$id)",
        null, null, 'danger'
    );

    die('Access Denied');
}

/* بيانات الرسالة — للسجل، قبل الحذف */
$subject     = (string)($message['subject'] ?? '');
$senderName  = (string)($message['sender_name'] ?? '#' . $message['sender_id']);

/* ==================== الحذف المنطقي ==================== */
$messageModel->deleteForReceiver($id);

/* ==================== التسجيل ==================== */
Logger::log(
    'messages',
    'delete_message',
    "حذف رسالة ($subject) من الوارد — واردة من ($senderName)",
    'message',
    $id,
    'warning'
);

header("Location: inbox.php?deleted=1");
exit;