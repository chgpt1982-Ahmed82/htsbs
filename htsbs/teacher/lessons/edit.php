<?php

session_start();

require_once '../../config/config.php';
require_once '../../app/models/Lesson.php';

$model = new Lesson();

$lesson = $model->getById($_GET['id']);

$courses = $model->getTeacherCourses(
    $_SESSION['user_id']
);

include '../../app/views/layouts/header.php';

?>
<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">
   
<h2>Edit Lesson</h2>

<form method="POST"
      action="update.php?id=<?= $lesson['id']; ?>">

<div class="mb-3">

<label>Course</label>

<select
name="course_id"
class="form-control">

<?php foreach($courses as $course): ?>

<option
value="<?= $course['id']; ?>"
<?= ($course['id']==$lesson['course_id']) ? 'selected' : ''; ?>>

<?= htmlspecialchars($course['course_name']); ?>

</option>

<?php endforeach; ?>

</select>

</div>

<div class="mb-3">

<label>Lesson Title</label>

<input
type="text"
name="lesson_title"
value="<?= htmlspecialchars($lesson['lesson_title']); ?>"
class="form-control">

</div>

<div class="mb-3">

<label>Description</label>

<textarea
name="lesson_description"
class="form-control"
rows="5"><?= htmlspecialchars($lesson['lesson_description']); ?></textarea>

</div>

<div class="mb-3">

<label>Lesson Type</label>

<select
name="lesson_type"
class="form-control">

<option value="pdf"
<?= ($lesson['lesson_type']=='pdf') ? 'selected' : ''; ?>>
PDF
</option>

<option value="ppt"
<?= ($lesson['lesson_type']=='ppt') ? 'selected' : ''; ?>>
PPT
</option>

<option value="video"
<?= ($lesson['lesson_type']=='video') ? 'selected' : ''; ?>>
Video
</option>

<option value="link"
<?= ($lesson['lesson_type']=='link') ? 'selected' : ''; ?>>
Link
</option>

</select>

</div>

<div class="mb-3">

<label>Video Link</label>

<input
type="text"
name="video_link"
value="<?= htmlspecialchars($lesson['video_link']); ?>"
class="form-control">

</div>

<button class="btn btn-success">

Update Lesson

</button>

</form>

</div>
</div>
</div>

<?php include '../../app/views/layouts/footer.php'; ?>
