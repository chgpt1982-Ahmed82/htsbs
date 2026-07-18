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
جلب أبناء ولي الأمر
*/

$stmt = $db->prepare(

"SELECT

s.id,
s.class_id,
s.student_number,
u.full_name

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

📅 Academic Calendar

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

$events =
$calendarModel->getParentEvents(
$child['class_id']
);

?>

<?php if(empty($events)): ?>

<div class="alert alert-info">

No Events Available

</div>

<?php else: ?>

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

<div class="card mb-3 border-<?= $badge; ?>">

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

<?php endif; ?>

</div>

</div>

<?php endforeach; ?>

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
