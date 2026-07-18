<?php

session_start();

require_once '../../config/config.php';
require_once '../../app/models/Lesson.php';

$model = new Lesson();

$courses = $model->getTeacherCourses(
    $_SESSION['user_id']
);

include '../../app/views/layouts/header.php';

?>

<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">
   
<h2>إضافة درس جديد </h2>

<form
    action="store.php"
    method="POST"
    enctype="multipart/form-data">

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

<label>Lesson Title</label>

<input
 type="text"
 name="lesson_title"
 class="form-control"
 required>

</div>

<div class="mb-3">

<label>Description</label>

<textarea
    name="lesson_description"
    class="form-control"></textarea>

</div>

<div class="mb-3">

<label>Lesson Type</label>

<select
 name="lesson_type"
 class="form-control">

<option value="pdf">PDF</option>
<option value="ppt">PowerPoint</option>
<option value="video">Video</option>
<option value="link">External Link</option>

</select>

</div>

<div class="mb-3">

<label>File Upload</label>

<input
 type="file"
 name="lesson_file"
 class="form-control">

</div>

<div class="mb-3">

<label>Video / Link URL</label>

<input
 type="text"
 name="video_link"
 class="form-control">

</div>

<button
 class="btn btn-success">


Save Lesson


</button>

</form>

</div>

</div>

</div>

<?php
include '../../app/views/layouts/footer.php';
?>
