<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

//echo "START";

//exit;



require_once '../../config/config.php';


require_once '../../app/models/course.php';
//echo "Course ok";
//exit;
$courseModel = new Course();

$courses = $courseModel->getAll();

include '../../app/views/layouts/header.php';
?>

<div class="container-fluid">
<div class="row flex-lg-row-reverse">
<?php include '../../app/views/layouts/sidebar.php'; ?>
<div class="col-md-10 p-4">


<h2>Courses</h2>

<a href="create.php"
   class="btn btn-primary mb-3">
   Add Course
</a>

<table class="table table-bordered">

<thead>
<tr>
    <th>ID</th>
    <th>Course Name</th>
    <th>Course Code</th>
    <th>Credit Hours</th>
    <th>Actions</th>
</tr>
</thead>

<tbody>

<?php foreach($courses as $course): ?>

<tr>

    <td><?= $course['id']; ?></td>

    <td><?= htmlspecialchars($course['course_name']); ?></td>

    <td><?= htmlspecialchars($course['course_code']); ?></td>

    <td><?= $course['credit_hours']; ?></td>
    <td>

    <a href="edit.php?id=<?= $course['id']; ?>"
       class="btn btn-warning btn-sm">
       Edit
    </a>

    <a href="delete.php?id=<?= $course['id']; ?>"
       class="btn btn-danger btn-sm"
       onclick="return confirm('Delete this course?')">
       Delete
    </a>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

<?php
include '../../app/views/layouts/footer.php';
?>