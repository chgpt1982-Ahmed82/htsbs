<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config/config.php';
require_once '../../app/models/ClassModel.php';

if (!isset($_GET['id'])) {
    die('Class ID Not Found');
}

$classModel = new ClassModel();

$class = $classModel->getById($_GET['id']);

if (!$class) {
    die('Class Not Found');
}

include '../../app/views/layouts/header.php';

?>

<div class="container-fluid">
<div class="row flex-lg-row-reverse">
<?php include '../../app/views/layouts/sidebar.php'; ?>
<div class="col-md-10 p-4">



<h2 class="mb-4">Edit Class</h2>

<form method="POST"
      action="update.php?id=<?= $class['id']; ?>">

    <div class="mb-3">

        <label class="form-label">
            Class Name
        </label>

        <input
            type="text"
            name="class_name"
            value="<?= htmlspecialchars($class['class_name']); ?>"
            class="form-control"
            required>

    </div>

    <div class="mb-3">

        <label class="form-label">
            Academic Year
        </label>

        <input
            type="text"
            name="academic_year"
            value="<?= htmlspecialchars($class['academic_year'] ?? ''); ?>"
            class="form-control"
            required>

    </div>

    <div class="mb-3">

        <label class="form-label">
            Semester
        </label>

        <select
            name="semester"
            class="form-control">

            <option value="First Semester"
                <?= ($class['semester'] == 'First Semester') ? 'selected' : ''; ?>>
                First Semester
            </option>

            <option value="Second Semester"
                <?= ($class['semester'] == 'Second Semester') ? 'selected' : ''; ?>>
                Second Semester
            </option>

            <option value="Summer"
                <?= ($class['semester'] == 'Summer') ? 'selected' : ''; ?>>
                Summer
            </option>

        </select>

    </div>

    <button type="submit"
            class="btn btn-success">

        Update Class

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
