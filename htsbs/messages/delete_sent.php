<?php
/*
=====================================================================
messages/delete_sent.php — حذف رسالة من صندوق الصادر
=====================================================================
✅ الآن يستخدم حذفاً منطقياً (deleteForSender):
   الرسالة تختفي من صادر هذا المستخدم فقط،
   وتبقى ظاهرة في وارد المستلم كما هي — لا فقدان بيانات للطرف الآخر
=====================================================================
*/

session_start();

require_once '../config/config.php';
require_once '../core/Auth.php';
require_once '../core/Logger.php';
require_once '../app/models/Message.php';

/* ==================== الصلاحية ==================== */
if (!Auth::check()) {
    exit('Unauthorized');
}

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: sent.php");
    exit;
}

$messageModel = new Message();

$message = $messageModel->find($id);

$userId = (int)$_SESSION['user_id'];

/* التأكد أن الرسالة تخص المستخدم الحالي كمرسل */
if (!$message || (int)$message['sender_id'] !== $userId) {

    Logger::log(
        'messages',
        'delete_sent_denied',
        "محاولة حذف رسالة صادرة لا يملكها المستخدم (message_id=$id)",
        null, null, 'danger'
    );

    exit('Access Denied');
}

$subject      = (string)($message['subject'] ?? '');
$receiverName = (string)($message['receiver_name'] ?? '#' . $message['receiver_id']);

/* ==================== الحذف المنطقي ==================== */
$messageModel->deleteForSender($id);

/* ==================== التسجيل ==================== */
Logger::log(
    'messages',
    'delete_sent_message',
    "حذف رسالة صادرة ($subject) إلى ($receiverName)",
    'message',
    $id,
    'warning'
);

$_SESSION['success'] = 'تم حذف الرسالة بنجاح';

header("Location: sent.php");
exit;