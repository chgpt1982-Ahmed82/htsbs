<?php
/*
=====================================================================
teacher/assignments/grade.php — تصحيح تسليم واجب
(هذا الملف يجمع العرض + المعالجة: GET يعرض النموذج، POST يحفظ)
=====================================================================
التعديلات:
  1. تسجيل التصحيح — مع توثيق الدرجة القديمة ← الجديدة
     (المعلم قد يعيد التصحيح، فالدرجة قد تكون موجودة مسبقاً)
  2. حماية صلاحيات: معلم فقط + الواجب من إنشائه هو (assignments.teacher_id)
  3. التحقق من نطاق الدرجة (0 - 100)
  4. إشعارات بالعربية بدل الإنجليزية
  5. دالة e() — تعالج NULL وتهرّب علامات التنصيص
  6. إصلاح خطأ: <textarea> كان يضيف فراغات وأسطر للملاحظات
=====================================================================
*/

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../core/Auth.php';
require_once '../../core/Logger.php';
require_once '../../app/models/Assignment.php';
require_once '../../app/models/Notification.php';

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
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$assignmentModel   = new Assignment();
$notificationModel = new Notification();

$submissionId = (int)($_GET['id'] ?? 0);

if ($submissionId <= 0) {
    die('Submission ID Not Found');
}

/* سجل المعلم */
$stmt = $db->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->execute([(int)$_SESSION['user_id']]);
$teacherId = (int)$stmt->fetchColumn();

if ($teacherId <= 0) {
    die('Teacher Not Found');
}

/*
====================================================================
جلب التسليم — مع التأكد أن الواجب من إنشاء هذا المعلم
(الاستعلام الأصلي لم يربط بجدول assignments إطلاقاً،
 فكان أي معلم يصحّح تسليمات واجبات معلمين آخرين)
====================================================================
*/
$stmt = $db->prepare("
    SELECT s.*,
           u.full_name,
           st.user_id,
           a.title       AS assignment_title,
           a.teacher_id  AS assignment_teacher_id,
           c.course_name
    FROM assignment_submissions s
    INNER JOIN students st  ON s.student_id = st.id
    INNER JOIN users u      ON st.user_id = u.id
    INNER JOIN assignments a ON s.assignment_id = a.id
    INNER JOIN courses c     ON a.course_id = c.id
    WHERE s.id = ?
");
$stmt->execute([$submissionId]);
$submission = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$submission) {
    die('Submission Not Found');
}

if ((int)$submission['assignment_teacher_id'] !== $teacherId) {

    Logger::log(
        'assignments',
        'grade_denied',
        "محاولة تصحيح تسليم واجب لا يملكه المعلم (submission_id=$submissionId)",
        'assignment',
        (int)$submission['assignment_id'],
        'danger'
    );

    die('غير مصرح لك بتصحيح هذا التسليم');
}

/*
====================================================================
✅ الدرجة القديمة — تُقرأ قبل الحفظ
المعلم قد يعيد التصحيح، فبلا القيمة القديمة يصبح السجل ناقصاً
====================================================================
*/
$oldScore    = $submission['score'];        // قد تكون NULL = لم يُصحَّح بعد
$oldFeedback = (string)($submission['feedback'] ?? '');
$isRegrade   = ($oldScore !== null);        // إعادة تصحيح؟

/* ==================== حفظ الدرجة ==================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $score    = (float)($_POST['score'] ?? -1);
    $feedback = trim((string)($_POST['feedback'] ?? ''));

    /* التحقق من نطاق الدرجة */
    if ($score < 0 || $score > 100) {
        die('الدرجة يجب أن تكون بين 0 و 100');
    }

    try {

        $assignmentModel->grade($submissionId, $score, $feedback);

    } catch (Throwable $ex) {

        Logger::log(
            'assignments',
            'grade_failed',
            "فشل حفظ تصحيح التسليم (submission_id=$submissionId)",
            null, null, 'danger'
        );

        die('تعذر حفظ الدرجة');
    }

    /*
    ================================================================
    التسجيل: نميّز بين التصحيح الأول وإعادة التصحيح
    ================================================================
    */
    $changed = !$isRegrade || (abs((float)$oldScore - $score) > 0.001);

    Logger::log(
        'assignments',
        $isRegrade ? 'regrade_submission' : 'grade_submission',
        "تصحيح واجب ({$submission['assignment_title']}) - مقرر ({$submission['course_name']})"
        . " - الطالب ({$submission['full_name']}): "
        . ($isRegrade
            ? "$oldScore ← $score"
            : "$score")
        . ($feedback !== '' ? " | ملاحظات: " . mb_substr($feedback, 0, 100) : ''),
        'student',
        (int)$submission['student_id'],
        $isRegrade ? 'warning' : 'info'
    );

    /* ==================== إشعار الطالب ==================== */
    $notificationModel->create(
        (int)$submission['user_id'],
        $isRegrade ? 'إعادة تصحيح واجب' : 'تم تصحيح واجبك',
        "الواجب ({$submission['assignment_title']}) - مقرر {$submission['course_name']}: "
        . ($isRegrade ? "تعديل الدرجة من $oldScore إلى $score" : "الدرجة $score")
        . ($feedback !== '' ? " | ملاحظات المعلم: $feedback" : ''),
        'assignment'
    );

    /* ==================== إشعار ولي الأمر (عند التعديل فقط) ==================== */
    if ($isRegrade && $changed) {

        $parentStmt = $db->prepare("
            SELECT p.user_id
            FROM parent_student ps
            INNER JOIN parents p ON ps.parent_id = p.id
            WHERE ps.student_id = ?
        ");
        $parentStmt->execute([(int)$submission['student_id']]);

        foreach ($parentStmt->fetchAll(PDO::FETCH_ASSOC) as $parent) {

            $notificationModel->create(
                (int)$parent['user_id'],
                'تعديل درجة واجب',
                "{$submission['full_name']} - الواجب ({$submission['assignment_title']}): "
                . "من $oldScore إلى $score",
                'assignment'
            );
        }
    }

    header("Location: submissions.php?id=" . (int)$submission['assignment_id'] . "&graded=1");
    exit;
}

include '../../app/views/layouts/header.php';
?>

<div class="container-fluid">
<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">

<h4 class="fw-bold mb-1">
    <i class="bi bi-clipboard-check text-warning"></i> تصحيح واجب
</h4>
<p class="text-muted small mb-4">
    <?= e($submission['assignment_title']); ?> — مقرر <?= e($submission['course_name']); ?>
</p>

<!-- ==================== بيانات التسليم ==================== -->

<div class="card border-0 shadow-sm mb-4">

    <div class="card-header bg-white d-flex justify-content-between align-items-center">

        <strong>
            <i class="bi bi-person-circle text-primary"></i>
            <?= e($submission['full_name']); ?>
        </strong>

        <small class="text-muted">
            <i class="bi bi-clock"></i>
            سُلِّم في: <?= e(date('Y-m-d H:i', strtotime((string)$submission['submitted_at']))); ?>
        </small>

    </div>

    <div class="card-body">

        <?php if ($isRegrade): ?>
            <div class="alert alert-warning small">
                <i class="bi bi-exclamation-triangle"></i>
                هذا التسليم <strong>مُصحَّح مسبقاً</strong> بدرجة
                <strong><?= e($oldScore); ?></strong>.
                أي تعديل سيُسجَّل في سجل النشاط ويُشعَر به الطالب وولي أمره.
            </div>
        <?php endif; ?>

        <?php if (!empty($submission['submission_text'])): ?>

            <p class="fw-bold mb-2">إجابة الطالب:</p>

            <div class="border rounded bg-light p-3 mb-3" style="white-space: pre-wrap;">
                <?= e($submission['submission_text']); ?>
            </div>

        <?php endif; ?>

        <?php if (!empty($submission['file_path'])): ?>

            <a href="<?= BASE_URL . '/' . e($submission['file_path']); ?>"
               target="_blank"
               class="btn btn-outline-primary btn-sm">
                <i class="bi bi-download"></i> تحميل ملف التسليم
            </a>

        <?php endif; ?>

        <?php if (empty($submission['submission_text']) && empty($submission['file_path'])): ?>
            <p class="text-muted mb-0">لم يرفق الطالب نصاً ولا ملفاً</p>
        <?php endif; ?>

    </div>

</div>

<!-- ==================== نموذج التصحيح ==================== -->

<div class="card border-0 shadow-sm">

    <div class="card-body">

        <form method="POST">

            <div class="row g-3">

                <div class="col-md-3">
                    <label class="form-label fw-bold">
                        الدرجة (0 - 100) <span class="text-danger">*</span>
                    </label>
                    <input type="number"
                           step="0.25"
                           min="0"
                           max="100"
                           name="score"
                           value="<?= e($submission['score']); ?>"
                           class="form-control"
                           required>
                </div>

                <div class="col-md-9">
                    <label class="form-label fw-bold">ملاحظات للطالب</label>
                    <textarea name="feedback"
                              rows="4"
                              class="form-control"
                              placeholder="مثال: عمل ممتاز، انتبه لتنسيق الإجابة..."><?= e($oldFeedback); ?></textarea>
                </div>

            </div>

            <hr class="my-4">

            <button type="submit" class="btn btn-success">
                <i class="bi bi-check-circle"></i>
                <?= $isRegrade ? 'حفظ التعديل' : 'حفظ الدرجة'; ?>
            </button>

            <a href="submissions.php?id=<?= (int)$submission['assignment_id']; ?>"
               class="btn btn-secondary">رجوع</a>

        </form>

    </div>

</div>

</div>
</div>
</div>

<?php include '../../app/views/layouts/footer.php'; ?>