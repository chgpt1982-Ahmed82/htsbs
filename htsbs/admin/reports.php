<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../config/config.php';
require_once '../config/database.php';

$db = (new Database())->connect();

$totalStudents =
$db->query(
"SELECT COUNT(*) total FROM students"
)->fetch(PDO::FETCH_ASSOC)['total'];

$totalTeachers =
$db->query(
"SELECT COUNT(*) total FROM teachers"
)->fetch(PDO::FETCH_ASSOC)['total'];

$totalCourses =
$db->query(
"SELECT COUNT(*) total FROM courses"
)->fetch(PDO::FETCH_ASSOC)['total'];

$totalQuizzes =
$db->query(
"SELECT COUNT(*) total FROM quizzes"
)->fetch(PDO::FETCH_ASSOC)['total'];

$avgScore =
$db->query(
"SELECT ROUND(AVG(score),2) avg_score
 FROM quiz_attempts"
)->fetch(PDO::FETCH_ASSOC)['avg_score'];

include '../app/views/layouts/header.php';
?>


<div class="container-fluid">
<div class="row flex-lg-row-reverse">
<?php include '../app/views/layouts/sidebar.php'; ?>
<div class="col-md-10 p-4">

<h2>System Reports</h2>

<div class="row">

<div class="col-md-3">

<div class="card">

<div class="card-body">

<h5>Students</h5>

<h2><?= $totalStudents; ?></h2>

</div>

</div>

</div>

<div class="col-md-3">

<div class="card">

<div class="card-body">

<h5>Teachers</h5>

<h2><?= $totalTeachers; ?></h2>

</div>

</div>

</div>

<div class="col-md-3">

<div class="card">

<div class="card-body">

<h5>Courses</h5>

<h2><?= $totalCourses; ?></h2>

</div>

</div>

</div>

<div class="col-md-3">

<div class="card">

<div class="card-body">

<h5>Quizzes</h5>

<h2><?= $totalQuizzes; ?></h2>

</div>

</div>

</div>

</div>

<hr>

<div class="card">

<div class="card-body">

<h4>Overall Average Score</h4>

<h2><?= $avgScore; ?>%</h2>

</div>

</div>

</div>

<?php include '../app/views/layouts/footer.php'; ?>
