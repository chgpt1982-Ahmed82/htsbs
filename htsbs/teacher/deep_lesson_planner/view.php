<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../app/models/DeepLessonPlanner.php';
require_once '../../app/models/Notification.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/login.php");
    exit;
}

if ($_SESSION['role_id'] != 2) {
    die("Access Denied");
}

$db = (new Database())->connect();
$model = new DeepLessonPlanner();
$notificationModel = new Notification();
$count = $notificationModel->unreadCount($_SESSION['user_id']);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    die("رقم التخطيط غير صحيح.");
}

/*
==================================================
جلب التخطيط مع بيانات المادة والصف
==================================================
*/

$stmt = $db->prepare("
SELECT
    dlp.*,
    c.course_name,
    cl.class_name,
    u.full_name
FROM deep_lesson_plans dlp
LEFT JOIN courses c  ON c.id  = dlp.subject_id
LEFT JOIN classes cl ON cl.id = dlp.class_id
LEFT JOIN users   u  ON u.id  = dlp.teacher_id
WHERE dlp.id = ?
AND dlp.teacher_id = ?
LIMIT 1
");

$stmt->execute([$id, $_SESSION['user_id']]);
$lesson = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$lesson) {
    die("التخطيط غير موجود.");
}

// Decode JSON
$planData = [];
if (!empty($lesson['lesson_plan_json'])) {
    $planData = json_decode($lesson['lesson_plan_json'], true);
    if (!is_array($planData)) $planData = [];
}

// Decode resources & facilities
$resources  = [];
$facilities = [];
if (!empty($lesson['resources'])) {
    $resources = json_decode($lesson['resources'], true) ?: [];
}
if (!empty($lesson['facilities'])) {
    $facilities = json_decode($lesson['facilities'], true) ?: [];
}

include '../../app/views/layouts/header.php';

?>

<div class="container-fluid">
<div class="row">
<?php include '../../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">

<?php if (isset($_SESSION['success'])): ?>
<div class="alert alert-success alert-dismissible fade show">
    <?= $_SESSION['success']; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['success']); ?>
<?php endif; ?>

<!-- Action Bar -->
<div class="card shadow border-0 mb-3">
<div class="card-body py-2">
<div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">

    <h5 class="mb-0 fw-bold">
        <i class="bi bi-journal-richtext text-primary"></i>
        <?= htmlspecialchars($lesson['lesson_title']); ?>
    </h5>

    <div class="btn-group" role="group">

        <a href="edit.php?id=<?= $lesson['id']; ?>" class="btn btn-warning btn-sm" title="تعديل">
            <i class="bi bi-pencil-fill"></i> تعديل
        </a>

        <a href="print.php?id=<?= $lesson['id']; ?>" target="_blank" class="btn btn-success btn-sm" title="طباعة">
            <i class="bi bi-printer-fill"></i> طباعة
        </a>

        <a href="export_pdf.php?id=<?= $lesson['id']; ?>" target="_blank" class="btn btn-danger btn-sm" title="PDF">
            <i class="bi bi-file-earmark-pdf-fill"></i> PDF
        </a>

        <a href="export_word.php?id=<?= $lesson['id']; ?>" class="btn btn-primary btn-sm" title="Word">
            <i class="bi bi-file-earmark-word-fill"></i> Word
        </a>

        <button onclick="copyLesson()" class="btn btn-secondary btn-sm" title="نسخ">
            <i class="bi bi-clipboard-fill"></i> نسخ
        </button>

        <?php if ($lesson['is_favorite']): ?>
        <a href="favorite.php?id=<?= $lesson['id']; ?>&action=remove" class="btn btn-warning btn-sm">
            <i class="bi bi-star-fill"></i>
        </a>
        <?php else: ?>
        <a href="favorite.php?id=<?= $lesson['id']; ?>&action=add" class="btn btn-outline-warning btn-sm">
            <i class="bi bi-star"></i>
        </a>
        <?php endif; ?>

        <a href="index.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-right-circle"></i> رجوع
        </a>

    </div>

</div>
</div>
</div>

<!-- Info Card -->
<div class="card shadow border-0 mb-4">
<div class="card-header bg-primary text-white">
    <i class="bi bi-info-circle-fill"></i> معلومات التخطيط
</div>
<div class="card-body">
<div class="row">
    <div class="col-md-3"><strong>المادة:</strong> <?= htmlspecialchars($lesson['course_name']); ?></div>
    <div class="col-md-3"><strong>الصف:</strong> <?= htmlspecialchars($lesson['class_name']); ?></div>
    <div class="col-md-3"><strong>الوحدة:</strong> <?= htmlspecialchars($lesson['unit_name']); ?></div>
    <div class="col-md-3"><strong>التاريخ:</strong> <?= htmlspecialchars($lesson['lesson_date'] ?? ''); ?></div>
    <div class="col-md-3 mt-2"><strong>زمن الحصة:</strong> <?= htmlspecialchars((string)$lesson['lesson_duration']); ?> دقيقة</div>
    <div class="col-md-3 mt-2"><strong>مستوى الطلبة:</strong> <?= htmlspecialchars($lesson['student_level']); ?></div>
    <div class="col-md-3 mt-2"><strong>طريقة التدريس:</strong> <?= htmlspecialchars($lesson['teaching_method']); ?></div>
    <div class="col-md-3 mt-2"><strong>الحالة:</strong>
        <?php
        switch ($lesson['status']) {
            case 'published': echo '<span class="badge bg-success">منشور</span>'; break;
            case 'archived':  echo '<span class="badge bg-secondary">مؤرشف</span>'; break;
            default:          echo '<span class="badge bg-warning text-dark">مسودة</span>';
        }
        ?>
    </div>
</div>

<?php if (!empty($resources)): ?>
<div class="mt-2"><strong>الوسائل:</strong> <?= implode(' | ', array_map('htmlspecialchars', $resources)); ?></div>
<?php endif; ?>

<?php if (!empty($facilities)): ?>
<div class="mt-1"><strong>المرافق:</strong> <?= implode(' | ', array_map('htmlspecialchars', $facilities)); ?></div>
<?php endif; ?>

</div>
</div>

<!-- Objectives & Skills -->
<div class="card shadow border-0 mb-4">
<div class="card-header bg-success text-white">
    <i class="bi bi-bullseye"></i> الأهداف السلوكية والمهارات الأساسية
</div>
<div class="card-body">
<table class="table table-bordered">
    <tr><th width="30%">الأهداف السلوكية</th><td>
        <ol class="mb-0">
            <?php if (!empty($lesson['objective_1'])): ?><li><?= htmlspecialchars($lesson['objective_1']); ?></li><?php endif; ?>
            <?php if (!empty($lesson['objective_2'])): ?><li><?= htmlspecialchars($lesson['objective_2']); ?></li><?php endif; ?>
        </ol>
    </td></tr>
    <tr><th>المهارات الأساسية</th><td>
        <ol class="mb-0">
            <?php if (!empty($lesson['skill_1'])): ?><li><?= htmlspecialchars($lesson['skill_1']); ?></li><?php endif; ?>
            <?php if (!empty($lesson['skill_2'])): ?><li><?= htmlspecialchars($lesson['skill_2']); ?></li><?php endif; ?>
        </ol>
    </td></tr>
</table>
</div>
</div>

<!-- Main Lesson Plan Body -->
<div class="card shadow border-0 mb-4">
<div class="card-header bg-dark text-white">
    <i class="bi bi-journal-text"></i> التخطيط التفصيلي للدرس
</div>
<div class="card-body lesson-plan-body">
    <?php if (!empty($lesson['lesson_plan_html'])): ?>
        <?= $lesson['lesson_plan_html']; ?>
    <?php elseif (!empty($lesson['lesson_plan'])): ?>
        <pre class="lesson-plan-text"><?= htmlspecialchars($lesson['lesson_plan']); ?></pre>
    <?php else: ?>
        <p class="text-muted text-center">لم يتم توليد محتوى التخطيط بعد.</p>
    <?php endif; ?>
</div>
</div>

<!-- Homework & Links -->
<?php if (!empty($lesson['homework']) || !empty($lesson['national_exams_link']) || !empty($lesson['bahrain_link'])): ?>
<div class="card shadow border-0 mb-4">
<div class="card-header bg-info text-white">
    <i class="bi bi-link-45deg"></i> الإثراء والروابط
</div>
<div class="card-body">
<table class="table table-bordered">
    <?php if (!empty($lesson['homework'])): ?>
    <tr><th>الإثراء المنزلي</th><td><?= nl2br(htmlspecialchars($lesson['homework'])); ?></td></tr>
    <?php endif; ?>
    <?php if (!empty($lesson['national_exams_link'])): ?>
    <tr><th>الربط بالامتحانات الوطنية</th><td><?= nl2br(htmlspecialchars($lesson['national_exams_link'])); ?></td></tr>
    <?php endif; ?>
    <?php if (!empty($lesson['bahrain_link'])): ?>
    <tr><th>الربط بتراث البحرين</th><td><?= nl2br(htmlspecialchars($lesson['bahrain_link'])); ?></td></tr>
    <?php endif; ?>
</table>
</div>
</div>
<?php endif; ?>

<!-- Version & Stats -->
<div class="card shadow border-0 mb-4">
<div class="card-header bg-secondary text-white">
    <i class="bi bi-graph-up"></i> إحصائيات التخطيط
</div>
<div class="card-body">
<table class="table table-sm">
    <tr><th>تاريخ الإنشاء</th><td><?= date('d/m/Y h:i A', strtotime($lesson['created_at'])); ?></td></tr>
    <tr><th>الإصدار</th><td><?= $lesson['version_no']; ?></td></tr>
    <tr><th>نموذج الذكاء الاصطناعي</th><td><?= htmlspecialchars($lesson['ai_model'] ?? '-'); ?></td></tr>
    <tr><th>زمن التوليد</th><td><?= $lesson['generation_time']; ?> ثانية</td></tr>
    <tr><th>الرموز المستخدمة</th><td><?= number_format($lesson['tokens_used']); ?></td></tr>
    <tr><th>مرات التصدير PDF</th><td><?= $lesson['exported_pdf']; ?></td></tr>
    <tr><th>مرات التصدير Word</th><td><?= $lesson['exported_word']; ?></td></tr>
    <tr><th>مرات الطباعة</th><td><?= $lesson['printed_count']; ?></td></tr>
</table>
</div>
</div>

</div><!-- end main-content -->
</div>
</div>

<script>
function copyLesson() {
    const lesson = document.querySelector('.lesson-plan-body');
    if (!lesson) return;
    navigator.clipboard.writeText(lesson.innerText).then(function () {
        Swal.fire({ icon: 'success', title: 'تم النسخ', text: 'تم نسخ التخطيط إلى الحافظة.', timer: 1800, showConfirmButton: false });
    });
}
</script>

<?php if (isset($_SESSION['success'])): ?>
<script>
Swal.fire({ icon: 'success', title: 'نجاح', text: '<?= $_SESSION['success']; ?>', timer: 2000, showConfirmButton: false });
</script>
<?php unset($_SESSION['success']); ?>
<?php endif; ?>

<style>
.lesson-plan-body { font-size: 15px; line-height: 2; direction: rtl; text-align: right; }
.deep-lesson-plan { direction: rtl; text-align: right; }
.section-box { border: 1px solid #dee2e6; border-radius: 12px; margin-bottom: 20px; overflow: hidden; }
.section-title { background: #0d6efd; color: #fff; padding: 10px 16px; font-weight: bold; font-size: 15px; }
.section-title-sub { background: #6c757d; color: #fff; padding: 8px 16px; font-weight: bold; font-size: 14px; }
.section-body { padding: 14px 18px; }
.section-intro .section-title { background: #198754; }
.section-goal1 .section-title { background: #0d6efd; }
.section-goal2 .section-title { background: #6f42c1; }
.section-eval .section-title { background: #dc3545; }
.section-conclusion .section-title { background: #fd7e14; }
.section-skills .section-title { background: #20c997; }
.section-diff .section-title { background: #343a40; }
.diff-card { border-radius: 10px; padding: 12px; margin-bottom: 8px; }
.diff-green { background: #d1e7dd; border: 2px solid #198754; }
.diff-yellow { background: #fff3cd; border: 2px solid #ffc107; }
.lesson-header-table th { background: #f8f9fa; }
.card { border-radius: 14px; }
.card-header { font-weight: bold; font-size: 16px; }
</style>

<?php include '../../app/views/layouts/footer.php'; ?>
