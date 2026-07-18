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

$examId =
$_GET['id'] ?? 0;

$exam =
$examModel->find(
$examId
);

if(!$exam)
{
    die(
    'Exam Not Found'
    );
}

$results =
$examModel->getResults(
$examId
);

/*
إحصائيات
*/

$totalStudents =
count($results);

$totalMarks = 0;

$highest = 0;

$lowest = null;

$passed = 0;

foreach($results as $result)
{
    $mark =
    $result['marks'];

    $totalMarks += $mark;

    if($mark > $highest)
    {
        $highest = $mark;
    }

    if(
    $lowest === null
    ||
    $mark < $lowest
    )
    {
        $lowest = $mark;
    }

    if(
    $mark >=
    ($exam['max_marks'] * 0.50)
    )
    {
        $passed++;
    }
}

$average =
$totalStudents > 0
?
round(
$totalMarks / $totalStudents,
2
)
:
0;

$passRate =
$totalStudents > 0
?
round(
($passed / $totalStudents)
* 100,
2
)
:
0;

include '../../app/views/layouts/header.php';

?>
<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">   
<h2>

Exam Report

</h2>

<?php if(isset($_GET['success'])): ?>

<div class="alert alert-success">

Exam Results Saved Successfully

</div>

<?php endif; ?>

<div class="card mb-4">

<div class="card-body">

<h4>

<?= htmlspecialchars(
$exam['exam_name']
); ?>

</h4>

<p>

Type:

<?= $exam['exam_type']; ?>

</p>

<p>

Date:

<?= $exam['exam_date']; ?>

</p>

<p>

Maximum Marks:

<?= $exam['max_marks']; ?>

</p>

</div>

</div>

<div class="row mb-4">

<div class="col-md-3">

<div class="card text-center">

<div class="card-body">

<h3>

<?= $totalStudents; ?>

</h3>

<p>

Students

</p>

</div>

</div>

</div>

<div class="col-md-3">

<div class="card text-center">

<div class="card-body">

<h3>

<?= $average; ?>

</h3>

<p>

Average

</p>

</div>

</div>

</div>

<div class="col-md-3">

<div class="card text-center">

<div class="card-body">

<h3>

<?= $highest; ?>

</h3>

<p>

Highest

</p>

</div>

</div>

</div>

<div class="col-md-3">

<div class="card text-center">

<div class="card-body">

<h3>

<?= $passRate; ?>%

</h3>

<p>

Pass Rate

</p>

</div>

</div>

</div>

</div>

<table class="table table-bordered table-striped">

<thead>

<tr>

<th>Student</th>

<th>Marks</th>

<th>Remarks</th>

</tr>

</thead>

<tbody>

<?php foreach($results as $result): ?>

<tr>

<td>

<?= htmlspecialchars(
$result['full_name']
); ?>

</td>

<td>

<?= $result['marks']; ?>

/

<?= $exam['max_marks']; ?>

</td>

<td>

<?= htmlspecialchars(
$result['remarks'] ?? ''
); ?>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

<a
href="mark.php?id=<?= $examId; ?>"
class="btn btn-primary">

Edit Marks

</a>

</div>

<?php include '../../app/views/layouts/footer.php'; ?>
