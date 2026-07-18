<?php
/*
=====================================================================
messages/view.php — عرض تفاصيل رسالة
=====================================================================
التعديلات:
  1. 🔴 حماية: كانت الصفحة تفتح أي رسالة بمعرّفها بلا أي تحقق!
     أي مستخدم يقرأ رسائل غيره بتغيير ?id= — تسريب خصوصية كامل
  2. حماية صلاحيات عامة (Auth::check)
  3. تصميم احترافي: صورة رمزية بالحروف الأولى، شارة دور المرسل،
     تمييز بصري بين الوارد/الصادر، زر رد سريع، طباعة، تنسيق تاريخ نسبي
  4. دالة e() لمعالجة NULL بأمان
=====================================================================
*/

session_start();

require_once '../config/config.php';
require_once '../core/Auth.php';
require_once '../app/models/Message.php';
require_once '../app/models/Notification.php';

/* ==================== الصلاحية ==================== */
if (!Auth::check()) {
    die('Access Denied');
}

if (!function_exists('e')) {
    function e($v): string {
        return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

$messageModel      = new Message();
$notificationModel = new Notification();

$userId = (int)$_SESSION['user_id'];
$roleId = (int)($_SESSION['role_id'] ?? 0);

$count = $notificationModel->unreadCount($userId);

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die('Message ID Not Found');
}

$message = $messageModel->find($id);

if (!$message) {
    die('Message Not Found');
}

/*
====================================================================
🔴 حماية جوهرية: السماح بالعرض فقط للمرسل أو المستلم
النسخة السابقة كانت تعرض أي رسالة بمعرّفها بلا أي تحقق ملكية —
أي مستخدم يقرأ خصوصيات محادثات غيره بتغيير ?id= في الرابط
====================================================================
*/
$isReceiver = ((int)$message['receiver_id'] === $userId);
$isSender   = ((int)$message['sender_id'] === $userId);

if (!$isReceiver && !$isSender) {

    if (class_exists('Logger')) {
        require_once '../core/Logger.php';
        Logger::log(
            'messages',
            'view_denied',
            "محاولة قراءة رسالة لا تخص المستخدم (message_id=$id)",
            null, null, 'danger'
        );
    }

    die('Access Denied');
}

/* تعليم الرسالة كمقروءة (فقط إن كان المستخدم هو المستلم) */
if ($isReceiver && !(int)($message['is_read'] ?? 0)) {
    $messageModel->markRead($id);
    $message['is_read'] = 1;
}

/* ==================== مساعدات العرض ==================== */

/* الحروف الأولى من الاسم لصورة رمزية */
function initials(string $name): string
{
    $name  = trim($name);
    $parts = preg_split('/\s+/', $name);
    $first = mb_substr($parts[0] ?? '', 0, 1);
    $second = isset($parts[1]) ? mb_substr($parts[1], 0, 1) : '';
    return mb_strtoupper($first . $second);
}

/* لون ثابت للصورة الرمزية مبني على اسم المرسل (نفس الشخص = نفس اللون دوماً) */
function avatarColor(string $seed): string
{
    $palette = ['#4f46e5', '#0891b2', '#0d9488', '#ca8a04', '#dc2626', '#7c3aed', '#db2777', '#059669'];
    $hash = 0;
    foreach (str_split($seed) as $ch) {
        $hash = (int)$hash + (int)ord($ch);
    }
    return $palette[$hash % count($palette)];
}

/* تسمية الدور */
$roleLabels = [1 => 'أدمن', 2 => 'معلم', 3 => 'طالب', 4 => 'ولي أمر'];

$senderName    = (string)($message['sender_name'] ?? 'مستخدم محذوف');
$receiverName  = (string)($message['receiver_name'] ?? 'مستخدم محذوف');
$senderRoleId  = (int)($message['sender_role'] ?? 0);
$senderRoleAr  = $roleLabels[$senderRoleId] ?? '';

$createdAt   = (string)($message['created_at'] ?? '');
$createdTs   = $createdAt !== '' ? strtotime($createdAt) : time();

/* تنسيق تاريخ عربي مقروء: اليوم / أمس / تاريخ كامل */
function formatArabicDate(int $ts): string
{
    $today     = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $day       = date('Y-m-d', $ts);
    $time      = date('H:i', $ts);

    if ($day === $today) {
        return "اليوم، الساعة $time";
    }
    if ($day === $yesterday) {
        return "أمس، الساعة $time";
    }

    $months = [
        1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
        5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
        9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
    ];

    return (int)date('d', $ts) . ' ' . $months[(int)date('n', $ts)] . ' ' . date('Y', $ts) . "، الساعة $time";
}

include '../app/views/layouts/header.php';
?>

<div class="container-fluid">
<div class="row flex-lg-row-reverse">

<?php
switch ($roleId) {
    case 1: include '../app/views/layouts/sidebar.php'; break;
    case 2: include '../app/views/layouts/teacher_sidebar.php'; break;
    case 3: include '../app/views/layouts/student_sidebar.php'; break;
    case 4: include '../app/views/layouts/parent_sidebar.php'; break;
}
?>

<div class="main-content">

<style>
.msg-view-card {
    border: none;
    border-radius: 16px;
    box-shadow: 0 2px 16px rgba(0,0,0,.06);
    overflow: hidden;
}
.msg-view-header {
    padding: 1.5rem;
    background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);
    border-bottom: 1px solid #e5e7eb;
}
.msg-avatar {
    width: 54px;
    height: 54px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 700;
    font-size: 1.15rem;
    flex-shrink: 0;
    box-shadow: 0 2px 6px rgba(0,0,0,.15);
}
.msg-subject {
    font-size: 1.35rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
}
.msg-meta-name {
    font-weight: 600;
    color: #1e293b;
}
.msg-meta-sub {
    font-size: .85rem;
    color: #64748b;
}
.msg-role-badge {
    font-size: .7rem;
    padding: .15rem .55rem;
    border-radius: 999px;
    background: #e0e7ff;
    color: #4338ca;
    font-weight: 600;
}
.msg-direction-badge {
    font-size: .75rem;
    padding: .3rem .75rem;
    border-radius: 999px;
    font-weight: 600;
}
.msg-direction-in {
    background: #dcfce7;
    color: #15803d;
}
.msg-direction-out {
    background: #dbeafe;
    color: #1d4ed8;
}
.msg-body {
    padding: 2rem 1.75rem;
    font-size: 1.02rem;
    line-height: 2;
    color: #334155;
    white-space: pre-wrap;
    word-break: break-word;
    min-height: 120px;
}
.msg-footer {
    padding: 1rem 1.75rem;
    background: #f8fafc;
    border-top: 1px solid #e5e7eb;
    display: flex;
    gap: .5rem;
    flex-wrap: wrap;
}
@media print {
    .msg-footer, .no-print { display: none !important; }
    .msg-view-card { box-shadow: none; }
}
</style>

<div class="d-flex align-items-center justify-content-between mb-3 no-print">
    <h4 class="fw-bold mb-0">
        <i class="bi bi-envelope-open-fill text-primary"></i> تفاصيل الرسالة
    </h4>
    <a href="<?= $isReceiver ? 'inbox.php' : 'sent.php'; ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-right"></i> رجوع
    </a>
</div>

<div class="msg-view-card">

    <!-- ==================== رأس الرسالة ==================== -->
    <div class="msg-view-header">

        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">

            <span class="msg-direction-badge <?= $isReceiver ? 'msg-direction-in' : 'msg-direction-out'; ?>">
                <?php if ($isReceiver): ?>
                    <i class="bi bi-inbox-fill"></i> رسالة واردة
                <?php else: ?>
                    <i class="bi bi-send-fill"></i> رسالة صادرة
                <?php endif; ?>
            </span>

            <span class="text-muted small">
                <i class="bi bi-clock"></i>
                <?= e(formatArabicDate($createdTs)); ?>
            </span>

        </div>

        <h3 class="msg-subject mb-3"><?= e($message['subject']); ?></h3>

        <div class="d-flex align-items-center gap-3">

            <div class="msg-avatar" style="background: <?= avatarColor($senderName); ?>;">
                <?= e(initials($senderName)); ?>
            </div>

            <div>
                <div class="msg-meta-name">
                    <?= e($senderName); ?>
                    <?php if ($senderRoleAr !== ''): ?>
                        <span class="msg-role-badge"><?= e($senderRoleAr); ?></span>
                    <?php endif; ?>
                </div>
                <div class="msg-meta-sub">
                    <i class="bi bi-arrow-left-short"></i>
                    إلى: <?= e($receiverName); ?>
                </div>
            </div>

        </div>

    </div>

    <!-- ==================== نص الرسالة ==================== -->
    <div class="msg-body"><?= e($message['message']); ?></div>

    <!-- ==================== إجراءات ==================== -->
    <div class="msg-footer no-print">

        <?php if ($isReceiver): ?>
            <a href="compose.php?reply_to=<?= (int)$message['sender_id']; ?>&subject=<?= urlencode('رد: ' . $message['subject']); ?>"
               class="btn btn-primary btn-sm">
                <i class="bi bi-reply-fill"></i> رد
            </a>
        <?php endif; ?>

        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-printer"></i> طباعة
        </button>

        <a href="<?= $isReceiver ? 'delete.php' : 'delete_sent.php'; ?>?id=<?= (int)$message['id']; ?>"
           class="btn btn-outline-danger btn-sm ms-auto"
           onclick="return confirm('هل تريد حذف هذه الرسالة نهائياً؟');">
            <i class="bi bi-trash"></i> حذف
        </a>

    </div>

</div>

</div>
</div>
</div>

<?php include '../app/views/layouts/footer.php'; ?>