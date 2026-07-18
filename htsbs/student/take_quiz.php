<?php

session_start();

require_once '../config/config.php';
require_once '../config/database.php';

$db = (new Database())->connect();

$quizId = $_GET['id'];

$stmt = $db->prepare(
    "SELECT *
     FROM quizzes
     WHERE id=?"
);

$stmt->execute([$quizId]);

$quiz = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $db->prepare(
    "SELECT *
     FROM quiz_questions
     WHERE quiz_id=?"
);

$stmt->execute([$quizId]);

$questions =
$stmt->fetchAll(PDO::FETCH_ASSOC);

include '../app/views/layouts/header.php';

?>

<div class="container-fluid">
<div class="row">

<?php include '../app/views/layouts/student_sidebar.php'; ?>
<div class="main-content">



<h2>

<?= htmlspecialchars($quiz['quiz_title']); ?>

</h2>

<form
method="POST"
action="submit_quiz.php">

<input
type="hidden"
name="quiz_id"
value="<?= $quizId; ?>">

<?php foreach($questions as $question): ?>

<div class="card mb-3">

<div class="card-body">

<h5>

<?= htmlspecialchars($question['question_text']); ?>

</h5>

<label>

<input
type="radio"
name="answers[<?= $question['id']; ?>]"
value="A">

<?= htmlspecialchars($question['option_a']); ?>

</label>

<br>

<label>

<input
type="radio"
name="answers[<?= $question['id']; ?>]"
value="B">

<?= htmlspecialchars($question['option_b']); ?>

</label>

<br>

<label>

<input
type="radio"
name="answers[<?= $question['id']; ?>]"
value="C">

<?= htmlspecialchars($question['option_c']); ?>

</label>

<br>

<label>

<input
type="radio"
name="answers[<?= $question['id']; ?>]"
value="D">

<?= htmlspecialchars($question['option_d']); ?>

</label>

</div>

</div>

<?php endforeach; ?>

<button
class="btn btn-success">

Submit Quiz

</button>

</form>

</div>


</div>
</div>
<?php include '../app/views/layouts/footer.php'; ?>
