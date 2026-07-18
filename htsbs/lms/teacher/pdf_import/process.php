<?php
/*
=====================================================================
lms/teacher/pdf_import/process.php — استخراج النص + اقتراح التقطيع
=====================================================================
صفحة انتقالية: تُشغَّل تلقائياً بعد الرفع، تعرض شريط تقدم بسيطاً
ثم تُحوّل تلقائياً إلى شاشة المراجعة عند الانتهاء.
=====================================================================
*/

session_start();

require_once '../../../config/config.php';
require_once '../../../config/database.php';
require_once '../../../core/Auth.php';
require_once '../../../core/Logger.php';
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
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $db->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->execute([(int)$_SESSION['user_id']]);
$teacherId = (int)$stmt->fetchColumn();

$importId = (int)($_GET['id'] ?? 0);

if ($importId <= 0) {
    die('Import ID Not Found');
}

/* التأكد من الملكية */
$stmt = $db->prepare("
    SELECT * FROM lms_pdf_imports WHERE id = ? AND teacher_id = ?
");
$stmt->execute([$importId, $teacherId]);
$import = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$import) {

    Logger::log(
        'lms_pdf_import', 'process_denied',
        "محاولة معالجة استيراد لا يملكه المعلم (import_id=$importId)",
        null, null, 'danger'
    );

    die('غير موجود أو لا تملك صلاحية الوصول');
}

/* إن كان جاهزاً مسبقاً، انتقل مباشرة للمراجعة */
if ($import['status'] === 'ready_for_review') {
    header("Location: review.php?id=$importId");
    exit;
}

$fullPath = '../../../' . $import['stored_path'];

/* ==================== المعالجة الفعلية ==================== */
try {

    $db->prepare("UPDATE lms_pdf_imports SET status = 'extracting' WHERE id = ?")
       ->execute([$importId]);

    $pages = PdfImporter::extractPages($fullPath);

    $suggested = PdfImporter::suggestLessons($pages);

    $storageDir = '../../../uploads/pdf_imports/extracted';

    $extractedPath = PdfImporter::saveJson($storageDir, 'pages_' . $importId, $pages);
    $suggestedPath = PdfImporter::saveJson($storageDir, 'suggested_' . $importId, $suggested);

    $stmt = $db->prepare("
        UPDATE lms_pdf_imports
        SET status = 'ready_for_review',
            total_pages = ?,
            extracted_text_path = ?,
            suggested_json_path = ?
        WHERE id = ?
    ");
    $stmt->execute([
        count($pages),
        str_replace('../../../', '', $extractedPath),
        str_replace('../../../', '', $suggestedPath),
        $importId,
    ]);

    Logger::log(
        'lms_pdf_import', 'process_success',
        "استخراج ملزمة PDF بنجاح (import_id=$importId) - "
        . count($pages) . " صفحة، " . count($suggested) . " درساً مقترحاً",
        'lms_pdf_import', $importId, 'info'
    );

    header("Location: review.php?id=$importId");
    exit;

} catch (Throwable $ex) {

    $db->prepare("
        UPDATE lms_pdf_imports SET status = 'failed', error_message = ? WHERE id = ?
    ")->execute([$ex->getMessage(), $importId]);

    Logger::log(
        'lms_pdf_import', 'process_failed',
        "فشل استخراج ملزمة PDF (import_id=$importId): " . $ex->getMessage(),
        'lms_pdf_import', $importId, 'danger'
    );
}

include '../../../app/views/layouts/header.php';
?>

<div class="container-fluid">
<div class="row flex-lg-row-reverse">

<?php include '../../../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">

<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5">

        <i class="bi bi-exclamation-octagon text-danger" style="font-size: 3rem;"></i>

        <h5 class="mt-3">تعذّرت معالجة الملف</h5>

        <p class="text-muted">
            <?= e($ex->getMessage() ?? 'خطأ غير معروف'); ?>
        </p>

        <a href="upload.php" class="btn btn-primary mt-2">
            <i class="bi bi-arrow-repeat"></i> المحاولة مرة أخرى
        </a>

    </div>
</div>

</div>
</div>
</div>

<?php include '../../../app/views/layouts/footer.php'; ?>
