<?php

session_start();

require_once '../../config/config.php';
require_once '../../app/models/Quiz.php';
require_once '../../app/models/QuizQuestion.php';

if(!isset($_GET['id']) || empty($_GET['id']))
{
    die('Quiz ID Missing');
}

$quizModel = new Quiz();
$questionModel = new QuizQuestion();

$quizId = (int)$_GET['id'];

$quiz = $quizModel->getById($quizId);

if(!$quiz)
{
    die('Quiz Not Found');
}

$questions =
$questionModel->getQuestions(
$quizId
);

include '../../app/views/layouts/header.php';

?>
<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">   

<h2>

📝 Quiz Questions

<br>

<small class="text-muted">

<?= htmlspecialchars($quiz['title']); ?>

</small>

</h2>

<hr>

<div class="card mb-4">

<div class="card-header">

Add New Question

</div>

<div class="card-body">

<form method="POST"
      action="store_question.php">

<input
type="hidden"
name="quiz_id"
value="<?= $quiz['id']; ?>">

<div class="mb-3">

<label>Question</label>

<textarea
name="question"
class="form-control"
required></textarea>

</div>

<div class="row">

<div class="col-md-6">

<div class="mb-3">

<label>Option A</label>

<input
type="text"
name="option_a"
class="form-control"
required>

</div>

</div>

<div class="col-md-6">

<div class="mb-3">

<label>Option B</label>

<input
type="text"
name="option_b"
class="form-control"
required>

</div>

</div>

</div>

<div class="row">

<div class="col-md-6">

<div class="mb-3">

<label>Option C</label>

<input
type="text"
name="option_c"
class="form-control">

</div>

</div>

<div class="col-md-6">

<div class="mb-3">

<label>Option D</label>

<input
type="text"
name="option_d"
class="form-control">

</div>

</div>

</div>

<div class="row">

<div class="col-md-6">

<div class="mb-3">

<label>Correct Answer</label>

<select
name="correct_answer"
class="form-control">

<option value="A">A</option>
<option value="B">B</option>
<option value="C">C</option>
<option value="D">D</option>

</select>

</div>

</div>

<div class="col-md-6">

<div class="mb-3">

<label>Marks</label>

<input
type="number"
name="marks"
value="1"
min="1"
class="form-control">

</div>

</div>

</div>

<button
type="submit"
class="btn btn-success">

➕ Add Question

</button>

<a
href="index.php"
class="btn btn-secondary">

Back

</a>

</form>

</div>

</div>

<hr>

<h4>

Questions List

</h4>

<table class="table table-bordered table-striped">

<thead class="table-dark">

<tr>

<th>#</th>

<th>Question</th>

<th>Correct Answer</th>

<th>Marks</th>

<th>Action</th>

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
$question['question_text']
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
href="delete_question.php?id=<?= $question['id']; ?>&quiz_id=<?= $quiz['id']; ?>"
class="btn btn-danger btn-sm"
onclick="return confirm('Delete Question?')">

Delete

</a>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

</div>

</div>

<?php include '../../app/views/layouts/footer.php'; ?>
