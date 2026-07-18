<?php

session_start();

require_once '../../config/config.php';
require_once '../../app/models/QuestionBank.php';

if(!isset($_GET['id']))
{
    die('Question ID Missing');
}

$questionBank =
new QuestionBank();

$question =
$questionBank->getById(
$_GET['id']
);

if(!$question)
{
    die('Question Not Found');
}

$courses =
$questionBank->getTeacherCourses(
$_SESSION['user_id']
);

include '../../app/views/layouts/header.php';

?>
<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">
   
<h2>✏ Edit Question</h2>

<div class="card">

<div class="card-body">

<form
action="update.php"
method="POST">

<input
type="hidden"
name="id"
value="<?= $question['id']; ?>">

<div class="mb-3">

<label>Course</label>

<select
name="course_id"
class="form-control"
required>

<?php foreach($courses as $course): ?>

<option
value="<?= $course['id']; ?>"
<?= $course['id'] == $question['course_id'] ? 'selected' : ''; ?>>

<?= htmlspecialchars($course['course_name']); ?>

</option>

<?php endforeach; ?>

</select>

</div>

<div class="mb-3">

<label>Category</label>

<input
type="text"
name="category"
class="form-control"
value="<?= htmlspecialchars($question['category']); ?>"
required>

</div>

<div class="mb-3">

<label>Question</label>

<textarea
name="question_text"
class="form-control"
rows="4"
required><?= htmlspecialchars($question['question_text']); ?></textarea>

</div>

<div class="mb-3">

<label>Option A</label>

<input
type="text"
name="option_a"
class="form-control"
value="<?= htmlspecialchars($question['option_a']); ?>"
required>

</div>

<div class="mb-3">

<label>Option B</label>

<input
type="text"
name="option_b"
class="form-control"
value="<?= htmlspecialchars($question['option_b']); ?>"
required>

</div>

<div class="mb-3">

<label>Option C</label>

<input
type="text"
name="option_c"
class="form-control"
value="<?= htmlspecialchars($question['option_c']); ?>">

</div>

<div class="mb-3">

<label>Option D</label>

<input
type="text"
name="option_d"
class="form-control"
value="<?= htmlspecialchars($question['option_d']); ?>">

</div>

<div class="mb-3">

<label>Correct Answer</label>

<select
name="correct_answer"
class="form-control">

<option value="A" <?= $question['correct_answer']=='A'?'selected':''; ?>>A</option>
<option value="B" <?= $question['correct_answer']=='B'?'selected':''; ?>>B</option>
<option value="C" <?= $question['correct_answer']=='C'?'selected':''; ?>>C</option>
<option value="D" <?= $question['correct_answer']=='D'?'selected':''; ?>>D</option>

</select>

</div>

<div class="mb-3">

<label>Marks</label>

<input
type="number"
name="marks"
class="form-control"
value="<?= $question['marks']; ?>"
required>

</div>

<button
type="submit"
class="btn btn-success">

Update Question

</button>

<a
href="index.php"
class="btn btn-secondary">

Back

</a>

</form>

</div>

</div>

</div>

</div>

</div>

<?php include '../../app/views/layouts/footer.php'; ?>
