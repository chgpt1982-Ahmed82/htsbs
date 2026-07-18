<?php

require_once '../../config/config.php';
require_once '../../app/models/Teacher.php';

$teacherModel = new Teacher();

$teachers = $teacherModel->getAll();

include '../../app/views/layouts/header.php';

?>

<div class="container-fluid">
<div class="row flex-lg-row-reverse">
<?php include '../../app/views/layouts/sidebar.php'; ?>
<div class="col-md-10 p-4">

<div class="d-flex justify-content-between mb-4">

<h2>Teachers</h2>

<a href="create.php"
   class="btn btn-primary">

    Add Teacher

</a>


</div>

<table class="table table-bordered">


<thead>

<tr>

    <th>Name</th>
    <th>Email</th>
    <th>Phone</th>
    <th>Department</th>
    <th>Specialization</th>
    <th>Qualification</th>
    <th>Actions</th>

</tr>

</thead>

<tbody>

<?php foreach($teachers as $teacher): ?>

<tr>

    <td><?= htmlspecialchars($teacher['full_name']); ?></td>

    <td><?= htmlspecialchars($teacher['email']); ?></td>

    <td><?= htmlspecialchars($teacher['phone']); ?></td>

    <td><?= htmlspecialchars($teacher['department_name']); ?></td>

    <td><?= htmlspecialchars($teacher['specialization']); ?></td>

    <td><?= htmlspecialchars($teacher['qualification']); ?></td>
    <td>
    <a href="edit.php?id=<?= $teacher['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
    
    <a href="delete.php?id=<?= $teacher['id']; ?>" class="btn btn-danger btn-sm"
    onclick="return confirm('Delete Teacher?')">Delete</a>
    </td>
    </tr>

<?php endforeach; ?>

</tbody>


</table>

<?php

include '../../app/views/layouts/footer.php';

?>
