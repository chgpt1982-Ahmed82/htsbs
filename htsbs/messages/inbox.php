<?php

session_start();

require_once '../config/config.php';
require_once '../app/models/Message.php';
require_once '../app/models/Notification.php';

$messageModel = new Message();
$notificationModel = new Notification();

$count =
$notificationModel->unreadCount(
    $_SESSION['user_id']
);

$messages =
$messageModel->inbox(
    $_SESSION['user_id']
);

include '../app/views/layouts/header.php';

?>

<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php

switch($_SESSION['role_id'])
{
    case 1:
        include '../app/views/layouts/sidebar.php';
    break;

    case 2:
        include '../app/views/layouts/teacher_sidebar.php';
    break;

    case 3:
        include '../app/views/layouts/student_sidebar.php';
    break;

    case 4:
        include '../app/views/layouts/parent_sidebar.php';
    break;
}

?>

<div class="main-content">

<h2 class="mb-3">
<i class="bi bi-envelope-fill"></i>
صندوق الوارد
</h2>

<a
href="compose.php"
class="btn btn-primary mb-3">

<i class="bi bi-envelope-plus-fill"></i>
إرسال رسالة جديدة

</a>
<a
href="sent.php"
class="btn btn-success mb-3">

<i class="bi bi-send-fill"></i>

الرسائل المرسلة

</a>
<?php if(isset($_GET['sent'])): ?>

<div class="alert alert-success">

تم إرسال الرسالة بنجاح

</div>

<?php endif; ?>

<style>

.table td,
.table th{
    padding:6px !important;
    vertical-align:middle;
    font-size:13px;
}

.text-nowrap{
    white-space:nowrap;
}

</style>

<div class="card shadow">

<div class="card-body">

<div class="table-responsive">

<table class="table table-bordered table-hover table-sm align-middle small">

<thead class="table-light">

<tr>

<th width="20%">المرسل</th>
<th width="15%">الوصف</th>
<th width="27%">الموضوع</th>
<th width="12%">التاريخ</th>
<th width="10%">الحالة</th>
<th width="10%">الإجراءات</th>

</tr>

</thead>

<tbody>

<?php if(empty($messages)): ?>

<tr>

<td colspan="6" class="text-center text-muted">

لا توجد رسائل

</td>

</tr>

<?php else: ?>

<?php foreach($messages as $message): ?>

<tr>

<td>

<strong>
<?= htmlspecialchars($message['sender_name']); ?>
</strong>

</td>

<td>

<?php

if($message['role_id'] == 1)
{
    echo '<span class="badge bg-danger">إداري</span>';
}
elseif($message['role_id'] == 2)
{
    echo '<span class="badge bg-primary">معلم</span>';

    if(!empty($message['department_name']))
    {
        echo '<br><small class="text-muted">'
        . htmlspecialchars($message['department_name'])
        . '</small>';
    }
}
elseif($message['role_id'] == 3)
{
    echo '<span class="badge bg-success">طالب</span>';

    if(!empty($message['class_name']))
    {
        echo '<br><small class="text-muted">'
        . htmlspecialchars($message['class_name'])
        . '</small>';
    }
}
elseif($message['role_id'] == 4)
{
    echo '<span class="badge bg-warning text-dark">ولي أمر</span>';
}

?>

</td>

<td>

<?= htmlspecialchars($message['subject']); ?>

</td>

<td class="text-nowrap">

<?= date(
'd/m/Y',
strtotime($message['created_at'])
); ?>

<br>

<small class="text-muted">

<?= date(
'H:i',
strtotime($message['created_at'])
); ?>

</small>

</td>

<td>

<?php if($message['is_read'] == 0): ?>

<span class="badge bg-danger">

غير مقروءة

</span>

<?php else: ?>

<span class="badge bg-success">

مقروءة

</span>

<?php endif; ?>

</td>

<td>

<a
href="view.php?id=<?= $message['id']; ?>"
class="btn btn-info btn-sm">

<i class="bi bi-eye"></i>

عرض

</a>

</td>

</tr>

<?php endforeach; ?>

<?php endif; ?>

</tbody>

</table>

</div>

</div>

</div>

</div>

</div>

</div>

<?php include '../app/views/layouts/footer.php'; ?>