<?php

require_once '../../config/config.php';
require_once '../../app/models/CourseAssignment.php';

$model = new CourseAssignment();

$teachers = $model->getTeachers();
$courses  = $model->getCourses();
$classes  = $model->getClasses();

include '../../app/views/layouts/header.php';

?>

<div class="container-fluid">
<div class="row flex-lg-row-reverse">
<?php include '../../app/views/layouts/sidebar.php'; ?>
<div class="col-md-10 p-4">

 
 
<h2>Add Course Assignment</h2>

<form method="POST" action="store.php">


<div class="mb-3">

    <label>Teacher</label>

    <select
        name="teacher_id"
        class="form-control">

        <?php foreach($teachers as $teacher): ?>

        <option value="<?= $teacher['id']; ?>">

            <?= $teacher['full_name']; ?>

        </option>

        <?php endforeach; ?>

    </select>

</div>

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

    <label>Class</label>

    <select
        name="class_id"
        class="form-control">

        <?php foreach($classes as $class): ?>

        <option value="<?= $class['id']; ?>">

            <?= $class['class_name']; ?>

        </option>

        <?php endforeach; ?>

    </select>

</div>

<div class="mb-3">

    <label>Semester</label>

    <select
        name="semester"
        class="form-control">

        <option>First Semester</option>
        <option>Second Semester</option>
        <option>Summer</option>

    </select>

</div>

<div class="mb-3">

    <label>Academic Year</label>

    <input
        type="text"
        name="academic_year"
        class="form-control"
        placeholder="2025/2026">

</div>

<button class="btn btn-success">

    Save Assignment

</button>


</form>

<?php

include '../../app/views/layouts/footer.php';

?>
