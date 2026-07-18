<?php

require_once '../../config/config.php';
require_once '../../config/database.php';

/* جلب الأقسام لعرضها في القائمة المنسدلة */
$database = new Database();
$db = $database->connect();

$departments = $db->query(
    "SELECT id, department_name FROM departments ORDER BY department_name"
)->fetchAll(PDO::FETCH_ASSOC);

include '../../app/views/layouts/header.php';
?>



<div class="container-fluid">
<div class="row flex-lg-row-reverse">
<?php include '../../app/views/layouts/sidebar.php'; ?>
<div class="col-md-10 p-4">

<h2>Add Course</h2>

<form action="store.php"
      method="POST">

    <div class="mb-3">

        <label>Course Name</label>

        <input
            type="text"
            name="course_name"
            class="form-control"
            required>

    </div>

    <div class="mb-3">

        <label>Course Code</label>

        <input
            type="text"
            name="course_code"
            class="form-control">

    </div>

    <div class="mb-3">

        <label>Credit Hours</label>

        <input
            type="number"
            name="credit_hours"
            class="form-control">

    </div>

    <div class="mb-3">

        <label>Description</label>

        <textarea
            name="description"
            class="form-control"></textarea>

    </div>

    <div class="mb-3">

       <div class="mb-3">

    <label>القسم <span class="text-danger">*</span></label>

    <select name="department_id" class="form-select" required>

        <option value="">— اختر القسم —</option>

        <?php foreach ($departments as $d): ?>
            <option value="<?= (int)$d['id']; ?>">
                <?= htmlspecialchars($d['department_name']); ?>
            </option>
        <?php endforeach; ?>

    </select>

    <?php if (!$departments): ?>
        <small class="text-danger">
            لا توجد أقسام — أضف قسماً أولاً من صفحة الأقسام
        </small>
    <?php endif; ?>

</div>
    </div>

    <button
        class="btn btn-success">

        Save

    </button>

</form>

<?php
include '../../app/views/layouts/footer.php';
?>