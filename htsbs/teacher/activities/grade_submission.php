<?php
/*
=====================================================================
teacher/activities/grade_submission.php — تصحيح تسليم نشاط
(يجمع العرض + المعالجة: GET يعرض النموذج، POST يحفظ)
=====================================================================
التعديلات:
  1. تسجيل التصحيح — مع توثيق الدرجة القديمة ← الجديدة
  2. التحقق من نطاق الدرجة مقابل max_grade (لم يكن يُستخدم إطلاقاً!)
  3. إشعار الطالب وولي الأمر (لم يكن هناك أي إشعار)
  4. دالة e() — تعالج NULL (grade و feedback قد تكونان NULL)
  5. العودة إلى تسليمات النشاط نفسه بدل القائمة العامة
=====================================================================
*/

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../core/Auth.php';
require_once '../../core/Logger.php';
require_once '../../app/models/Notification.php';

/* ==================== الصلاحية: معلم فقط ==================== */
if (!Auth::check() || (int)($_SESSION['role_id'] ?? 0) !== 2) {
    exit('Unauthorized Access');
}

if (!function_exists('e')) {
    function e($v): string {
        return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

$db = (new Database())->connect();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$notificationModel = new Notification();

/*
====================================
سجل المعلم
====================================
*/
$stmt = $db->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->execute([(int)$_SESSION['user_id']]);
$teacher = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$teacher) {
    die('Teacher Not Found');
}

$teacherId = (int)$teacher['id'];

/*
====================================
التسليم
(التحقق من ملكية المعلم موجود أصلاً — AND a.teacher_id = ?)
أضفنا: max_grade واسم المقرر وuser_id للطالب
====================================
*/
$submissionId = (int)($_GET['id'] ?? 0);

if ($submissionId <= 0) {
    die('Submission ID Not Found');
}

$stmt = $db->prepare("
    SELECT s.*,
           u.full_name,
           st.user_id,
           a.title,
           a.max_grade,
           c.course_name
    FROM activity_submissions s
    INNER JOIN students st  ON s.student_id = st.id
    INNER JOIN users u      ON st.user_id = u.id
    INNER JOIN activities a ON s.activity_id = a.id
    INNER JOIN courses c    ON a.course_id = c.id
    WHERE s.id = ? AND a.teacher_id = ?
");
$stmt->execute([$submissionId, $teacherId]);
$submission = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$submission) {

    /* قد تكون محاولة وصول لتسليم نشاط معلم آخر */
    Logger::log(
        'activities',
        'grade_denied',
        "محاولة تصحيح تسليم نشاط غير موجود أو لا يملكه المعلم (submission_id=$submissionId)",
        null,
        null,
        'warning'
    );

    die('Submission Not Found');
}

/* الدرجة العظمى — 100 افتراضياً إن لم تُحدَّد للنشاط */
$maxGrade = (float)($submission['max_grade'] ?? 0);

if ($maxGrade <= 0) {
    $maxGrade = 100;
}

/*
====================================================================
✅ الدرجة القديمة — تُقرأ قبل الحفظ
====================================================================
*/
$oldGrade    = $submission['grade'];               // NULL = لم يُصحَّح بعد
$oldFeedback = (string)($submission['feedback'] ?? '');
$isRegrade   = ($oldGrade !== null);

/*
====================================
حفظ التصحيح
====================================
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $grade    = (float)($_POST['grade'] ?? -1);
    $feedback = trim((string)($_POST['feedback'] ?? ''));

    /*
    التحقق من نطاق الدرجة مقابل max_grade
    (النسخة السابقة لم تستخدم max_grade إطلاقاً —
     فكان يمكن رصد 500 في نشاط من 10)
    */
    if ($grade < 0 || $grade > $maxGrade) {
        die("الدرجة يجب أن تكون بين 0 و $maxGrade");
    }

    try {

        $stmt = $db->prepare("
            UPDATE activity_submissions
            SET grade = ?, feedback = ?
            WHERE id = ?
        ");
        $stmt->execute([$grade, $feedback, $submissionId]);

    } catch (Throwable $ex) {

        Logger::log(
            'activities',
            'grade_failed',
            "فشل حفظ تصحيح النشاط (submission_id=$submissionId)",
            null, null, 'danger'
        );

        die('تعذر حفظ التصحيح');
    }

    /*
    ================================================================
    التسجيل: نميّز بين التصحيح الأول وإعادة التصحيح
    ================================================================
    */
    $changed = !$isRegrade || (abs((float)$oldGrade - $grade) > 0.001);

    Logger::log(
        'activities',
        $isRegrade ? 'regrade_submission' : 'grade_submission',
        "تصحيح نشاط ({$submission['title']}) - مقرر ({$submission['course_name']})"
        . " - الطالب ({$submission['full_name']}): "
        . ($isRegrade
            ? "$oldGrade ← $grade / $maxGrade"
            : "$grade / $maxGrade")
        . ($feedback !== '' ? " | ملاحظات: " . mb_substr($feedback, 0, 100) : ''),
        'student',
        (int)$submission['student_id'],
        $isRegrade ? 'warning' : 'info'
    );

    /*
    ================================================================
    إشعار الطالب
    (النسخة السابقة لم تُشعر الطالب إطلاقاً —
     كان يُصحَّح نشاطه ولا يعلم!)
    ================================================================
    */
    $notificationModel->create(
        (int)$submission['user_id'],
        $isRegrade ? 'إعادة تصحيح نشاط' : 'تم تصحيح نشاطك',
        "النشاط ({$submission['title']}) - مقرر {$submission['course_name']}: "
        . ($isRegrade
            ? "تعديل الدرجة من $oldGrade إلى $grade / $maxGrade"
            : "الدرجة $grade / $maxGrade")
        . ($feedback !== '' ? " | ملاحظات المعلم: $feedback" : ''),
        'activity'
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
                'تعديل درجة نشاط',
                "{$submission['full_name']} - النشاط ({$submission['title']}): "
                . "من $oldGrade إلى $grade / $maxGrade",
                'activity'
            );
        }
    }

    header("Location: submissions.php?activity_id=" . (int)$submission['activity_id'] . "&graded=1");
    exit;
}

include '../../app/views/layouts/header.php';
?>

<div class="container-fluid">
<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">

<div class="card border-0 shadow-sm">

    <div class="card-header bg-success text-white">
        <h5 class="mb-0">
            <i class="bi bi-clipboard-check"></i> تصحيح النشاط
        </h5>
    </div>

    <div class="card-body">

        <?php if ($isRegrade): ?>
            <div class="alert alert-warning small">
                <i class="bi bi-exclamation-triangle"></i>
                هذا التسليم <strong>مُصحَّح مسبقاً</strong> بدرجة
                <strong><?= e($oldGrade); ?> / <?= e($maxGrade); ?></strong>.
                أي تعديل سيُسجَّل في سجل النشاط ويُشعَر به الطالب وولي أمره.
            </div>
        <?php endif; ?>

        <div class="row g-2 mb-3 small">

            <div class="col-md-4">
                <strong>الطالب:</strong> <?= e($submission['full_name']); ?>
            </div>

            <div class="col-md-4">
                <strong>النشاط:</strong> <?= e($submission['title']); ?>
            </div>

            <div class="col-md-4">
                <strong>المقرر:</strong> <?= e($submission['course_name']); ?>
            </div>

            <div class="col-md-4">
                <strong>الدرجة العظمى:</strong> <?= e($maxGrade); ?>
            </div>

            <div class="col-md-8">
                <strong>سُلِّم في:</strong>
                <?= $submission['submitted_at']
                    ? e(date('Y-m-d H:i', strtotime((string)$submission['submitted_at'])))
                    : '—'; ?>
            </div>

        </div>

        <hr>

        <?php if (!empty($submission['submission_text'])): ?>

            <p class="fw-bold mb-2">نص الحل:</p>

            <div class="border rounded bg-light p-3 mb-3" style="white-space: pre-wrap;">
                <?= e($submission['submission_text']); ?>
            </div>

        <?php endif; ?>

        <?php if (!empty($submission['file_path'])): ?>

            <a href="<?= BASE_URL . '/' . e($submission['file_path']); ?>"
               target="_blank"
               class="btn btn-outline-primary btn-sm mb-3">
                <i class="bi bi-download"></i> تحميل الملف
            </a>

        <?php endif; ?>

        <?php if (empty($submission['submission_text']) && empty($submission['file_path'])): ?>
            <p class="text-muted">لم يرفق الطالب نصاً ولا ملفاً</p>
        <?php endif; ?>

        <hr>

        <!-- ==================== نموذج التصحيح ==================== -->

        <form method="POST">

            <div class="row g-3">

                <div class="col-md-3">
                    <label class="form-label fw-bold">
                        الدرجة (0 - <?= e($maxGrade); ?>) <span class="text-danger">*</span>
                    </label>
                    <input type="number"
                           step="0.25"
                           min="0"
                           max="<?= e($maxGrade); ?>"
                           name="grade"
                           class="form-control"
                           value="<?= e($submission['grade']); ?>"
                           required>
                </div>

                <div class="col-md-9">
                    <label class="form-label fw-bold">التغذية الراجعة</label>
                    <textarea name="feedback"
                              class="form-control"
                              rows="4"
                              placeholder="ملاحظات تشجيعية أو توجيهية للطالب..."><?= e($oldFeedback); ?></textarea>
                </div>

            </div>

            <hr class="my-4">

            <button type="submit" class="btn btn-success">
                <i class="bi bi-check-circle"></i>
                <?= $isRegrade ? 'حفظ التعديل' : 'حفظ التصحيح'; ?>
            </button>

            <a href="submissions.php" class="btn btn-secondary">رجوع</a>

        </form>

    </div>

</div>

</div>
</div>
</div>

<?php include '../../app/views/layouts/footer.php'; ?>