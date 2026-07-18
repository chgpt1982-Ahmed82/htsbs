<?php
/*
=====================================================================
lms/teacher/pdf_import/imported.php — عرض الدروس المستوردة من ملزمة
=====================================================================
يعرض فقط الدروس التي أنشأتها عملية استيراد محددة (source_import_id)،
بخلاف lessons.php التي تعرض كل دروس المعلم بلا تمييز بين المقررات.
مطابق لأسلوب lms_init.php المستخدم في بقية صفحات وحدة LMS.
=====================================================================
*/

require_once dirname(__DIR__, 2) . '/includes/lms_init.php';

lms_require_role(2);

$teacher = $lms->getTeacherByUserId((int)$_SESSION['user_id']);
if (!$teacher) exit('Teacher Not Found');

$teacherId = (int)$teacher['id'];
$flash = null;

$importId = (int)($_GET['import_id'] ?? 0);
$courseId = (int)($_GET['course_id'] ?? 0);

if ($importId <= 0 && $courseId <= 0) {
    die('يرجى تحديد import_id أو course_id');
}

/* ==================== معالجة: نشر/إخفاء درس ==================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    lms_csrf_check();

    $action   = $_POST['action'] ?? '';
    $lessonId = (int)($_POST['lesson_id'] ?? 0);

    if ($action === 'toggle_publish' && $lessonId > 0) {

        $stmt = $db->prepare("
            SELECT id, is_published FROM lms_lessons
            WHERE id = ? AND teacher_id = ?
        ");
        $stmt->execute([$lessonId, $teacherId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {

            $newStatus = (int)$row['is_published'] === 1 ? 0 : 1;

            $db->prepare("UPDATE lms_lessons SET is_published = ? WHERE id = ? AND teacher_id = ?")
               ->execute([$newStatus, $lessonId, $teacherId]);

            $lms->log(
                (int)$_SESSION['user_id'],
                $newStatus ? 'publish_lesson' : 'unpublish_lesson',
                'lesson_id=' . $lessonId
            );

            $flash = ['success', $newStatus
                ? 'تم نشر الدرس — أصبح ظاهراً للطلاب الآن'
                : 'تم إخفاء الدرس عن الطلاب (مسودة)'];
        }
    }
}

/* ==================== جلب الدروس ==================== */
if ($importId > 0) {

    /* التأكد أن عملية الاستيراد تخص هذا المعلم */
    $stmt = $db->prepare("
        SELECT pi.*, c.course_name
        FROM lms_pdf_imports pi
        INNER JOIN courses c ON pi.course_id = c.id
        WHERE pi.id = ? AND pi.teacher_id = ?
    ");
    $stmt->execute([$importId, $teacherId]);
    $import = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$import) {
        die('عملية الاستيراد غير موجودة أو لا تملك صلاحية الوصول إليها');
    }

    $courseId   = (int)$import['course_id'];
    $courseName = (string)$import['course_name'];

    $stmt = $db->prepare("
        SELECT l.*,
               (SELECT COUNT(*) FROM lms_activities WHERE lesson_id = l.id) AS activities_count
        FROM lms_lessons l
        WHERE l.teacher_id = ? AND l.source_import_id = ?
        ORDER BY l.lesson_order
    ");
    $stmt->execute([$teacherId, $importId]);

} else {

    /* fallback: عرض دروس مقرر كامل (استُدعيت الصفحة مباشرة بلا import_id) */
    $stmt = $db->prepare("SELECT course_name FROM courses WHERE id = ?");
    $stmt->execute([$courseId]);
    $courseName = (string)$stmt->fetchColumn();

    $stmt = $db->prepare("
        SELECT l.*,
               (SELECT COUNT(*) FROM lms_activities WHERE lesson_id = l.id) AS activities_count
        FROM lms_lessons l
        WHERE l.teacher_id = ? AND l.course_id = ?
        ORDER BY l.lesson_order
    ");
    $stmt->execute([$teacherId, $courseId]);
}

$lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);

include dirname(__DIR__, 2) . '/app/views/layouts/header.php';
?>

<div class="container-fluid px-0">
<div class="row flex-lg-row-reverse g-0">

<?php include dirname(__DIR__, 2) . '/app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">

<h4 class="fw-bold mb-1">
    <i class="bi bi-collection-play text-primary"></i> الدروس المستوردة
</h4>
<p class="text-muted small mb-4">
    مقرر: <?= e($courseName); ?>
    — <?= count($lessons); ?> درساً
</p>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash[0]; ?> alert-dismissible fade show">
    <?= e($flash[1]); ?>
    <button class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (!$lessons): ?>

<div class="alert alert-warning">
    لا توجد دروس لعرضها. إن كنت وصلت هنا بعد استيراد، تأكد أن عملية الاستيراد اكتملت بنجاح.
</div>

<?php else: ?>

<div class="alert alert-info small">
    <i class="bi bi-info-circle"></i>
    الدروس أدناه <strong>مسودات</strong> حتى تضيف لها الأنشطة الخمسة وتنشرها يدوياً — لن تظهر للطلاب قبل ذلك.
</div>

<?php foreach ($lessons as $lesson): ?>

<div class="card border-0 shadow-sm mb-3">

    <div class="card-body">

        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">

            <div>
                <h6 class="fw-bold mb-1">
                    <span class="badge bg-secondary">درس <?= (int)$lesson['lesson_order']; ?></span>
                    <?= e($lesson['title']); ?>
                </h6>

                <p class="text-muted small mb-2" style="max-width: 700px;">
                    <?= e(mb_substr((string)$lesson['description'], 0, 200)); ?>
                    <?= mb_strlen((string)$lesson['description']) > 200 ? '…' : ''; ?>
                </p>

                <span class="badge <?= (int)$lesson['is_published'] === 1 ? 'bg-success' : 'bg-warning text-dark'; ?>">
                    <?= (int)$lesson['is_published'] === 1 ? 'منشور' : 'مسودة'; ?>
                </span>

                <span class="badge bg-light text-dark border">
                    <?= (int)$lesson['activities_count']; ?> / 5 أنشطة
                </span>
            </div>

            <div class="d-flex gap-2 flex-shrink-0">

                <a href="../activities.php?lesson_id=<?= (int)$lesson['id']; ?>"
                   class="btn btn-sm btn-primary">
                    <i class="bi bi-list-check"></i> الأنشطة
                </a>

                <a href="../lessons.php?edit=<?= (int)$lesson['id']; ?>"
                   class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-pencil"></i> تعديل
                </a>

                <form method="POST" class="d-inline">
                    <?= lms_csrf_field(); ?>
                    <input type="hidden" name="action" value="toggle_publish">
                    <input type="hidden" name="lesson_id" value="<?= (int)$lesson['id']; ?>">

                    <?php if ((int)$lesson['activities_count'] < 5 && (int)$lesson['is_published'] === 0): ?>
                        <button type="submit" class="btn btn-sm btn-outline-success" disabled
                                title="أضف الأنشطة الخمسة أولاً">
                            <i class="bi bi-eye"></i> نشر
                        </button>
                    <?php else: ?>
                        <button type="submit" class="btn btn-sm <?= (int)$lesson['is_published'] === 1 ? 'btn-outline-warning' : 'btn-outline-success'; ?>">
                            <i class="bi bi-eye<?= (int)$lesson['is_published'] === 1 ? '-slash' : ''; ?>"></i>
                            <?= (int)$lesson['is_published'] === 1 ? 'إخفاء' : 'نشر'; ?>
                        </button>
                    <?php endif; ?>
                </form>

            </div>

        </div>

    </div>

</div>

<?php endforeach; ?>

<?php endif; ?>

<a href="upload.php" class="btn btn-secondary mt-2">
    <i class="bi bi-arrow-repeat"></i> استيراد ملزمة أخرى
</a>

</div>
</div>
</div>

<?php include dirname(__DIR__, 2) . '/app/views/layouts/footer.php'; ?>
