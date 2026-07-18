<?php

require_once '../../config/config.php';
require_once '../../app/models/CourseAssignment.php';

$model = new CourseAssignment();

$assignments = $model->getAll();

include '../../app/views/layouts/header.php';

?>

<div class="container-fluid">
<div class="row flex-lg-row-reverse">
<?php include '../../app/views/layouts/sidebar.php'; ?>
<div class="col-md-10 p-4">


<div class="d-flex justify-content-between mb-4">

<h2>Course Assignments</h2>

<a href="create.php"
   class="btn btn-primary">

    Add Assignment

</a>


</div>

<table class="table table-bordered">


<thead>

<tr>

    <th>Teacher</th>
    <th>Course</th>
    <th>Class</th>
    <th>Semester</th>
    <th>Academic Year</th>
    <th>Actions</th>

</tr>

</thead>

<tbody>

<?php foreach($assignments as $item): ?>

<tr>

    <td><?= htmlspecialchars($item['full_name']); ?></td>

    <td><?= htmlspecialchars($item['course_name']); ?></td>

    <td><?= htmlspecialchars($item['class_name']); ?></td>

    <td><?= htmlspecialchars($item['semester']); ?></td>

    <td><?= htmlspecialchars($item['academic_year']); ?></td>

    <td>

       
        <a href="edit.php?id=<?= $item['id']; ?>"
           class="btn btn-warning btn-sm">
            Edit
        </a>
        
         <a href="delete.php?id=<?= $item['id']; ?>"
           class="btn btn-danger btn-sm"
           onclick="return confirm('Delete Assignment?')">

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
