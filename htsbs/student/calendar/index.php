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
جلب الطالب الحالي
*/

$stmt = $db->prepare(

"SELECT

id,
class_id

FROM students

WHERE user_id=?"

);

$stmt->execute([
$_SESSION['user_id']
]);

$student =
$stmt->fetch(PDO::FETCH_ASSOC);

if(!$student)
{
    die('Student Not Found');
}

/*
أحداث الصف
*/

$events =
$calendarModel->getStudentEvents(
$student['class_id']
);

include '../../app/views/layouts/header.php';

?>
<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/student_sidebar.php'; ?>
<div class="main-content">



<div class="d-flex justify-content-between align-items-center mb-4">

<h2>

📅 My Academic Calendar

</h2>

<span class="badge bg-primary">

<?= count($events); ?>

Events

</span>

</div>

<?php if(empty($events)): ?>

<div class="alert alert-info">

No Calendar Events Available

</div>

<?php else: ?>

<div class="timeline">

<?php foreach($events as $event): ?>

<?php

$badge = 'secondary';

$icon = '📅';

switch($event['event_type'])
{
    case 'Exam':
        $badge = 'danger';
        $icon = '🧪';
        break;

    case 'Assignment':
        $badge = 'warning';
        $icon = '📝';
        break;

    case 'Announcement':
        $badge = 'primary';
        $icon = '📢';
        break;

    case 'Holiday':
        $badge = 'success';
        $icon = '🏖';
        break;

    case 'Event':
        $badge = 'info';
        $icon = '🎉';
        break;
}

?>

<div class="card mb-4 shadow-sm">

<div class="card-header">

<div class="d-flex justify-content-between">

<div>

<span
class="badge bg-<?= $badge; ?>">

<?= $icon; ?>

<?= htmlspecialchars(
$event['event_type']
); ?>

</span>

</div>

<div>

<?= date(

'd/m/Y',

strtotime(
$event['start_date']
)

); ?>

</div>

</div>

</div>

<div class="card-body">

<h5>

<?= htmlspecialchars(
$event['title']
); ?>

</h5>

<p>

<?= nl2br(

htmlspecialchars(
$event['description']
)

); ?>

</p>

</div>

<div class="card-footer">

<strong>

Start:

</strong>

<?= $event['start_date']; ?>

  

<strong>

End:

</strong>

<?= $event['end_date']; ?>

</div>

</div>

<?php endforeach; ?>

</div>

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

<table class="table table-bordered">

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

<?= htmlspecialchars(
$event['event_type']
); ?>

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
