<?php

require_once '../../config/config.php';
require_once '../../app/models/CourseAssignment.php';

$model = new CourseAssignment();

$assignment = $model->getById($_GET['id']);

$teachers = $model->getTeachers();
$courses  = $model->getCourses();
$classes  = $model->getClasses();

include '../../app/views/layouts/header.php';

?>


<div class="container-fluid">
<div class="row flex-lg-row-reverse">
<?php include '../../app/views/layouts/sidebar.php'; ?>
<div class="col-md-10 p-4">


<h2>Edit Course Assignment</h2>

<form method="POST"
      action="update.php?id=<?= $assignment['id']; ?>">


<div class="mb-3">

    <label>Teacher</label>

    <select
        name="teacher_id"
        class="form-control">

        <?php foreach($teachers as $teacher): ?>

        <option
            value="<?= $teacher['id']; ?>"
            <?= ($teacher['id'] == $assignment['teacher_id']) ? 'selected' : ''; ?>>

            <?= htmlspecialchars($teacher['full_name']); ?>

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

        <option
            value="<?= $course['id']; ?>"
            <?= ($course['id'] == $assignment['course_id']) ? 'selected' : ''; ?>>

            <?= htmlspecialchars($course['course_name']); ?>

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

        <option
            value="<?= $class['id']; ?>"
            <?= ($class['id'] == $assignment['class_id']) ? 'selected' : ''; ?>>

            <?= htmlspecialchars($class['class_name']); ?>

        </option>

        <?php endforeach; ?>

    </select>

</div>

<div class="mb-3">

    <label>Semester</label>

    <select
        name="semester"
        class="form-control">

        <option value="First Semester"
            <?= ($assignment['semester'] == 'First Semester') ? 'selected' : ''; ?>>
            First Semester
        </option>

        <option value="Second Semester"
            <?= ($assignment['semester'] == 'Second Semester') ? 'selected' : ''; ?>>
            Second Semester
        </option>

        <option value="Summer"
            <?= ($assignment['semester'] == 'Summer') ? 'selected' : ''; ?>>
            Summer
        </option>

    </select>

</div>

<div class="mb-3">

    <label>Academic Year</label>

    <input
        type="text"
        name="academic_year"
        value="<?= htmlspecialchars($assignment['academic_year']); ?>"
        class="form-control">

</div>

<button class="btn btn-success">

    Update Assignment

</button>


</form>

<?php

include '../../app/views/layouts/footer.php';

?>
