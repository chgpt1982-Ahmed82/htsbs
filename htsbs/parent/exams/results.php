<?php

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';

require_once '../../app/models/Exam.php';
require_once '../../app/models/Notification.php';

$db = (new Database())->connect();

$examModel =
new Exam();

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

<h2>

Children Exam Results

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

$results =
$examModel->getStudentResults(
$child['id']
);

?>

<?php if(empty($results)): ?>

<div class="alert alert-info">

No Exam Results Found

</div>

<?php else: ?>

<table class="table table-bordered">

<thead>

<tr>

<th>Exam</th>

<th>Type</th>

<th>Date</th>

<th>Marks</th>

<th>Maximum</th>

<th>Percentage</th>

<th>Status</th>

</tr>

</thead>

<tbody>

<?php foreach($results as $result): ?>

<?php

$percentage =
$result['max_marks'] > 0
?
round(
($result['marks']
/
$result['max_marks'])
* 100,
2
)
:
0;

?>

<tr>

<td>

<?= htmlspecialchars(
$result['exam_name']
); ?>

</td>

<td>

<?= htmlspecialchars(
$result['exam_type']
); ?>

</td>

<td>

<?= $result['exam_date']; ?>

</td>

<td>

<?= $result['marks']; ?>

</td>

<td>

<?= $result['max_marks']; ?>

</td>

<td>

<?= $percentage; ?>%

</td>

<td>

<?php if($percentage >= 50): ?>

<span class="badge bg-success">

Passed

</span>

<?php else: ?>

<span class="badge bg-danger">

Failed

</span>

<?php endif; ?>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

<?php endif; ?>

</div>

</div>

<?php endforeach; ?>

<?php endif; ?>

</div>

<?php include '../../app/views/layouts/footer.php'; ?>
