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
جلب الطالب الحالي
*/

$stmt = $db->prepare(

"SELECT

id,
student_number

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
    die(
    'Student Not Found'
    );
}

/*
نتائج الطالب
*/

$results =
$examModel->getStudentResults(
$student['id']
);

include '../../app/views/layouts/header.php';

?>
<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/student_sidebar.php'; ?>
<div class="col-md-10 p-4">

<h2 class="mb-4">

My Exam Results

</h2>

<?php if(empty($results)): ?>

<div class="alert alert-info">

No Exam Results Available

</div>

<?php else: ?>

<table class="table table-bordered table-striped">

<thead>

<tr>

<th>Exam</th>

<th>Type</th>

<th>Date</th>

<th>Marks</th>

<th>Maximum</th>

<th>Percentage</th>

<th>Remarks</th>

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
($result['marks'] /
$result['max_marks']) * 100,
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

<?= htmlspecialchars(
$result['remarks'] ?? ''
); ?>

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
<?php include '../../app/views/layouts/footer.php'; ?>
