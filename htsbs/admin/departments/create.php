<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config/config.php';

include '../../app/views/layouts/header.php';


?>


<div class="container-fluid">
<div class="row flex-lg-row-reverse">
<?php include '../../app/views/layouts/sidebar.php'; ?>
<div class="col-md-10 p-4">



<h2 class="mb-4">Add Department</h2>

<form action="store.php" method="POST">

    <div class="mb-3">

        <label class="form-label">
            Department Name
        </label>

        <input
            type="text"
            name="department_name"
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
            class="form-control"
            required>

    </div>

    <button
        type="submit"
        class="btn btn-success">

        Save Department

    </button>

    <a
        href="index.php"
        class="btn btn-secondary">

        Cancel

    </a>

</form>


</div>

<?php

include '../../app/views/layouts/footer.php';

?>
