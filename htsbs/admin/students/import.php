<?php
/*
=====================================================================
admin/students/import.php — صفحة استيراد الطلاب من CSV
=====================================================================
التعديلات:
  1. حماية صلاحيات (أدمن فقط) — كانت الصفحة مفتوحة للجميع
  2. قائمة منسدلة للأقسام (بدل تثبيت department_id = 1)
  3. عرض الصفوف الموجودة — لأن الاستيراد لم يعد ينشئ صفوفاً تلقائياً
  4. عرض رسائل الأخطاء ($_SESSION['error']) — كانت تُهمل تماماً
  5. زر تحميل ملف CSV نموذجي جاهز
=====================================================================
*/

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../core/Auth.php';

/* ==================== الصلاحية: أدمن فقط ==================== */
if (!Auth::check() || (int)($_SESSION['role_id'] ?? 0) !== 1) {
    die('Access Denied');
}

$db = (new Database())->connect();

/* الأقسام — لاختيار قسم الطلاب المستوردين */
$departments = $db->query(
    "SELECT id, department_name FROM departments ORDER BY department_name"
)->fetchAll(PDO::FETCH_ASSOC);

/* الصفوف الموجودة — يجب أن تطابق أسماؤها ما في ملف CSV تماماً */
$classes = $db->query(
    "SELECT id, class_name FROM classes ORDER BY class_name"
)->fetchAll(PDO::FETCH_ASSOC);

include '../../app/views/layouts/header.php';
?>

<div class="container-fluid">
<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/sidebar.php'; ?>

<div class="col-md-10 p-4">

<div class="card shadow-sm border-0">

    <div class="card-header bg-white">
        <h4 class="mb-0">📥 استيراد الطلاب من ملف CSV</h4>
    </div>

    <div class="card-body">

        <!-- ==================== الرسائل ==================== -->

        <?php if (!empty($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= $_SESSION['success']; ?>
                <button class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['error'])): ?>
            <div class="alert alert-warning alert-dismissible fade show">
                <?= $_SESSION['error']; /* يحتوي <br> مُنسّقة من import_process */ ?>
                <button class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- ==================== تعليمات ==================== -->

        <div class="alert alert-info">

            <strong>ترتيب الأعمدة في الملف (بهذا الترتيب تماماً):</strong>

            <hr class="my-2">

            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-2 bg-white">
                    <thead class="table-light">
                        <tr>
                            <th>1</th><th>2</th><th>3</th><th>4</th>
                            <th>5</th><th>6</th><th>7</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>الرقم الأكاديمي <span class="text-danger">*</span></td>
                            <td>الرقم الشخصي</td>
                            <td>اسم الطالب <span class="text-danger">*</span></td>
                            <td>الصف <span class="text-danger">*</span></td>
                            <td>المعدل</td>
                            <td>هاتف ولي الأمر</td>
                            <td>هاتف الطالب</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <ul class="mb-0 small">
                <li>السطر الأول يُعتبر رؤوس أعمدة ويُتخطى تلقائياً.</li>
                <li>الحقول المعلّمة بـ <span class="text-danger">*</span> إلزامية، والسطر بدونها يُتخطى.</li>
                <li>كلمة المرور الأولية لكل طالب = <strong>رقمه الأكاديمي</strong>.</li>
                <li>البريد يُولَّد تلقائياً: <code>الرقم_الأكاديمي@lms.edu</code></li>
                <li>الطالب المسجّل مسبقاً (بنفس الرقم أو البريد) يُتخطى ولا يُكرَّر.</li>
            </ul>

        </div>

        <!-- ==================== تحذير الصفوف ==================== -->

        <div class="alert alert-warning">

            <strong>⚠️ مهم:</strong>
            اسم الصف في الملف يجب أن <strong>يطابق تماماً</strong> اسم صف موجود في النظام.
            الاستيراد <strong>لا ينشئ صفوفاً جديدة</strong> — أي اسم غير مطابق يُتخطى سطره.

            <hr class="my-2">

            <?php if ($classes): ?>

                <strong>الصفوف المتاحة حالياً:</strong>
                <div class="mt-2">
                    <?php foreach ($classes as $c): ?>
                        <span class="badge bg-secondary fs-6 me-1 mb-1">
                            <?= htmlspecialchars($c['class_name']); ?>
                        </span>
                    <?php endforeach; ?>
                </div>

            <?php else: ?>

                <span class="text-danger">
                    لا توجد صفوف في النظام —
                    <a href="<?= BASE_URL ?>/admin/classes/create.php" class="alert-link">أضف صفاً أولاً</a>
                </span>

            <?php endif; ?>

        </div>

        <!-- ==================== النموذج ==================== -->

        <?php if (!$departments): ?>

            <div class="alert alert-danger">
                لا توجد أقسام في النظام —
                <a href="<?= BASE_URL ?>/admin/departments/create.php" class="alert-link">أضف قسماً أولاً</a>
                قبل استيراد الطلاب.
            </div>

        <?php else: ?>

        <form method="POST"
              action="import_process.php"
              enctype="multipart/form-data"
              onsubmit="return confirm('سيتم استيراد الطلاب من الملف المحدد. هل تريد المتابعة؟');">

            <div class="row g-3">

                <!-- القسم -->
                <div class="col-md-6">

                    <label class="form-label fw-bold">
                        القسم <span class="text-danger">*</span>
                    </label>

                    <select name="department_id" class="form-select" required>

                        <option value="">— اختر القسم —</option>

                        <?php foreach ($departments as $d): ?>
                            <option value="<?= (int)$d['id']; ?>">
                                <?= htmlspecialchars($d['department_name']); ?>
                            </option>
                        <?php endforeach; ?>

                    </select>

                    <small class="text-muted">
                        جميع الطلاب في هذا الملف سيُسندون إلى القسم المحدد
                    </small>

                </div>

                <!-- الملف -->
                <div class="col-md-6">

                    <label class="form-label fw-bold">
                        ملف CSV <span class="text-danger">*</span>
                    </label>

                    <input type="file"
                           name="excel_file"
                           accept=".csv,text/csv"
                           class="form-control"
                           required>

                    <small class="text-muted">
                        الحد الأقصى: 5 ميجابايت — الصيغة: CSV فقط
                    </small>

                </div>

            </div>

            <hr class="my-4">

            <button type="submit" class="btn btn-success">
                <i class="bi bi-upload"></i> استيراد الطلاب
            </button>

            <a href="sample_csv.php" class="btn btn-outline-primary">
                <i class="bi bi-download"></i> تحميل ملف نموذجي
            </a>

            <a href="index.php" class="btn btn-secondary">
                رجوع
            </a>

        </form>

        <?php endif; ?>

    </div>

</div>

</div>
</div>
</div>

<?php include '../../app/views/layouts/footer.php'; ?>