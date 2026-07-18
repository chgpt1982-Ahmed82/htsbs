<?php

session_start();

require_once '../config/config.php';
require_once '../config/database.php';

$db = (new Database())->connect();

$quizzes =
$db->query(
"SELECT * FROM quizzes ORDER BY id DESC"
)->fetchAll(PDO::FETCH_ASSOC);

include '../app/views/layouts/header.php';

?>

<div class="container-fluid">
<div class="row">

<?php include '../app/views/layouts/student_sidebar.php'; ?>
<div class="main-content">



<h2>Available Quizzes</h2>

<table class="table table-bordered">

<tr>

<th>Quiz</th>
<th>Marks</th>
<th>Action</th>

</tr>

<?php foreach($quizzes as $quiz): ?>

<tr>

<td><?= $quiz['title']; ?></td>

<td><?= $quiz['total_marks']; ?></td>

<td>

<a
href="take_quiz.php?id=<?= $quiz['id']; ?>"
class="btn btn-success">

Start Quiz

</a>

</td>

</tr>

<?php endforeach; ?>

</table>


</div>
</div>
</div>