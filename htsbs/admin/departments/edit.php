<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config/config.php';
require_once '../../app/models/Department.php';

if (!isset($_GET['id'])) {
    die('Department ID Not Found');
}

$departmentModel = new Department();

$department = $departmentModel->getById($_GET['id']);

if (!$department) {
    die('Department Not Found');
}

include '../../app/views/layouts/header.php';

?>

<div class="container-fluid">
<div class="row flex-lg-row-reverse">
<?php include '../../app/views/layouts/sidebar.php'; ?>
<div class="col-md-10 p-4">


<h2 class="mb-4">Edit Department</h2>

<form method="POST"
      action="update.php?id=<?= $department['id']; ?>">

    <div class="mb-3">

        <label class="form-label">
            Department Name
        </label>

        <input
            type="text"
            name="department_name"
            value="<?= htmlspecialchars($department['department_name']); ?>"
            class="form-control"
            required>

    </div>

    <div class="mb-3">

        <label class="form-label">
            Department Code
        </label>

        <input
            type="text"
            name="department_code"
            value="<?= htmlspecialchars($department['department_code']); ?>"
            class="form-control"
            required>

    </div>

    <button type="submit"
            class="btn btn-success">

        Update Department

    </button>

    <a href="index.php"
       class="btn btn-secondary">

        Cancel

    </a>

</form>


</div>

<?php

include '../../app/views/layouts/footer.php';

?>
