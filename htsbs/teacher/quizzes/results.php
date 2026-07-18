<?php

session_start();

require_once '../../config/config.php';
require_once '../../app/models/Quiz.php';

if(!isset($_GET['id']))
{
    die('Quiz ID Missing');
}

$quizModel = new Quiz();

$quizId = (int)$_GET['id'];

$quiz = $quizModel->getById($quizId);

if(!$quiz)
{
    die('Quiz Not Found');
}

$results =
$quizModel->getResults(
$quizId
);

include '../../app/views/layouts/header.php';

?>

<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">   

<div class="d-flex justify-content-between mb-4">

<div>

<h2>

📊 Quiz Results

</h2>

<h5 class="text-muted">

<?= htmlspecialchars(
$quiz['title']
); ?>

</h5>

</div>

<a
href="index.php"
class="btn btn-secondary">

Back

</a>

</div>

<?php if(empty($results)): ?>

<div class="alert alert-info">

No student has completed this quiz yet.

</div>

<?php else: ?>

<div class="card">

<div class="card-body">

<table class="table table-bordered table-striped">

<thead class="table-dark">

<tr>

<th>#</th>

<th>Student</th>

<th>Score</th>

<th>Attempt</th>

<th>Started At</th>

<th>Completed At</th>

</tr>

</thead>

<tbody>

<?php

$counter = 1;

foreach($results as $row):

?>

<tr>

<td>

<?= $counter++; ?>

</td>

<td>

<?= htmlspecialchars(
$row['full_name']
); ?>

</td>

<td>

<?= $row['score']; ?>

</td>

<td>

<?= $row['attempt_number']; ?>

</td>

<td>

<?= $row['started_at']; ?>

</td>

<td>

<?= $row['completed_at']; ?>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

</div>

<?php

$totalStudents =
count($results);

$totalScore = 0;

$highestScore = 0;

foreach($results as $result)
{
    $totalScore +=
    $result['score'];

    if(
    $result['score']
    >
    $highestScore
    )
    {
        $highestScore =
        $result['score'];
    }
}

$averageScore =
$totalStudents > 0
?
round(
$totalScore / $totalStudents,
2
)
:
0;

?>

<div class="row mt-4">

<div class="col-md-4">

<div class="card text-center">

<div class="card-body">

<h3>

<?= $totalStudents; ?>

</h3>

<p>

Students Completed

</p>

</div>

</div>

</div>

<div class="col-md-4">

<div class="card text-center">

<div class="card-body">

<h3>

<?= $averageScore; ?>

</h3>

<p>

Average Score

</p>

</div>

</div>

</div>

<div class="col-md-4">

<div class="card text-center">

<div class="card-body">

<h3>

<?= $highestScore; ?>

</h3>

<p>

Highest Score

</p>

</div>

</div>

</div>

</div>

<?php endif; ?>

</div>

</div>

</div>

<?php include '../../app/views/layouts/footer.php'; ?>
