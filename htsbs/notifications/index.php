<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once '../config/config.php';
require_once '../app/models/Notification.php';

$model = new Notification();

$count =
$model->unreadCount(
$_SESSION['user_id']
);

$notifications =
$model->getUserNotifications(
$_SESSION['user_id']
);

include '../app/views/layouts/header.php';

?>
<div class="container-fluid">

<div class="row flex-lg-row-reverse">


<?php
if($_SESSION['role_id'] == 1)
{
    include '../app/views/layouts/sidebar.php';
}
elseif($_SESSION['role_id'] == 2)
{
    include '../app/views/layouts/teacher_sidebar.php';
}
elseif($_SESSION['role_id'] == 3)
{
    include '../app/views/layouts/student_sidebar.php';
}
elseif($_SESSION['role_id'] == 4)
{
    include '../app/views/layouts/parent_sidebar.php';
}

?>

<div class="main-content">

<h2 class="mb-4">

🔔 Notifications

<span class="badge bg-primary">

<?= count($notifications); ?>

</span>

</h2>

<?php if(empty($notifications)): ?>

<div class="alert alert-info">

No Notifications Found

</div>

<?php endif; ?>

<?php foreach($notifications as $n): ?>

<div class="card mb-3 shadow-sm">

<div class="card-body">

<h5 class="text-primary">

<?= htmlspecialchars($n['title']); ?>

</h5>

<p>

<?= htmlspecialchars($n['message']); ?>

</p>

<small class="text-muted">

<?= $n['created_at']; ?>

</small>

<div class="mt-3">

<?php if($n['is_read'] == 0): ?>

<a
href="mark_read.php?id=<?= $n['id']; ?>"
class="btn btn-success btn-sm">

Mark Read

</a>

<?php else: ?>

<span class="badge bg-success">

Read

</span>

<?php endif; ?>

</div>

</div>

</div>

<?php endforeach; ?>

</div>

<?php include '../app/views/layouts/footer.php'; ?>
