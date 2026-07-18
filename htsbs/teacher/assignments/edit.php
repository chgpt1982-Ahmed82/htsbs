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

$teacherId = $teacher['id'];

/*
|---------------------------------------------------
| جلب بيانات الواجب
|---------------------------------------------------
*/

$assignmentId = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("
SELECT *
FROM assignments
WHERE id=?
AND teacher_id=?
");

$stmt->execute([
    $assignmentId,
    $teacherId
]);

$assignment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$assignment) {
    die('Assignment Not Found');
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
    $teacherId
]);

$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../app/views/layouts/header.php';

?>

<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">

<div class="card border-0 shadow">

<div class="card-header bg-warning text-dark">

    <h5 class="mb-0">

        <i class="bi bi-pencil-square"></i>

        تعديل الواجب

    </h5>

</div>

<div class="card-body">

<form
method="POST"
action="update.php?id=<?= $assignment['id']; ?>">

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

            <option
            value="<?= $course['id']; ?>"
            <?= ($course['id'] == $assignment['course_id']) ? 'selected' : ''; ?>>

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
        value="<?= htmlspecialchars($assignment['title']); ?>"
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
        class="form-control"><?= htmlspecialchars($assignment['description']); ?></textarea>

    </div>

    <div class="mb-3">

        <label class="form-label fw-semibold">

            تاريخ التسليم

        </label>

        <input
        type="date"
        name="due_date"
        value="<?= htmlspecialchars($assignment['due_date']); ?>"
        class="form-control"
        required>

    </div>

    <?php if (!empty($assignment['file_path'])): ?>

    <div class="mb-3">

        <label class="form-label fw-semibold">

            الملف الحالي

        </label>

        <div>

            <a
            href="<?= BASE_URL . '/' . $assignment['file_path']; ?>"
            target="_blank"
            class="btn btn-info btn-sm">

                <i class="bi bi-download"></i>

                تحميل الملف الحالي

            </a>

        </div>

    </div>

    <?php endif; ?>

    <div class="d-flex gap-2">

        <button
        type="submit"
        class="btn btn-success">

            <i class="bi bi-save-fill"></i>

            حفظ التعديلات

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
