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
جلب الطالب
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
امتحانات الصف
*/

$exams =
$examModel->getStudentExams(
$student['class_id']
);

include '../../app/views/layouts/header.php';

?>
<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/student_sidebar.php'; ?>
<div class="col-md-10 p-4">

<div class="d-flex justify-content-between align-items-center mb-4">

<h2>

My Exams

</h2>

<a
href="results.php"
class="btn btn-success">

📊 My Results

</a>

</div>

<?php if(empty($exams)): ?>

<div class="alert alert-info">

No Exams Available

</div>

<?php else: ?>

<table class="table table-bordered table-striped">

<thead>

<tr>

<th>Exam</th>

<th>Course</th>

<th>Type</th>

<th>Date</th>

<th>Maximum Marks</th>

</tr>

</thead>

<tbody>

<?php foreach($exams as $exam): ?>

<tr>

<td>

<?= htmlspecialchars(
$exam['exam_name']
); ?>

</td>

<td>

<?= htmlspecialchars(
$exam['course_name']
); ?>

</td>

<td>

<?= htmlspecialchars(
$exam['exam_type']
); ?>

</td>

<td>

<?= $exam['exam_date']; ?>

</td>

<td>

<?= $exam['max_marks']; ?>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

<?php endif; ?>

</div>

<?php include '../../app/views/layouts/footer.php'; ?>
