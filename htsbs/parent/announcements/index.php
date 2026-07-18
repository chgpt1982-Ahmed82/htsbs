<?php

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';

require_once '../../app/models/Announcement.php';
require_once '../../app/models/Notification.php';

$db = (new Database())->connect();

$announcementModel =
new Announcement();

$notificationModel =
new Notification();

$count =
$notificationModel->unreadCount(
$_SESSION['user_id']
);

/*
جلب أبناء ولي الأمر
*/

$stmt = $db->prepare(

"SELECT

s.id,
s.class_id,
u.full_name,
s.student_number

FROM parent_student ps

INNER JOIN students s
ON ps.student_id=s.id

INNER JOIN users u
ON s.user_id=u.id

INNER JOIN parents p
ON ps.parent_id=p.id

WHERE p.user_id=?"

);

$stmt->execute([
$_SESSION['user_id']
]);

$children =
$stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../app/views/layouts/header.php';
include '../../app/views/layouts/parent_sidebar.php';

?>

<div class="col-md-10 p-4">

<h2 class="mb-4">

📢 School Announcements

</h2>

<?php if(empty($children)): ?>

<div class="alert alert-warning">

No Students Linked To This Parent

</div>

<?php else: ?>

<?php foreach($children as $child): ?>

<div class="card mb-4">

<div class="card-header bg-primary text-white">

<strong>

<?= htmlspecialchars(
$child['full_name']
); ?>

</strong>

*

<?= htmlspecialchars(
$child['student_number']
); ?>

</div>

<div class="card-body">

<?php

$announcements =
$announcementModel->getParentAnnouncements(
$child['class_id']
);

?>

<?php if(empty($announcements)): ?>

<div class="alert alert-info">

No Announcements Available

</div>

<?php else: ?>

<?php foreach($announcements as $announcement): ?>

<div class="card mb-3 border-primary">

<div class="card-header bg-light">

<div class="d-flex justify-content-between">

<strong>

<?= htmlspecialchars(
$announcement['title']
); ?>

</strong>

<small>

<?= date(

'd/m/Y',

strtotime(
$announcement['created_at']
)

); ?>

</small>

</div>

</div>

<div class="card-body">

<p>

<?= nl2br(

htmlspecialchars(
$announcement['message']
)

); ?>

</p>

</div>

<div class="card-footer text-muted">

Published:

<?= $announcement['created_at']; ?>

</div>

</div>

<?php endforeach; ?>

<?php endif; ?>

</div>

</div>

<?php endforeach; ?>

<?php endif; ?>

</div>

<?php include '../../app/views/layouts/footer.php'; ?>
