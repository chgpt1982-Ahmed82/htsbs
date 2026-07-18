<?php

require_once '../../config/config.php';
require_once '../../app/models/Student.php';

$studentModel = new Student();

$departments = $studentModel->getDepartments();

$classes = $studentModel->getClasses();

include '../../app/views/layouts/header.php';

?>


<div class="container-fluid">
<div class="row flex-lg-row-reverse">
<?php include '../../app/views/layouts/sidebar.php'; ?>
<div class="col-md-10 p-4">

    
    
<h2 class="mb-4">

    Add Student

</h2>

<form
    method="POST"
    action="store.php">

    <div class="row">

        <div class="col-md-6 mb-3">

            <label>Full Name</label>

            <input
                type="text"
                name="full_name"
                class="form-control"
                required>

        </div>

        <div class="col-md-6 mb-3">

            <label>Email</label>

            <input
                type="email"
                name="email"
                class="form-control"
                required>

        </div>

        <div class="col-md-6 mb-3">

            <label>Password</label>

            <input
                type="password"
                name="password"
                class="form-control"
                required>

        </div>

        <div class="col-md-6 mb-3">

            <label>Phone</label>

            <input
                type="text"
                name="phone"
                class="form-control">

        </div>

        <div class="col-md-6 mb-3">

            <label>Student Number</label>

            <input
                type="text"
                name="student_number"
                class="form-control"
                required>

        </div>

        <div class="col-md-6 mb-3">

            <label>National ID</label>

            <input
                type="text"
                name="national_id"
                class="form-control">

        </div>

        <div class="col-md-6 mb-3">

            <label>Department</label>

            <select
                name="department_id"
                class="form-select"
                required>

                <?php foreach($departments as $department): ?>

                    <option
                        value="<?= $department['id']; ?>">

                        <?= htmlspecialchars($department['department_name']); ?>

                    </option>

                <?php endforeach; ?>

            </select>

        </div>

        <div class="col-md-6 mb-3">

            <label>Class</label>

            <select
                name="class_id"
                class="form-select">

                <?php foreach($classes as $class): ?>

                    <option
                        value="<?= $class['id']; ?>">

                        <?= htmlspecialchars($class['class_name']); ?>

                    </option>

                <?php endforeach; ?>

            </select>

        </div>

        <div class="col-md-6 mb-3">

            <label>Academic Level</label>

            <input
                type="text"
                name="academic_level"
                class="form-control">

        </div>

        <div class="col-md-6 mb-3">

            <label>GPA</label>

            <input
                type="number"
                step="0.01"
                name="gpa"
                class="form-control">

        </div>

        <div class="col-md-6 mb-3">

            <label>Guardian Phone 1</label>

            <input
                type="text"
                name="guardian_phone_1"
                class="form-control">

        </div>

        <div class="col-md-6 mb-3">

            <label>Guardian Phone 2</label>

            <input
                type="text"
                name="guardian_phone_2"
                class="form-control">

        </div>

    </div>

    <button
        type="submit"
        class="btn btn-success">

        Save Student

    </button>

    <a
        href="index.php"
        class="btn btn-secondary">

        Back

    </a>

</form>


</div>

<?php include '../../app/views/layouts/footer.php'; ?>
