<?php

session_start();

require_once '../config/config.php';
require_once '../app/models/Message.php';
require_once '../app/models/Notification.php';




$messageModel = new Message();



$messages = $messageModel->sent(
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
<i class="bi bi-send-fill"></i>
الرسائل المرسلة
</h2>

<a
href="inbox.php"
class="btn btn-secondary">

رجوع

</a>
<?php if(isset($_SESSION['success'])): ?>

<div class="alert alert-success">

<?= $_SESSION['success']; ?>

</div>

<?php unset($_SESSION['success']); ?>

<?php endif; ?>
<div class="card shadow">

<div class="card-body">

<div class="table-responsive">

<table class="table table-bordered table-hover table-sm align-middle">

<thead class="table-light">

<tr>

<th>#</th>
<th>المستلم</th>
<th>الموضوع</th>
<th>التاريخ</th>
<th>الإجراءات</th>

</tr>

</thead>

<tbody>

<?php foreach($messages as $index => $message): ?>

<tr>

<td>
<?= $index + 1; ?>
</td>

<td>
<?= htmlspecialchars(
$message['receiver_name']
); ?>
</td>

<td>
<?= htmlspecialchars(
$message['subject']
); ?>
</td>

<td>

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

<a
href="view.php?id=<?= $message['id']; ?>"
class="btn btn-info btn-sm">

<i class="bi bi-eye"></i>
عرض

</a>
<!--
<a
href="delete_sent.php?id=<?= $message['id']; ?>"
class="btn btn-danger btn-sm"
onclick="return confirm('هل تريد حذف هذه الرسالة؟');">

<i class="bi bi-trash"></i>
حذف

</a>
--> 

</td>
</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

</div>

</div>

</div>

</div>

</div>

<?php include '../app/views/layouts/footer.php'; ?>