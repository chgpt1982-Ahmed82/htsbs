<?php

session_start();

require_once '../../config/config.php';
require_once '../../app/models/QuestionBank.php';

$questionBank =
new QuestionBank();

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
   

<div class="d-flex justify-content-between align-items-center mb-4">

<h2>

📚 Add Question To Question Bank

</h2>

<a
href="index.php"
class="btn btn-secondary">

Back

</a>

</div>

<div class="card">

<div class="card-body">

<form
action="store.php"
method="POST">

<div class="mb-3">

<label class="form-label">

Course

</label>

<select
name="course_id"
class="form-control"
required>

<option value="">

Select Course

</option>

<?php foreach($courses as $course): ?>

<option
value="<?= $course['id']; ?>">

<?= htmlspecialchars(
$course['course_name']
); ?>

</option>

<?php endforeach; ?>

</select>

</div>

<div class="mb-3">

<label class="form-label">

Category

</label>

<input
type="text"
name="category"
class="form-control"
placeholder="Example: Chapter 1, Loops, Functions"
required>

</div>

<div class="mb-3">

<label class="form-label">

Question

</label>

<textarea
name="question_text"
class="form-control"
rows="4"
required></textarea>

</div>

<div class="row">

<div class="col-md-6">

<div class="mb-3">

<label class="form-label">

Option A

</label>

<input
type="text"
name="option_a"
class="form-control"
required>

</div>

</div>

<div class="col-md-6">

<div class="mb-3">

<label class="form-label">

Option B

</label>

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

<label class="form-label">

Option C

</label>

<input
type="text"
name="option_c"
class="form-control"
required>

</div>

</div>

<div class="col-md-6">

<div class="mb-3">

<label class="form-label">

Option D

</label>

<input
type="text"
name="option_d"
class="form-control"
required>

</div>

</div>

</div>

<div class="row">

<div class="col-md-6">

<div class="mb-3">

<label class="form-label">

Correct Answer

</label>

<select
name="correct_answer"
class="form-control"
required>

<option value="A">A</option>
<option value="B">B</option>
<option value="C">C</option>
<option value="D">D</option>

</select>

</div>

</div>

<div class="col-md-6">

<div class="mb-3">

<label class="form-label">

Marks

</label>

<input
type="number"
name="marks"
value="1"
min="1"
class="form-control"
required>

</div>

</div>

</div>

<button
type="submit"
class="btn btn-success">

💾 Save Question

</button>

</form>

</div>

</div>

</div>

</div>

</div>

<?php include '../../app/views/layouts/footer.php'; ?>
