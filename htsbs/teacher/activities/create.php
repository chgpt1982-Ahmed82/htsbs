<?php

session_start();

require_once '../../config/config.php';
require_once '../../app/models/Activity.php';

$model = new Activity();

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
<h2>إضافة نشاط جديد</h2>

<form action="store.php" method="POST">

<div class="mb-3">

<label>Course</label>

<select
name="course_id"
class="form-control">

<?php foreach($courses as $course): ?>

<option value="<?= $course['id']; ?>">
<?= $course['course_name']; ?>
</option>

<?php endforeach; ?>

</select>

</div>

<div class="mb-3">

<label>Title</label>

<input
type="text"
name="title"
class="form-control"
required>

</div>

<div class="mb-3">

<label>Instructions</label>

<textarea
name="instructions"
class="form-control"></textarea>

</div>

<div class="mb-3">

<label>Max Grade</label>

<input
type="number"
name="max_grade"
value="100"
class="form-control">

</div>

<div class="mb-3">

<label>Due Date</label>

<input
type="date"
name="due_date"
class="form-control">

</div>

<button class="btn btn-success">
Save Activity
</button>

</form>

</div>
</div>
</div>

<?php include '../../app/views/layouts/footer.php'; ?>
