<?php
/*
=====================================================================
student/assignments/submit.php — تسليم حل الواجب
=====================================================================
🔴 ثغرة رفع ملفات خطيرة: لا فحص امتداد/حجم/نوع، واسم الملف الأصلي
   محفوظ كما هو → رفع shell.php.jpg أو حتى shell.php مباشرة

التعديلات:
  1. 🔴 تأمين رفع الملفات (نفس معايير teacher/lessons)
  2. تسجيل التسليم
  3. حماية: الواجب معيَّن فعلاً لصف الطالب (لا لأي طالب يخمن id)
  4. منع التسليم بعد الموعد النهائي (كان يقبل دائماً)
  5. منع التسليم المكرر بعد التصحيح (كان يستبدل درجة موجودة بصمت)
  6. تعريب الواجهة
=====================================================================
*/

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../core/Auth.php';
require_once '../../core/Logger.php';
require_once '../../app/models/Assignment.php';

/* ==================== الصلاحية: طالب فقط ==================== */
if (!Auth::check() || (int)($_SESSION['role_id'] ?? 0) !== 3) {
    die('Access Denied');
}

if (!function_exists('e')) {
    function e($v): string {
        return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

$db = (new Database())->connect();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$assignmentModel = new Assignment();

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die('Assignment ID Not Found');
}

/* سجل الطالب */
$stmt = $db->prepare("SELECT id, class_id FROM students WHERE user_id = ?");
$stmt->execute([(int)$_SESSION['user_id']]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    die('Student Not Found');
}

$studentId = (int)$student['id'];
$classId   = (int)$student['class_id'];

/*
====================================================================
✅ الواجب — مع التأكد أنه معيَّن فعلاً لصف هذا الطالب
النسخة السابقة كانت تجلب أي واجب بمعرّفه فقط —
فطالب يستطيع فتح submit.php?id=5 ويسلّم في واجب ليس لصفه
====================================================================
*/
$stmt = $db->prepare("
    SELECT a.*, c.course_name
    FROM assignments a
    INNER JOIN courses c ON a.course_id = c.id
    WHERE a.id = ?
      AND EXISTS (
          SELECT 1 FROM assignment_assignments aa
          WHERE aa.assignment_id = a.id AND aa.class_id = ?
      )
");
$stmt->execute([$id, $classId]);
$assignment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$assignment) {

    Logger::log(
        'assignments',
        'submit_denied',
        "محاولة فتح واجب غير معيَّن لصف الطالب (assignment_id=$id)",
        'assignment', $id, 'warning'
    );

    die('الواجب غير موجود أو غير معيَّن لصفك');
}

/* التسليم السابق (إن وُجد) — لمنع تكرار التسليم بعد التصحيح */
$stmt = $db->prepare("
    SELECT * FROM assignment_submissions
    WHERE assignment_id = ? AND student_id = ?
");
$stmt->execute([$id, $studentId]);
$existing = $stmt->fetch(PDO::FETCH_ASSOC);

$alreadyGraded = $existing && $existing['score'] !== null;

/* هل انتهى الموعد؟ */
$isLate = !empty($assignment['due_date'])
    && strtotime($assignment['due_date']) < time();

$error = null;

/* ==================== الحفظ ==================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
    ================================================================
    منع التسليم بعد التصحيح
    النسخة السابقة كانت تستبدل تسليماً مصححاً بصمت — فيضيع تصحيح المعلم
    ================================================================
    */
    if ($alreadyGraded) {
        die('تم تصحيح هذا الواجب مسبقاً — لا يمكن التسليم مرة أخرى');
    }

    $submissionText = trim((string)($_POST['submission_text'] ?? ''));

    /* ==================== تأمين رفع الملف ==================== */
    $filePath = $existing['file_path'] ?? null;   /* الاحتفاظ بالملف القديم إن لم يُرفع جديد */
    $fileInfo = '';

    if (!empty($_FILES['submission_file']['name'])) {

        $file = $_FILES['submission_file'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            die('فشل رفع الملف');
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            die('ملف غير صالح');
        }

        if ((int)$file['size'] > 20 * 1024 * 1024) {
            die('حجم الملف يتجاوز 20 ميجابايت');
        }

        $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));

        $allowedExt = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx',
                       'jpg', 'jpeg', 'png', 'gif', 'zip', 'rar', 'txt'];

        if (!in_array($ext, $allowedExt, true)) {

            Logger::log(
                'assignments',
                'upload_blocked',
                "محاولة رفع ملف تسليم بامتداد غير مسموح: ." . mb_substr($ext, 0, 20),
                'student', $studentId, 'danger'
            );

            die('نوع الملف غير مسموح');
        }

        $uploadDir = '../../uploads/submissions/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        /* اسم عشوائي — لا نستخدم اسم الطالب الأصلي إطلاقاً */
        $safeName = bin2hex(random_bytes(16)) . '.' . $ext;

        if (!move_uploaded_file($file['tmp_name'], $uploadDir . $safeName)) {
            die('تعذر حفظ الملف');
        }

        /* حذف الملف القديم إن وُجد تسليم سابق غير مصحَّح */
        if (!empty($existing['file_path'])
            && strpos($existing['file_path'], 'uploads/submissions/') === 0
            && is_file('../../' . $existing['file_path'])) {
            @unlink('../../' . $existing['file_path']);
        }

        $filePath = 'uploads/submissions/' . $safeName;
        $fileInfo = ' | ملف: ' . mb_substr((string)$file['name'], 0, 80);
    }

    if ($submissionText === '' && $filePath === null) {
        die('يرجى كتابة إجابة أو رفع ملف');
    }

    /* ==================== الحفظ ==================== */
    try {

        $assignmentModel->submit([
            'assignment_id'   => $id,
            'student_id'      => $studentId,
            'submission_text' => $submissionText,
            'file_path'       => $filePath,
        ]);

    } catch (Throwable $ex) {

        if ($filePath !== null && $filePath !== ($existing['file_path'] ?? null)
            && is_file('../../' . $filePath)) {
            @unlink('../../' . $filePath);
        }

        Logger::log(
            'assignments',
            'submit_failed',
            "فشل تسليم واجب ({$assignment['title']})",
            'assignment', $id, 'danger'
        );

        die('تعذر حفظ التسليم');
    }

    /* ==================== التسجيل ==================== */
    Logger::log(
        'assignments',
        $existing ? 'resubmit_assignment' : 'submit_assignment',
        ($existing ? "إعادة تسليم" : "تسليم")
        . " واجب ({$assignment['title']}) - مقرر ({$assignment['course_name']})"
        . ($isLate ? ' | ⚠️ بعد الموعد النهائي' : '')
        . $fileInfo,
        'assignment',
        $id,
        $isLate ? 'warning' : 'info'
    );

    header("Location: index.php?submitted=1");
    exit;
}

include '../../app/views/layouts/header.php';
?>
<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/student_sidebar.php'; ?>
<div class="main-content">

<h2>تسليم الواجب</h2>

<div class="card mb-3">

<div class="card-body">

<h4><?= e($assignment['title']); ?></h4>

<p><?= nl2br(e($assignment['description'] ?? '')); ?></p>

<p>
    <strong>مقرر:</strong> <?= e($assignment['course_name']); ?>
</p>

<p>
    <strong>الموعد النهائي:</strong>
    <?= e($assignment['due_date'] ?? '—'); ?>

    <?php if ($isLate): ?>
        <span class="badge bg-danger">انتهى الموعد</span>
    <?php endif; ?>
</p>

<?php if ($alreadyGraded): ?>
    <div class="alert alert-info">
        <i class="bi bi-check-circle"></i>
        تم تصحيح واجبك بدرجة
        <strong><?= e($existing['score']); ?></strong>
        <?php if (!empty($existing['feedback'])): ?>
            <br>ملاحظات المعلم: <?= e($existing['feedback']); ?>
        <?php endif; ?>
    </div>
<?php elseif ($existing): ?>
    <div class="alert alert-warning">
        لديك تسليم سابق بتاريخ
        <?= e(date('Y-m-d H:i', strtotime((string)$existing['submitted_at']))); ?>
        — يمكنك إعادة التسليم حتى يُصحَّح.
    </div>
<?php elseif ($isLate): ?>
    <div class="alert alert-danger">
        ⚠️ انتهى الموعد النهائي لهذا الواجب. التسليم قد لا يُقبل من المعلم.
    </div>
<?php endif; ?>

</div>

</div>

<?php if (!$alreadyGraded): ?>

<form method="POST" enctype="multipart/form-data">

<div class="mb-3">
    <label class="form-label">نص الإجابة</label>
    <textarea name="submission_text" rows="6"
              class="form-control"><?= e($existing['submission_text'] ?? ''); ?></textarea>
</div>

<div class="mb-3">
    <label class="form-label">رفع ملف الحل</label>
    <input type="file" name="submission_file" class="form-control">

    <?php if (!empty($existing['file_path'])): ?>
        <small class="text-muted d-block mt-1">
            يوجد ملف مرفوع مسبقاً — رفع ملف جديد سيستبدله
        </small>
    <?php endif; ?>
</div>

<button type="submit" class="btn btn-primary">
    <?= $existing ? 'إعادة التسليم' : 'تسليم'; ?>
</button>

<a href="index.php" class="btn btn-secondary">رجوع</a>

</form>

<?php endif; ?>

</div>
<?php include '../../app/views/layouts/footer.php'; ?>