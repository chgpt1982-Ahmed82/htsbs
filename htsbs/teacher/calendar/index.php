<?php

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';

require_once '../../app/models/Calendar.php';
require_once '../../app/models/Notification.php';

$db = (new Database())->connect();

$calendarModel =
new Calendar();

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
أحداث المعلم
*/

$events =
$calendarModel->getTeacherEvents(
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

📅 Academic Calendar

</h2>

<a
href="create.php"
class="btn btn-primary">

➕ Create Event

</a>

</div>

<?php if(isset($_GET['success'])): ?>

<div class="alert alert-success">

Event Published Successfully

</div>

<?php endif; ?>

<?php if(empty($events)): ?>

<div class="alert alert-info">

No Calendar Events Found

</div>

<?php else: ?>

<table class="table table-bordered table-striped">

<thead class="table-dark">

<tr>

<th>ID</th>

<th>Title</th>

<th>Type</th>

<th>Start Date</th>

<th>End Date</th>

<th>Target Classes</th>

<th>Students</th>

<th>Details</th>

</tr>

</thead>

<tbody>

<?php foreach($events as $event): ?>

<?php

$classes =
$calendarModel->getEventClasses(
$event['id']
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

<?= $event['id']; ?>

</td>

<td>

<?= htmlspecialchars(
$event['title']
); ?>

</td>

<td>

<?php

switch($event['event_type'])
{
    case 'Exam':
        echo '🧪 Exam';
        break;

    case 'Assignment':
        echo '📝 Assignment';
        break;

    case 'Announcement':
        echo '📢 Announcement';
        break;

    case 'Holiday':
        echo '🏖 Holiday';
        break;

    default:
        echo '🎉 Event';
}

?>

</td>

<td>

<?= $event['start_date']; ?>

</td>

<td>

<?= $event['end_date']; ?>

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

<button
type="button"
class="btn btn-info btn-sm"
data-bs-toggle="modal"
data-bs-target="#event<?= $event['id']; ?>">

View

</button>

</td>

</tr>

<div
class="modal fade"
id="event<?= $event['id']; ?>"
tabindex="-1">

<div class="modal-dialog modal-lg">

<div class="modal-content">

<div class="modal-header">

<h5 class="modal-title">

<?= htmlspecialchars(
$event['title']
); ?>

</h5>

<button
type="button"
class="btn-close"
data-bs-dismiss="modal">

</button>

</div>

<div class="modal-body">

<p>

<strong>

Type:

</strong>

<?= $event['event_type']; ?>

</p>

<p>

<strong>

Start Date:

</strong>

<?= $event['start_date']; ?>

</p>

<p>

<strong>

End Date:

</strong>

<?= $event['end_date']; ?>

</p>

<p>

<strong>

Target Classes:

</strong>

<?= implode(
', ',
$classNames
); ?>

</p>

<hr>

<p>

<?= nl2br(

htmlspecialchars(
$event['description']
)

); ?>

</p>

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

<hr>

<h4>

📌 Upcoming Events

</h4>

<?php

$upcoming =
$calendarModel->getUpcomingEvents(
10
);

?>

<?php if(empty($upcoming)): ?>

<div class="alert alert-warning">

No Upcoming Events

</div>

<?php else: ?>

<table class="table table-sm table-bordered">

<thead>

<tr>

<th>Title</th>

<th>Type</th>

<th>Date</th>

</tr>

</thead>

<tbody>

<?php foreach($upcoming as $event): ?>

<tr>

<td>

<?= htmlspecialchars(
$event['title']
); ?>

</td>

<td>

<?= $event['event_type']; ?>

</td>

<td>

<?= $event['start_date']; ?>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

<?php endif; ?>

</div>

<?php include '../../app/views/layouts/footer.php'; ?>
