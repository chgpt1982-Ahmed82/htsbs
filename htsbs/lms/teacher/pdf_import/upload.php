<?php
/*
=====================================================================
lms/teacher/pdf_import/upload.php — رفع ملف PDF لملزمة
=====================================================================
*/

session_start();

require_once '../../../config/config.php';
require_once '../../../config/database.php';
require_once '../../../core/Auth.php';
require_once '../../../core/Logger.php';
require_once '../../../core/Csrf.php';

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

$stmt = $db->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->execute([(int)$_SESSION['user_id']]);
$teacherId = (int)$stmt->fetchColumn();

if ($teacherId <= 0) {
    die('Teacher Not Found');
}

/* مقررات المعلم — "الملزمة" = مقرر موجود بالفعل */
$stmt = $db->prepare("
    SELECT DISTINCT c.id, c.course_name, c.course_code
    FROM course_assignments ca
    INNER JOIN courses c ON ca.course_id = c.id
    WHERE ca.teacher_id = ?
    ORDER BY c.course_name
");
$stmt->execute([$teacherId]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$error = null;

/* ==================== الرفع ==================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    Csrf::verify();

    $courseId = (int)($_POST['course_id'] ?? 0);

    if ($courseId <= 0) {
        $error = 'يرجى اختيار المقرر (الملزمة)';
    } elseif (empty($_FILES['pdf_file']['name'])) {
        $error = 'يرجى اختيار ملف PDF';
    } else {

        /* التأكد أن المقرر مسند للمعلم */
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM course_assignments
            WHERE teacher_id = ? AND course_id = ?
        ");
        $stmt->execute([$teacherId, $courseId]);

        if ((int)$stmt->fetchColumn() === 0) {

            $error = 'المقرر غير مسند إليك';

        } else {

            $file = $_FILES['pdf_file'];

            if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
                $error = 'فشل رفع الملف';
            } elseif ((int)$file['size'] > 30 * 1024 * 1024) {
                $error = 'حجم الملف يتجاوز 30 ميجابايت';
            } elseif (strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION)) !== 'pdf') {
                $error = 'يجب أن يكون الملف بصيغة PDF';
            } else {

                /* فحص MIME الفعلي — لا نثق بالامتداد وحده */
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime  = (string)$finfo->file($file['tmp_name']);

                if ($mime !== 'application/pdf') {

                    Logger::log(
                        'lms_pdf_import', 'upload_blocked',
                        "محاولة رفع ملف بنوع MIME غير مطابق لـ PDF: $mime",
                        null, null, 'danger'
                    );

                    $error = 'محتوى الملف غير مطابق لصيغة PDF';

                } else {

                    $uploadDir = '../../../uploads/pdf_imports/';

                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $safeName = bin2hex(random_bytes(16)) . '.pdf';

                    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $safeName)) {

                        $error = 'تعذّر حفظ الملف';

                    } else {

                        $stmt = $db->prepare("
                            INSERT INTO lms_pdf_imports
                                (course_id, teacher_id, original_filename, stored_path, status)
                            VALUES (?, ?, ?, ?, 'uploaded')
                        ");
                        $stmt->execute([
                            $courseId,
                            $teacherId,
                            (string)$file['name'],
                            'uploads/pdf_imports/' . $safeName,
                        ]);

                        $importId = (int)$db->lastInsertId();

                        Logger::log(
                            'lms_pdf_import', 'upload',
                            "رفع ملزمة PDF (" . $file['name'] . ") لمقرر (course_id=$courseId)",
                            'lms_pdf_import', $importId, 'info'
                        );

                        header("Location: process.php?id=$importId");
                        exit;
                    }
                }
            }
        }
    }
}

include '../../../app/views/layouts/header.php';
?>

<div class="container-fluid">
<div class="row flex-lg-row-reverse">

<?php include '../../../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">

<h4 class="fw-bold mb-1">
    <i class="bi bi-file-earmark-pdf-fill text-danger"></i> استيراد ملزمة من PDF
</h4>
<p class="text-muted small mb-4">
    يستخرج النظام نص الملزمة تلقائياً ويقترح تقسيمها إلى دروس — وستراجع الاقتراح قبل التأكيد
</p>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle"></i> <?= e($error); ?>
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm">

    <div class="card-body">

        <?php if (!$courses): ?>

            <div class="alert alert-warning">
                لا توجد مقررات مسندة إليك
            </div>

        <?php else: ?>

        <form method="POST" enctype="multipart/form-data">

            <?= Csrf::field(); ?>

            <div class="mb-3">
                <label class="form-label fw-bold">المقرر (الملزمة) <span class="text-danger">*</span></label>
                <select name="course_id" class="form-select" required>
                    <option value="">— اختر المقرر —</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?= (int)$course['id']; ?>">
                            <?= e($course['course_name']); ?>
                            <?= $course['course_code'] ? ' (' . e($course['course_code']) . ')' : ''; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">ملف PDF <span class="text-danger">*</span></label>
                <input type="file" name="pdf_file" accept="application/pdf" class="form-control" required>
                <small class="text-muted">الحد الأقصى 30 ميجابايت</small>
            </div>

            <div class="alert alert-info small">
                <i class="bi bi-info-circle"></i>
                للحصول على أفضل نتيجة تقطيع تلقائي، تأكد أن عناوين الدروس في الملف بالنمط:
                <strong>"الدرس 1: العنوان"</strong> — وإلا يمكنك تعديل حدود كل درس يدوياً في شاشة المراجعة التالية.
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="bi bi-upload"></i> رفع ومعالجة الملف
            </button>

        </form>

        <?php endif; ?>

    </div>

</div>

</div>
</div>
</div>

<?php include '../../../app/views/layouts/footer.php'; ?>
