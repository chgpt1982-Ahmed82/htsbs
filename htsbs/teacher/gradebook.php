<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);


session_start();
require_once '../config/config.php';
require_once '../config/database.php';

$db = (new Database())->connect();

$userId = $_SESSION['user_id'];

$sql = "

SELECT

u.full_name,
s.student_number,

q.title,

qa.score,

qa.total_questions,

qa.submitted_at

FROM quiz_attempts qa

INNER JOIN students s
ON qa.student_id=s.id

INNER JOIN users u
ON s.user_id=u.id

INNER JOIN quizzes q
ON qa.quiz_id=q.id

INNER JOIN teachers t
ON q.teacher_id=t.id

WHERE t.user_id=?

ORDER BY qa.id DESC

";

$stmt = $db->prepare($sql);

$stmt->execute([$userId]);

$results =
$stmt->fetchAll(PDO::FETCH_ASSOC);

include '../app/views/layouts/header.php';

?>
<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">
<h2>Gradebook</h2>

<table class="table table-bordered">

<tr>

<th>Student</th>
<th>Student No</th>
<th>Quiz</th>
<th>Score %</th>
<th>Questions</th>
<th>Date</th>

</tr>

<?php foreach($results as $row): ?>

<tr>

<td><?= htmlspecialchars($row['full_name']); ?></td>

<td><?= $row['student_number']; ?></td>

<td><?= htmlspecialchars($row['title']); ?></td>

<td><?= $row['score']; ?>%</td>

<td><?= $row['total_questions']; ?></td>

<td><?= $row['submitted_at']; ?></td>

</tr>

<?php endforeach; ?>

</table>

<a
href="export_excel.php"
class="btn btn-success">

Export Excel

</a>

</div>

<?php include '../app/views/layouts/footer.php'; ?>
