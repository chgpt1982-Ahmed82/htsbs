<?php

require_once '../../config/config.php';
require_once '../../app/models/ClassModel.php';

$classModel = new ClassModel();

$classes = $classModel->getAll();

include '../../app/views/layouts/header.php';

?>


<div class="container-fluid">
<div class="row flex-lg-row-reverse">
<?php include '../../app/views/layouts/sidebar.php'; ?>
<div class="col-md-10 p-4">


<div class="d-flex justify-content-between mb-4">

    <h2>Classes Management</h2>

    <a href="create.php"
       class="btn btn-primary">

        Add Class

    </a>

</div>

<table class="table table-bordered">

    <thead class="table-dark">

    <tr>

        <th>ID</th>
        <th>Class Name</th>
        <th>Academic Year</th>
        <th>Semester</th>
        <th>Actions</th>

    </tr>

    </thead>

    <tbody>

    <?php foreach($classes as $class): ?>

    <tr>

        <td><?= $class['id']; ?></td>

        <td><?= htmlspecialchars($class['class_name']); ?></td>
        <td><?= htmlspecialchars($class['academic_year'] ?? ''); ?> </td>
        <td><?= htmlspecialchars($class['semester'] ?? ''); ?></td>

        <td>

            <a href="edit.php?id=<?= $class['id']; ?>"
               class="btn btn-warning btn-sm">

                Edit

            </a>

            <a href="delete.php?id=<?= $class['id']; ?>"
               class="btn btn-danger btn-sm"
               onclick="return confirm('Delete this class?')">

                Delete

            </a>

        </td>

    </tr>

    <?php endforeach; ?>

    </tbody>

</table>

</div>

<?php

include '../../app/views/layouts/footer.php';

?>
