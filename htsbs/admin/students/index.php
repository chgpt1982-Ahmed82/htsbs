<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

session_start();

require_once '../../config/config.php';
require_once '../../app/models/Student.php';

$studentModel = new Student();

$classId = $_GET['class_id'] ?? '';

$classes = $studentModel->getClasses();

$students = $studentModel->getAllByClass($classId);

include '../../app/views/layouts/header.php';

?>

<div class="container-fluid">
<div class="row flex-lg-row-reverse">
<?php include '../../app/views/layouts/sidebar.php'; ?>
<div class="col-md-10 p-4">


<div class="d-flex justify-content-between align-items-center mb-4">

    <h2>Students</h2>

    <div>

        <a href="create.php"
           class="btn btn-primary">

            Add Student

        </a>

        <a href="import.php"
           class="btn btn-success">

            Import Excel

        </a>

    </div>

</div>

<div class="card mb-4">

    <div class="card-body">

        <form method="GET">

            <div class="row">

                <div class="col-md-4">

                    <label class="form-label fw-bold">

                        Filter By Class

                    </label>

                    <select
                        name="class_id"
                        class="form-select"
                        onchange="this.form.submit()">

                        <option value="">
                            All Classes
                        </option>

                        <?php foreach($classes as $class): ?>

                            <option
                                value="<?= $class['id']; ?>"
                                <?= ($classId == $class['id']) ? 'selected' : ''; ?>>

                                <?= htmlspecialchars($class['class_name']); ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

            </div>

        </form>

    </div>

</div>

<div class="table-responsive">

    <table class="table table-bordered table-striped">

        <thead class="table-dark">

            <tr>

                <th>Name</th>
                <th>Student No.</th>
                <th>Class</th>
                <th>Phone</th>
                <th>Level</th>
                <th>Department</th>
                <th width="150">Actions</th>

            </tr>

        </thead>

        <tbody>

        <?php foreach($students as $student): ?>

            <tr>

                <td>
                    <?= htmlspecialchars($student['full_name']); ?>
                </td>

                <td>
                    <?= htmlspecialchars($student['student_number']); ?>
                </td>

                <td>
                    <?= htmlspecialchars($student['class_name']); ?>
                </td>

                <td>
                    <?= htmlspecialchars($student['phone']); ?>
                </td>

                <td>
                    <?= htmlspecialchars($student['academic_level']); ?>
                </td>

                <td>
                    <?= htmlspecialchars($student['department_name']); ?>
                </td>

                <td>

                    <a
                        href="edit.php?id=<?= $student['id']; ?>"
                        class="btn btn-warning btn-sm">

                        Edit

                    </a>

                    <a
                        href="delete.php?id=<?= $student['id']; ?>"
                        class="btn btn-danger btn-sm"
                        onclick="return confirm('Delete Student?')">

                        Delete

                    </a>

                </td>

            </tr>

        <?php endforeach; ?>

        </tbody>

    </table>

</div>


</div>

<?php

include '../../app/views/layouts/footer.php';

?>
