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
جلب المعلم الحالي
*/

$stmt = $db->prepare(

"SELECT id

 FROM teachers

 WHERE user_id=?"

);

$stmt->execute([
$_SESSION['user_id']
]);

$teacher =
$stmt->fetch(PDO::FETCH_ASSOC);

if(!$teacher)
{
    die('Teacher Not Found');
}

/*
إعلانات المعلم
*/

$announcements =
$announcementModel->getTeacherAnnouncements(
$teacher['id']
);

include '../../app/views/layouts/header.php';

?>
<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">   
<div class="d-flex justify-content-between align-items-center mb-4">

<h2>

Announcements

</h2>

<a
href="create.php"
class="btn btn-primary">

➕ Create Announcement

</a>

</div>

<?php if(isset($_GET['success'])): ?>

<div class="alert alert-success">

Announcement Published Successfully

</div>

<?php endif; ?>

<?php if(empty($announcements)): ?>

<div class="alert alert-info">

No Announcements Found

</div>

<?php else: ?>

<table class="table table-bordered table-striped">

<thead>

<tr>

<th>ID</th>

<th>Title</th>

<th>Classes</th>

<th>Students</th>

<th>Created At</th>

<th>View</th>

</tr>

</thead>

<tbody>

<?php foreach($announcements as $announcement): ?>

<?php

$classes =
$announcementModel->getAnnouncementClasses(
$announcement['id']
);

$classNames = [];

$totalStudents = 0;

foreach($classes as $class)
{
    $classNames[] =
    $class['class_name'];

    $studentStmt =
    $db->prepare(

    "SELECT COUNT(*)

     FROM students

     WHERE class_id=?"

    );

    $studentStmt->execute([
        $class['id']
    ]);

    $totalStudents +=
    $studentStmt->fetchColumn();
}

?>

<tr>

<td>

<?= $announcement['id']; ?>

</td>

<td>

<?= htmlspecialchars(
$announcement['title']
); ?>

</td>

<td>

<?= implode(
', ',
$classNames
); ?>

</td>

<td>

<?= $totalStudents; ?>

</td>

<td>

<?= $announcement['created_at']; ?>

</td>

<td>

<button
type="button"
class="btn btn-info btn-sm"
data-bs-toggle="modal"
data-bs-target="#announcement<?= $announcement['id']; ?>">

View

</button>

</td>

</tr>

<div
class="modal fade"
id="announcement<?= $announcement['id']; ?>">

<div class="modal-dialog">

<div class="modal-content">

<div class="modal-header">

<h5 class="modal-title">

<?= htmlspecialchars(
$announcement['title']
); ?>

</h5>

<button
type="button"
class="btn-close"
data-bs-dismiss="modal"></button>

</div>

<div class="modal-body">

<p>

<?= nl2br(
htmlspecialchars(
$announcement['message']
)
); ?>

</p>

<hr>

<strong>

Classes:

</strong>

<?= implode(
', ',
$classNames
); ?>

</div>

<div class="modal-footer">

<button
type="button"
class="btn btn-secondary"
data-bs-dismiss="modal">

Close

</button>

</div>

</div>

</div>

</div>

<?php endforeach; ?>

</tbody>

</table>

<?php endif; ?>

</div>

<?php include '../../app/views/layouts/footer.php'; ?>
