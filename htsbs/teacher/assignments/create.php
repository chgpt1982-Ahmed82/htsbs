<?php

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';

if (
    !isset($_SESSION['user_id']) ||
    $_SESSION['role_id'] != 2
) {
    exit('Unauthorized Access');
}

$db = (new Database())->connect();

/*
|---------------------------------------------------
| جلب المعلم الحالي
|---------------------------------------------------
*/

$stmt = $db->prepare("
SELECT id
FROM teachers
WHERE user_id=?
");

$stmt->execute([
    $_SESSION['user_id']
]);

$teacher = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$teacher) {
    die('Teacher Not Found');
}

/*
|---------------------------------------------------
| جلب مقررات المعلم
|---------------------------------------------------
*/

$stmt = $db->prepare("
SELECT DISTINCT

    c.id,
    c.course_name

FROM course_assignments ca

INNER JOIN courses c
ON ca.course_id = c.id

WHERE ca.teacher_id=?

ORDER BY c.course_name
");

$stmt->execute([
    $teacher['id']
]);

$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../app/views/layouts/header.php';

?>

<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">

<div class="card border-0 shadow">

<div class="card-header bg-primary text-white">

    <h5 class="mb-0">

        <i class="bi bi-plus-circle"></i>

        إضافة واجب جديد

    </h5>

</div>

<div class="card-body">

<form
    action="store.php"
    method="POST"
    enctype="multipart/form-data">

    
    <div class="mb-3">

        <label class="form-label fw-semibold">

            المقرر الدراسي

        </label>

        <select
        name="course_id"
        class="form-select"
        required>

            <option value="">اختر المقرر</option>

            <?php foreach ($courses as $course): ?>

            <option value="<?= $course['id']; ?>">

                <?= htmlspecialchars($course['course_name']); ?>

            </option>

            <?php endforeach; ?>

        </select>

    </div>

    <div class="mb-3">

        <label class="form-label fw-semibold">

            عنوان الواجب

        </label>

        <input
        type="text"
        name="title"
        class="form-control"
        required>

    </div>

    <div class="mb-3">

        <label class="form-label fw-semibold">

            وصف الواجب

        </label>

        <textarea
        name="description"
        rows="5"
        class="form-control"></textarea>

    </div>

    <div class="mb-3">

        <label class="form-label fw-semibold">

            تاريخ التسليم

        </label>

        <input
        type="date"
        name="due_date"
        class="form-control"
        required>

    </div>

    <div class="mb-3">

        <label class="form-label fw-semibold">

            ملف الواجب

        </label>

        <input
        type="file"
        name="assignment_file"
        class="form-control">

        <small class="text-muted">

            PDF - DOCX - ZIP - RAR

        </small>

    </div>

    <div class="d-flex gap-2">

        <button
        type="submit"
        class="btn btn-success">

            <i class="bi bi-save-fill"></i>

            حفظ الواجب

        </button>

        <a
        href="index.php"
        class="btn btn-secondary">

            رجوع

        </a>

    </div>

</form>

</div>

</div>

</div>

</div>

</div>

<?php include '../../app/views/layouts/footer.php'; ?>
