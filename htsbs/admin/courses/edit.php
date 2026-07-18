<?php

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../app/models/course.php';

$courseModel = new Course();

/* التحقق من المعرّف */
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die('Course ID Not Found');
}

$course = $courseModel->getById($id);

if (!$course) {
    die('Course Not Found');
}

/* جلب الأقسام للقائمة المنسدلة (هنا يُولَّد $departments) */
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

<h2 class="mb-4">تعديل المقرر</h2>

<form method="POST" action="update.php?id=<?= (int)$course['id']; ?>">

    <div class="mb-3">
        <label class="form-label">اسم المقرر <span class="text-danger">*</span></label>
        <input
            type="text"
            name="course_name"
            value="<?= htmlspecialchars($course['course_name'] ?? ''); ?>"
            class="form-control"
            required>
    </div>

    <div class="mb-3">
        <label class="form-label">رمز المقرر</label>
        <input
            type="text"
            name="course_code"
            value="<?= htmlspecialchars($course['course_code'] ?? ''); ?>"
            class="form-control">
    </div>

    <div class="mb-3">
        <label class="form-label">الساعات المعتمدة</label>
        <input
            type="number"
            name="credit_hours"
            value="<?= htmlspecialchars((string)($course['credit_hours'] ?? '')); ?>"
            class="form-control">
    </div>

    <div class="mb-3">
        <label class="form-label">الوصف</label>
        <textarea
            name="description"
            class="form-control"
            rows="4"><?= htmlspecialchars($course['description'] ?? ''); ?></textarea>
    </div>

    <!-- القسم: قائمة منسدلة بدل إدخال الرقم يدوياً -->
    <div class="mb-3">

        <label class="form-label">القسم <span class="text-danger">*</span></label>

        <select name="department_id" class="form-select" required>

            <option value="">— اختر القسم —</option>

            <?php foreach ($departments as $d): ?>

                <option value="<?= (int)$d['id']; ?>"
                    <?= (int)($course['department_id'] ?? 0) === (int)$d['id'] ? 'selected' : ''; ?>>
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

    <button type="submit" class="btn btn-success">
        حفظ التعديلات
    </button>

    <a href="index.php" class="btn btn-secondary">
        إلغاء
    </a>

</form>

</div>
</div>
</div>

<?php
include '../../app/views/layouts/footer.php';
?>