<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config/config.php';
require_once '../../app/models/Teacher.php';

if (!isset($_GET['id'])) {
    die('Teacher ID Not Found');
}

$teacherModel = new Teacher();

$teacher = $teacherModel->getById($_GET['id']);

$departments = $teacherModel->getDepartments();

if (!$teacher) {
    die('Teacher Not Found');
}

include '../../app/views/layouts/header.php';

?>

<div class="container-fluid">
<div class="row flex-lg-row-reverse">
<?php include '../../app/views/layouts/sidebar.php'; ?>
<div class="col-md-10 p-4">


<h2>Edit Teacher</h2>

<form method="POST"
      action="update.php?id=<?= $teacher['id']; ?>">


<div class="mb-3">

    <label>Full Name</label>

    <input
        type="text"
        name="full_name"
        value="<?= htmlspecialchars($teacher['full_name']); ?>"
        class="form-control"
        required>

</div>

<div class="mb-3">

    <label>Email</label>

    <input
        type="email"
        name="email"
        value="<?= htmlspecialchars($teacher['email']); ?>"
        class="form-control"
        required>

</div>

<div class="mb-3">

    <label>Phone</label>

    <input
        type="text"
        name="phone"
        value="<?= htmlspecialchars($teacher['phone']); ?>"
        class="form-control">

</div>

<div class="mb-3">

    <label>Department</label>

    <select
        name="department_id"
        class="form-control">

        <?php foreach($departments as $department): ?>

        <option
            value="<?= $department['id']; ?>"
            <?= ($department['id'] == $teacher['department_id']) ? 'selected' : ''; ?>>

            <?= htmlspecialchars($department['department_name']); ?>

        </option>

        <?php endforeach; ?>

    </select>

</div>

<div class="mb-3">

    <label>Specialization</label>

    <input
        type="text"
        name="specialization"
        value="<?= htmlspecialchars($teacher['specialization']); ?>"
        class="form-control">

</div>

<div class="mb-3">

    <label>Qualification</label>

    <input
        type="text"
        name="qualification"
        value="<?= htmlspecialchars($teacher['qualification']); ?>"
        class="form-control">

</div>

<button class="btn btn-success">
    Update Teacher
</button>

</form>

<?php

include '../../app/views/layouts/footer.php';

?>
