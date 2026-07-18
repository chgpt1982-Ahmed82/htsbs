<?php
/*
=====================================================================
teacher/gradebook/edit.php — نموذج تعديل درجة واحدة
⚠️ صفحة عرض فقط — التسجيل في update.php
=====================================================================
*/

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../core/Auth.php';

/* ==================== الصلاحية: معلم فقط ==================== */
if (!Auth::check() || (int)($_SESSION['role_id'] ?? 0) !== 2) {
    die('Access Denied');
}

if (!function_exists('e')) {
    function e($v): string {
        return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

$db = (new Database())->connect();

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die('Grade ID Not Found');
}

/* سجل المعلم */
$stmt = $db->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->execute([(int)$_SESSION['user_id']]);
$teacherId = (int)$stmt->fetchColumn();

if ($teacherId <= 0) {
    die('Teacher Not Found');
}

/*
جلب الدرجة — مع التأكد أنها من رصد هذا المعلم
(حماية من التلاعب بالمعرّف: معلم يعدّل درجة رصدها معلم آخر)
*/
$stmt = $db->prepare("
    SELECT g.*, u.full_name, s.student_number, c.course_name
    FROM gradebook g
    INNER JOIN students s ON g.student_id = s.id
    INNER JOIN users u    ON s.user_id = u.id
    INNER JOIN courses c  ON g.course_id = c.id
    WHERE g.id = ? AND g.teacher_id = ?
");
$stmt->execute([$id, $teacherId]);
$grade = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$grade) {
    die('الدرجة غير موجودة أو لا تملك صلاحية تعديلها');
}

/* أنواع التقييم — مطابقة لـ ENUM في قاعدة البيانات */
$types = ['Quiz', 'Assignment', 'Activity', 'Midterm', 'Final', 'Participation'];

$typeLabels = [
    'Quiz'          => 'اختبار قصير',
    'Assignment'    => 'واجب',
    'Activity'      => 'نشاط',
    'Midterm'       => 'اختبار نصفي',
    'Final'         => 'اختبار نهائي',
    'Participation' => 'مشاركة',
];

include '../../app/views/layouts/header.php';
?>

<div class="container-fluid">
<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">

<h4 class="fw-bold mb-4">
    <i class="bi bi-pencil-square text-warning"></i> تعديل درجة
</h4>

<div class="card border-0 shadow-sm">

    <div class="card-header bg-white">
        <strong><?= e($grade['full_name']); ?></strong>
        <small class="text-muted">
            (<?= e($grade['student_number']); ?>) - <?= e($grade['course_name']); ?>
        </small>
    </div>

    <div class="card-body">

        <form method="POST" action="update.php?id=<?= (int)$grade['id']; ?>">

            <div class="row g-3">

                <div class="col-md-6">
                    <label class="form-label fw-bold">نوع التقييم</label>
                    <select name="assessment_type" class="form-select" required>
                        <?php foreach ($types as $t): ?>
                            <option value="<?= e($t); ?>"
                                <?= $grade['assessment_type'] === $t ? 'selected' : ''; ?>>
                                <?= e($typeLabels[$t] ?? $t); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">عنوان التقييم</label>
                    <input type="text" name="title" class="form-control"
                           value="<?= e($grade['title']); ?>" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold">الدرجة</label>
                    <input type="number" name="score" class="form-control"
                           step="0.25" min="0"
                           value="<?= e($grade['score']); ?>" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold">الدرجة العظمى</label>
                    <input type="number" name="max_score" class="form-control"
                           step="0.25" min="0.25"
                           value="<?= e($grade['max_score']); ?>" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold">الوزن (%)</label>
                    <input type="number" name="weight" class="form-control"
                           step="0.5" min="0" max="100"
                           value="<?= e($grade['weight']); ?>">
                </div>

            </div>

            <div class="alert alert-warning mt-4 mb-3 small">
                <i class="bi bi-info-circle"></i>
                الدرجة الحالية:
                <strong><?= e($grade['score']); ?> / <?= e($grade['max_score']); ?></strong>
                — سيُسجَّل أي تغيير في سجل النشاط، وسيُشعَر الطالب وولي أمره.
            </div>

            <button type="submit" class="btn btn-success">
                <i class="bi bi-check-circle"></i> حفظ التعديل
            </button>

            <a href="report.php?course_id=<?= (int)$grade['course_id']; ?>"
               class="btn btn-secondary">رجوع</a>

        </form>

    </div>

</div>

</div>
</div>
</div>

<?php include '../../app/views/layouts/footer.php'; ?>