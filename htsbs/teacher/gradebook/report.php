<?php
/*
=====================================================================
teacher/gradebook/report.php — تقرير درجات المقرر
⚠️ النسخة السابقة كانت معطّلة: تستدعي $gradebookModel->calculateFinalGrade()
   وهو متغير غير موجود → خطأ قاتل
=====================================================================
*/

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../core/Auth.php';

if (!Auth::check() || (int)($_SESSION['role_id'] ?? 0) !== 2) {
    die('Access Denied');
}

if (!function_exists('e')) {
    function e($v): string {
        return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

$db = (new Database())->connect();

$courseId = (int)($_GET['course_id'] ?? 0);

if ($courseId <= 0) {
    die('Course ID Not Found');
}

/* سجل المعلم */
$stmt = $db->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->execute([(int)$_SESSION['user_id']]);
$teacherId = (int)$stmt->fetchColumn();

/* التأكد أن المعلم يدرّس هذا المقرر */
$stmt = $db->prepare("
    SELECT COUNT(*) FROM course_assignments
    WHERE teacher_id = ? AND course_id = ?
");
$stmt->execute([$teacherId, $courseId]);

if ((int)$stmt->fetchColumn() === 0) {
    die('غير مصرح لك بعرض درجات هذا المقرر');
}

$stmt = $db->prepare("SELECT course_name FROM courses WHERE id = ?");
$stmt->execute([$courseId]);
$courseName = (string)$stmt->fetchColumn();

/* الدرجات */
$stmt = $db->prepare("
    SELECT g.*, u.full_name, s.student_number
    FROM gradebook g
    INNER JOIN students s ON g.student_id = s.id
    INNER JOIN users u    ON s.user_id = u.id
    WHERE g.course_id = ? AND g.teacher_id = ?
    ORDER BY u.full_name, g.created_at DESC
");
$stmt->execute([$courseId, $teacherId]);
$grades = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
حساب النسبة النهائية لكل طالب
(مجموع الدرجات ÷ مجموع الدرجات العظمى × 100)
هذا ما كان يفترض أن تفعله calculateFinalGrade المفقودة
*/
$finalGrades = [];

foreach ($grades as $g) {
    $sid = (int)$g['student_id'];
    $finalGrades[$sid]['sum'] = ($finalGrades[$sid]['sum'] ?? 0) + (float)$g['score'];
    $finalGrades[$sid]['max'] = ($finalGrades[$sid]['max'] ?? 0) + (float)$g['max_score'];
}

$typeLabels = [
    'Quiz' => 'اختبار قصير', 'Assignment' => 'واجب', 'Activity' => 'نشاط',
    'Midterm' => 'نصفي', 'Final' => 'نهائي', 'Participation' => 'مشاركة',
];

include '../../app/views/layouts/header.php';
?>

<div class="container-fluid">
<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">

<h4 class="fw-bold mb-1">
    <i class="bi bi-clipboard-data text-primary"></i> تقرير الدرجات
</h4>
<p class="text-muted small mb-4">مقرر: <?= e($courseName); ?></p>

<div class="card border-0 shadow-sm">
<div class="table-responsive">

<table class="table table-hover align-middle mb-0">

    <thead class="table-light">
        <tr>
            <th>الطالب</th>
            <th>الرقم الأكاديمي</th>
            <th>النوع</th>
            <th>عنوان التقييم</th>
            <th class="text-center">الدرجة</th>
            <th class="text-center">النسبة النهائية</th>
            <th>التاريخ</th>
            <th class="text-center">إجراءات</th>
        </tr>
    </thead>

    <tbody>

    <?php if (!$grades): ?>
        <tr><td colspan="8" class="text-center text-muted py-4">لا توجد درجات مرصودة</td></tr>
    <?php endif; ?>

    <?php foreach ($grades as $grade): ?>
    <?php
        $sid   = (int)$grade['student_id'];
        $sum   = $finalGrades[$sid]['sum'] ?? 0;
        $max   = $finalGrades[$sid]['max'] ?? 0;
        $final = $max > 0 ? round(($sum / $max) * 100, 1) : 0;
    ?>
        <tr>
            <td><?= e($grade['full_name']); ?></td>
            <td class="small"><?= e($grade['student_number']); ?></td>
            <td class="small">
                <?= e($typeLabels[$grade['assessment_type']] ?? $grade['assessment_type']); ?>
            </td>
            <td class="small"><?= e($grade['title']); ?></td>
            <td class="text-center fw-bold">
                <?= e($grade['score']); ?> / <?= e($grade['max_score']); ?>
            </td>
            <td class="text-center">
                <span class="badge bg-<?= $final >= 60 ? 'success' : 'danger'; ?>">
                    <?= $final; ?>%
                </span>
            </td>
            <td class="small text-muted">
                <?= e(date('Y-m-d H:i', strtotime((string)$grade['created_at']))); ?>
            </td>
            <td class="text-center">

                <a href="edit.php?id=<?= (int)$grade['id']; ?>"
                   class="btn btn-sm btn-warning">
                    <i class="bi bi-pencil"></i>
                </a>

                <a href="delete.php?id=<?= (int)$grade['id']; ?>"
                   class="btn btn-sm btn-danger"
                   onclick="return confirm('هل تريد حذف هذه الدرجة نهائياً؟');">
                    <i class="bi bi-trash"></i>
                </a>

            </td>
        </tr>
    <?php endforeach; ?>

    </tbody>

</table>

</div>
</div>

</div>
</div>
</div>

<?php include '../../app/views/layouts/footer.php'; ?>