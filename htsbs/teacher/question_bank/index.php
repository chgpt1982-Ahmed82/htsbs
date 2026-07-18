<?php

session_start();

require_once '../../config/config.php';
require_once '../../app/models/QuestionBank.php';
require_once '../../app/models/Notification.php';

$questionBank =
new QuestionBank();

$notificationModel =
new Notification();

$count =
$notificationModel->unreadCount(
$_SESSION['user_id']
);

$questions =
$questionBank->getAllByTeacher(
$_SESSION['user_id']
);

include '../../app/views/layouts/header.php';

?>
<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">
   
<div class="d-flex justify-content-between align-items-center mb-4">

<h2>

📚 Question Bank

</h2>

<a
href="create.php"
class="btn btn-primary">

➕ Add Question

</a>

</div>

<?php if(empty($questions)): ?>

<div class="alert alert-info">

No Questions Found

</div>

<?php else: ?>

<div class="card">

<div class="card-body">

<table class="table table-bordered table-striped">

<thead class="table-dark">

<tr>

<th>ID</th>

<th>Course</th>

<th>Category</th>

<th>Question</th>

<th>Correct</th>

<th>Marks</th>

<th>Actions</th>

</tr>

</thead>

<tbody>

<?php foreach($questions as $question): ?>

<tr>

<td>

<?= $question['id']; ?>

</td>

<td>

<?= htmlspecialchars(
$question['course_name']
); ?>

</td>

<td>

<?= htmlspecialchars(
$question['category']
); ?>

</td>

<td>

<?= htmlspecialchars(
mb_strimwidth(
$question['question_text'],
0,
80,
'...'
)
); ?>

</td>

<td>

<?= $question['correct_answer']; ?>

</td>

<td>

<?= $question['marks']; ?>

</td>

<td>

<a
href="edit.php?id=<?= $question['id']; ?>"
class="btn btn-warning btn-sm">

Edit

</a>

<a
href="delete.php?id=<?= $question['id']; ?>"
class="btn btn-danger btn-sm"
onclick="return confirm('Delete this question?')">

Delete

</a>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

</div>

<?php endif; ?>

</div>

</div>

</div>

<?php include '../../app/views/layouts/footer.php'; ?>
