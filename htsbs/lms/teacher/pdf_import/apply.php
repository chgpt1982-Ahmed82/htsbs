<?php
/*
=====================================================================
lms/teacher/pdf_import/apply.php — الإنشاء الفعلي للدروس
=====================================================================
تُنفَّذ فقط بعد تأكيد صريح من المعلم في review.php
كل درس يُنشأ بـ is_published = 0 (مسودة) — المعلم ينشرها يدوياً
بعد إضافة الأنشطة الخمسة، بدل ظهورها للطلاب فوراً بلا تمارين.
=====================================================================
*/

session_start();

require_once '../../../config/config.php';
require_once '../../../config/database.php';
require_once '../../../core/Auth.php';
require_once '../../../core/Logger.php';
require_once '../../../core/Csrf.php';

if (!Auth::check() || (int)($_SESSION['role_id'] ?? 0) !== 2) {
    die('Access Denied');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: upload.php');
    exit;
}

Csrf::verify();

$db = (new Database())->connect();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $db->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->execute([(int)$_SESSION['user_id']]);
$teacherId = (int)$stmt->fetchColumn();

$importId = (int)($_POST['import_id'] ?? 0);
$lessons  = $_POST['lessons'] ?? [];

/* التأكد من الملكية */
$stmt = $db->prepare("
    SELECT * FROM lms_pdf_imports WHERE id = ? AND teacher_id = ?
");
$stmt->execute([$importId, $teacherId]);
$import = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$import) {

    Logger::log(
        'lms_pdf_import', 'apply_denied',
        "محاولة تطبيق استيراد لا يملكه المعلم (import_id=$importId)",
        null, null, 'danger'
    );

    die('غير موجود أو لا تملك صلاحية الوصول');
}

if (!is_array($lessons) || !$lessons) {
    die('لم تُحدَّد أي دروس للإنشاء');
}

$courseId = (int)$import['course_id'];

/* آخر ترتيب موجود لهذا المقرر — الدروس الجديدة تُضاف بعده */
$stmt = $db->prepare("
    SELECT COALESCE(MAX(lesson_order), 0) FROM lms_lessons WHERE course_id = ?
");
$stmt->execute([$courseId]);
$nextOrder = (int)$stmt->fetchColumn();

$created = 0;
$skipped = 0;

try {

    $db->beginTransaction();

    foreach ($lessons as $lesson) {

        if (empty($lesson['include'])) {
            $skipped++;
            continue;
        }

        $title   = trim((string)($lesson['title'] ?? ''));
        $content = trim((string)($lesson['content'] ?? ''));

        if ($title === '') {
            $skipped++;
            continue;
        }

        $nextOrder++;

        $stmt = $db->prepare("
            INSERT INTO lms_lessons
                (course_id, teacher_id, source_import_id, lesson_order,
                 title, description, is_published)
            VALUES (?, ?, ?, ?, ?, ?, 0)
        ");
        $stmt->execute([
            $courseId,
            $teacherId,
            $importId,
            $nextOrder,
            mb_substr($title, 0, 255),
            $content,
        ]);

        $created++;
    }

    $stmt = $db->prepare("UPDATE lms_pdf_imports SET status = 'applied' WHERE id = ?");
    $stmt->execute([$importId]);

    $db->commit();

} catch (Throwable $ex) {

    if ($db->inTransaction()) {
        $db->rollBack();
    }

    Logger::log(
        'lms_pdf_import', 'apply_failed',
        "فشل إنشاء الدروس من استيراد (import_id=$importId): " . $ex->getMessage(),
        'lms_pdf_import', $importId, 'danger'
    );

    die('تعذّر إنشاء الدروس — لم يُحفظ أي شيء');
}

/* ==================== التسجيل ==================== */
Logger::log(
    'lms_pdf_import', 'apply_success',
    "إنشاء $created درساً من ملزمة PDF (import_id=$importId)"
    . ($skipped > 0 ? " | تخطّي: $skipped" : '')
    . " — كمسودات (is_published=0)",
    'course', $courseId, 'info'
);

$_SESSION['success'] = "تم إنشاء $created درساً كمسودات. أضف الأنشطة الخمسة لكل درس ثم انشره ليظهر للطلاب.";

header("Location: imported.php?import_id=$importId");
exit;
