<?php

require_once '../../config/config.php';
require_once '../../app/models/Teacher.php';

$teacherModel = new Teacher();

$departments = $teacherModel->getDepartments();

include '../../app/views/layouts/header.php';

?>

<div class="container-fluid">
<div class="row flex-lg-row-reverse">
<?php include '../../app/views/layouts/sidebar.php'; ?>
<div class="col-md-10 p-4">


<h2>Add Teacher</h2>

<form method="POST" action="store.php">


<div class="mb-3">

    <label>Full Name</label>

    <input
        type="text"
        name="full_name"
        class="form-control"
        required>

</div>

<div class="mb-3">

    <label>Email</label>

    <input
        type="email"
        name="email"
        class="form-control"
        required>

</div>

<div class="mb-3">

    <label>Password</label>

    <input
        type="password"
        name="password"
        class="form-control"
        required>

</div>

<div class="mb-3">

    <label>Phone</label>

    <input
        type="text"
        name="phone"
        class="form-control">

</div>

<div class="mb-3">

    <label>Department</label>

    <select
        name="department_id"
        class="form-control">

        <?php foreach($departments as $department): ?>

        <option value="<?= $department['id']; ?>">

            <?= $department['department_name']; ?>

        </option>

        <?php endforeach; ?>

    </select>

</div>

<div class="mb-3">

    <label>Specialization</label>

    <input
        type="text"
        name="specialization"
        class="form-control">

</div>

<div class="mb-3">

    <label>Qualification</label>

    <input
        type="text"
        name="qualification"
        class="form-control">

</div>

<button
    class="btn btn-success">

    Save Teacher

</button>


</form>

<?php

include '../../app/views/layouts/footer.php';

?>
