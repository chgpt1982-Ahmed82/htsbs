<?php
/*
=====================================================================
admin/students/edit.php — تعديل بيانات طالب (صفحة عرض النموذج)
=====================================================================
التعديلات:
  1. دالة e() — تعالج NULL تلقائياً (سبب تحذير Deprecated)
     وتهرّب علامات التنصيص (ENT_QUOTES) — حماية XSS أقوى
  2. حماية صلاحيات (أدمن فقط)
  3. التحقق من id ومن وجود الطالب
  4. إغلاق وسوم div الناقصة
  ⚠️ لا Logger هنا — هذه صفحة عرض فقط، التسجيل في update.php
=====================================================================
*/

session_start();

require_once '../../config/config.php';
require_once '../../core/Auth.php';
require_once '../../app/models/Student.php';

/* ==================== الصلاحية: أدمن فقط ==================== */
if (!Auth::check() || (int)($_SESSION['role_id'] ?? 0) !== 1) {
    die('Access Denied');
}

/*
====================================================================
دالة إخراج آمن
تعالج NULL (سبب التحذير) + تهرّب ' و " (حماية XSS)
يُفضَّل نقلها إلى config/config.php ليستفيد منها المشروع كله
====================================================================
*/
if (!function_exists('e')) {
    function e($value): string
    {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

/* ==================== التحقق من المدخلات ==================== */
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die('Student ID Not Found');
}

$studentModel = new Student();

$student = $studentModel->getById($id);

if (!$student) {
    die('Student Not Found');
}

$departments = $studentModel->getDepartments();
$classes     = $studentModel->getClasses();

include '../../app/views/layouts/header.php';
?>

<div class="container-fluid">
<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/sidebar.php'; ?>

<div class="col-md-10 p-4">

<h2 class="mb-4">تعديل بيانات الطالب</h2>

<form method="POST" action="update.php?id=<?= (int)$student['id']; ?>">

<div class="row">

    <!-- ==================== البيانات الشخصية ==================== -->

    <div class="col-md-6 mb-3">
        <label class="form-label">الاسم الكامل <span class="text-danger">*</span></label>
        <input type="text"
               name="full_name"
               value="<?= e($student['full_name']); ?>"
               class="form-control"
               required>
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">البريد الإلكتروني <span class="text-danger">*</span></label>
        <input type="email"
               name="email"
               value="<?= e($student['email']); ?>"
               class="form-control"
               required>
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">الهاتف</label>
        <input type="text"
               name="phone"
               value="<?= e($student['phone']); ?>"
               class="form-control">
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">الرقم الأكاديمي</label>
        <input type="text"
               name="student_number"
               value="<?= e($student['student_number']); ?>"
               class="form-control">
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">الرقم الشخصي</label>
        <input type="text"
               name="national_id"
               value="<?= e($student['national_id']); ?>"
               class="form-control">
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">المستوى الدراسي</label>
        <input type="text"
               name="academic_level"
               value="<?= e($student['academic_level']); ?>"
               class="form-control">
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">المعدل التراكمي</label>
        <input type="text"
               name="gpa"
               value="<?= e($student['gpa']); ?>"
               class="form-control">
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">هاتف ولي الأمر 1</label>
        <input type="text"
               name="guardian_phone_1"
               value="<?= e($student['guardian_phone_1']); ?>"
               class="form-control">
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">هاتف ولي الأمر 2</label>
        <input type="text"
               name="guardian_phone_2"
               value="<?= e($student['guardian_phone_2']); ?>"
               class="form-control">
    </div>

    <!-- ==================== القسم ==================== -->

    <div class="col-md-6 mb-3">

        <label class="form-label">القسم <span class="text-danger">*</span></label>

        <select name="department_id" class="form-select" required>

            <option value="">— اختر القسم —</option>

            <?php foreach ($departments as $department): ?>

                <option value="<?= (int)$department['id']; ?>"
                    <?= ((int)$department['id'] === (int)$student['department_id']) ? 'selected' : ''; ?>>
                    <?= e($department['department_name']); ?>
                </option>

            <?php endforeach; ?>

        </select>

    </div>

    <!-- ==================== الصف ==================== -->

    <div class="col-md-6 mb-3">

        <label class="form-label">الصف <span class="text-danger">*</span></label>

        <select name="class_id" class="form-select" required>

            <option value="">— اختر الصف —</option>

            <?php foreach ($classes as $class): ?>

                <option value="<?= (int)$class['id']; ?>"
                    <?= ((int)$class['id'] === (int)$student['class_id']) ? 'selected' : ''; ?>>
                    <?= e($class['class_name']); ?>
                </option>

            <?php endforeach; ?>

        </select>

        <small class="text-muted">
            ⚠️ تغيير الصف يغيّر مقررات الطالب ودروسه في التعلم التفاعلي
        </small>

    </div>

</div>

<hr class="my-4">

<button type="submit" class="btn btn-success">
    <i class="bi bi-check-circle"></i> حفظ التعديلات
</button>

<a href="index.php" class="btn btn-secondary">رجوع</a>

</form>

</div>
</div>
</div>

<?php include '../../app/views/layouts/footer.php'; ?>