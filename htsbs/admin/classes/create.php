<?php

require_once '../../config/config.php';

include '../../app/views/layouts/header.php';

?>

<div class="container-fluid">
<div class="row flex-lg-row-reverse">
<?php include '../../app/views/layouts/sidebar.php'; ?>
<div class="col-md-10 p-4">

<h2>Add Class</h2>

<form action="store.php" method="POST">


<div class="mb-3">

    <label>Class Name</label>

    <input
        type="text"
        name="class_name"
        class="form-control"
        required>

</div>

<div class="mb-3">

    <label>Academic Year</label>

    <input
        type="text"
        name="academic_year"
        class="form-control"
        placeholder="2025/2026"
        required>

</div>

<div class="mb-3">

    <label>Semester</label>

    <select
        name="semester"
        class="form-control">

        <option>First Semester</option>
        <option>Second Semester</option>
        <option>Summer</option>

    </select>

</div>

<button
    class="btn btn-success">

    Save

</button>


</form>

<?php

include '../../app/views/layouts/footer.php';

?>
