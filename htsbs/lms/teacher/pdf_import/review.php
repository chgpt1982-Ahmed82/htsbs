<?php
/*
=====================================================================
lms/teacher/pdf_import/review.php — مراجعة التقطيع المقترح
=====================================================================
يعرض كل درس مقترح (العنوان، نطاق الصفحات، المحتوى، تلميح التمارين)
في نموذج قابل للتعديل. المعلم يستبعد/يعدّل/يدمج قبل التأكيد النهائي
في apply.php — لا شيء يُنشأ في lms_lessons قبل هذا التأكيد الصريح.
=====================================================================
*/

session_start();

require_once '../../../config/config.php';
require_once '../../../config/database.php';
require_once '../../../core/Auth.php';
require_once '../../../core/Csrf.php';
require_once '../../includes/PdfImporter.php';

if (!Auth::check() || (int)($_SESSION['role_id'] ?? 0) !== 2) {
    die('Access Denied');
}

if (!function_exists('e')) {
    function e($v): string {
        return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

$db = (new Database())->connect();

$stmt = $db->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->execute([(int)$_SESSION['user_id']]);
$teacherId = (int)$stmt->fetchColumn();

$importId = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("
    SELECT pi.*, c.course_name
    FROM lms_pdf_imports pi
    INNER JOIN courses c ON pi.course_id = c.id
    WHERE pi.id = ? AND pi.teacher_id = ?
");
$stmt->execute([$importId, $teacherId]);
$import = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$import) {
    die('غير موجود أو لا تملك صلاحية الوصول');
}

if ($import['status'] !== 'ready_for_review') {
    header("Location: process.php?id=$importId");
    exit;
}

$suggested = PdfImporter::loadJson('../../../' . $import['suggested_json_path']);

include '../../../app/views/layouts/header.php';
?>

<div class="container-fluid">
<div class="row flex-lg-row-reverse">

<?php include '../../../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">

<h4 class="fw-bold mb-1">
    <i class="bi bi-clipboard-check text-primary"></i> مراجعة تقطيع الملزمة
</h4>
<p class="text-muted small mb-4">
    مقرر: <?= e($import['course_name']); ?>
    — <?= (int)$import['total_pages']; ?> صفحة
    — <?= count($suggested); ?> درساً مقترحاً
</p>

<div class="alert alert-info small">
    <i class="bi bi-info-circle"></i>
    راجع كل درس أدناه. يمكنك تعديل العنوان، استبعاد درس (بإلغاء تحديد المربع)،
    أو تعديل المحتوى مباشرة. لن يُنشأ أي درس فعلي إلا بعد الضغط على "تأكيد الإنشاء" بالأسفل.
</div>

<form method="POST" action="apply.php">

    <?php require_once '../../../core/Csrf.php'; ?>
    <?= Csrf::field(); ?>

    <input type="hidden" name="import_id" value="<?= $importId; ?>">

    <?php foreach ($suggested as $i => $lesson): ?>

    <div class="card border-0 shadow-sm mb-3">

        <div class="card-header bg-white d-flex justify-content-between align-items-center">

            <div class="form-check">
                <input class="form-check-input" type="checkbox"
                       name="lessons[<?= $i; ?>][include]" value="1" checked
                       id="inc<?= $i; ?>">
                <label class="form-check-label fw-bold" for="inc<?= $i; ?>">
                    درس <?= $i + 1; ?>
                </label>
            </div>

            <span class="badge bg-secondary">
                صفحات <?= (int)$lesson['start_page']; ?> - <?= (int)$lesson['end_page']; ?>
            </span>

        </div>

        <div class="card-body">

            <div class="mb-3">
                <label class="form-label fw-bold">عنوان الدرس</label>
                <input type="text" name="lessons[<?= $i; ?>][title]"
                       class="form-control" value="<?= e($lesson['title']); ?>">
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">محتوى الدرس (قابل للتعديل)</label>
                <textarea name="lessons[<?= $i; ?>][content]" rows="6"
                          class="form-control" style="white-space: pre-wrap;"><?= e($lesson['content']); ?></textarea>
            </div>

            <?php if (!empty($lesson['exercises_hint'])): ?>
            <div class="mb-2">
                <label class="form-label fw-bold text-success">
                    <i class="bi bi-lightbulb"></i> تلميح: أسئلة مكتشفة في الملف لهذا الدرس
                </label>
                <textarea class="form-control bg-light" rows="4" readonly><?= e($lesson['exercises_hint']); ?></textarea>
                <small class="text-muted">
                    انسخ هذه الأسئلة يدوياً إلى صفحة "إضافة أنشطة" للدرس بعد إنشائه — التحويل التلقائي لأسئلة اختيار من متعدد غير مدعوم حالياً.
                </small>
            </div>
            <?php endif; ?>

        </div>

    </div>

    <?php endforeach; ?>

    <div class="d-flex gap-2 mb-4">
        <button type="submit" class="btn btn-success">
            <i class="bi bi-check-circle"></i> تأكيد الإنشاء
        </button>
        <a href="upload.php" class="btn btn-secondary">إلغاء والبدء من جديد</a>
    </div>

</form>

</div>
</div>
</div>

<?php include '../../../app/views/layouts/footer.php'; ?>
