<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config/config.php';
require_once '../../app/models/Department.php';

$departmentModel = new Department();

$departments = $departmentModel->getAll();

include '../../app/views/layouts/header.php';

?>

<div class="container-fluid">
<div class="row flex-lg-row-reverse">
<?php include '../../app/views/layouts/sidebar.php'; ?>
<div class="col-md-10 p-4">


<div class="d-flex justify-content-between align-items-center mb-4">

    <h2>Departments Management</h2>

    <a href="create.php"
       class="btn btn-primary">

        Add Department

    </a>

</div>

<table class="table table-bordered table-striped">

    <thead class="table-dark">

        <tr>

            <th>ID</th>

            <th>Department Name</th>

            <th>Department Code</th>

            <th width="180">Actions</th>

        </tr>

    </thead>

    <tbody>

    <?php if(!empty($departments)): ?>

        <?php foreach($departments as $department): ?>

            <tr>

                <td>
                    <?= $department['id']; ?>
                </td>

                <td>
                    <?= htmlspecialchars($department['department_name']); ?>
                </td>

                <td>
                    <?= htmlspecialchars($department['department_code']); ?>
                </td>

                <td>

                    <a
                        href="edit.php?id=<?= $department['id']; ?>"
                        class="btn btn-warning btn-sm">

                        Edit

                    </a>

                    <a
                        href="delete.php?id=<?= $department['id']; ?>"
                        class="btn btn-danger btn-sm"
                        onclick="return confirm('Are you sure you want to delete this department?');">

                        Delete

                    </a>

                </td>

            </tr>

        <?php endforeach; ?>

    <?php else: ?>

        <tr>

            <td colspan="4" class="text-center">

                No Departments Found

            </td>

        </tr>

    <?php endif; ?>

    </tbody>

</table>


</div>

<?php

include '../../app/views/layouts/footer.php';

?>
