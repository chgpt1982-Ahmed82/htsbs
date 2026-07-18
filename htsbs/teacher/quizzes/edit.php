<?php

session_start();

require_once '../../config/config.php';
require_once '../../app/models/Quiz.php';

$model = new Quiz();

$quiz = $model->getById($_GET['id']);

$courses = $model->getTeacherCourses(
    $_SESSION['user_id']
);

$classes = $model->getClasses();

include '../../app/views/layouts/header.php';

?>
<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">   
<h2>Edit Quiz</h2>

<form method="POST"
      action="update.php?id=<?= $quiz['id']; ?>">

<div class="mb-3">

<label>Course</label>

<select name="course_id" class="form-control">

<?php foreach($courses as $course): ?>

<option
value="<?= $course['id']; ?>"
<?= ($course['id']==$quiz['course_id']) ? 'selected' : ''; ?>>

<?= htmlspecialchars($course['course_name']); ?>

</option>

<?php endforeach; ?>

</select>

</div>

<div class="mb-3">

<label>Class</label>

<select name="class_id" class="form-control">

<?php foreach($classes as $class): ?>

<option
value="<?= $class['id']; ?>"
<?= ($class['id']==$quiz['class_id']) ? 'selected' : ''; ?>>

<?= htmlspecialchars($class['class_name']); ?>

</option>

<?php endforeach; ?>

</select>

</div>

<div class="mb-3">

<label>Quiz Title</label>

<input
type="text"
name="title"
value="<?= htmlspecialchars($quiz['title']); ?>"
class="form-control"
required>

</div>

<div class="mb-3">

<label>Duration (Minutes)</label>

<input
type="number"
name="duration_minutes"
value="<?= $quiz['duration_minutes']; ?>"
class="form-control">

</div>

<div class="mb-3">

<label>Total Marks</label>

<input
type="number"
name="total_marks"
value="<?= $quiz['total_marks']; ?>"
class="form-control">

</div>

<div class="mb-3">

<label>Attempts Allowed</label>

<input
type="number"
name="attempts_allowed"
value="<?= $quiz['attempts_allowed']; ?>"
class="form-control">

</div>

<div class="mb-3">

<label>Start Date</label>

<input
type="datetime-local"
name="start_date"
value="<?= date('Y-m-d\TH:i', strtotime($quiz['start_date'])); ?>"
class="form-control">

</div>

<div class="mb-3">

<label>End Date</label>

<input
type="datetime-local"
name="end_date"
value="<?= date('Y-m-d\TH:i', strtotime($quiz['end_date'])); ?>"
class="form-control">

</div>

<div class="mb-3">

<label>Published</label>

<select
name="is_published"
class="form-control">

<option value="1"
<?= $quiz['is_published'] ? 'selected' : ''; ?>>
Published
</option>

<option value="0"
<?= !$quiz['is_published'] ? 'selected' : ''; ?>>
Draft
</option>

</select>

</div>

<button class="btn btn-success">
Update Quiz
</button>

</form>

</div>
</div>
</div>

<?php include '../../app/views/layouts/footer.php'; ?>
