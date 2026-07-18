<?php
/*
=====================================================================
teacher/lessons/store.php — إضافة درس جديد
=====================================================================
التعديلات:
  1. 🔴 تأمين رفع الملفات: فحص الامتداد والحجم و MIME
     (كان يقبل shell.php → تنفيذ كود على الخادم!)
  2. تسجيل العملية في السجلات
  3. حماية صلاحيات: معلم فقط + المقرر مسند إليه فعلاً
  4. التحقق من صحة نوع الدرس (ENUM) ورابط الفيديو
  5. اسم ملف عشوائي — يمنع كشف أسماء الملفات وتخمينها
=====================================================================
*/

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../core/Auth.php';
require_once '../../core/Logger.php';
require_once '../../app/models/Lesson.php';

/* ==================== الصلاحية: معلم فقط ==================== */
if (!Auth::check() || (int)($_SESSION['role_id'] ?? 0) !== 2) {
    die('Access Denied');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/teacher/lessons/index.php");
    exit;
}

$db = (new Database())->connect();

$model     = new Lesson();
$teacherId = (int)$model->getTeacherId((int)$_SESSION['user_id']);

if ($teacherId <= 0) {
    die('Teacher Not Found');
}

/* ==================== التحقق من المدخلات ==================== */
$courseId    = (int)($_POST['course_id'] ?? 0);
$title       = trim((string)($_POST['lesson_title'] ?? ''));
$description = trim((string)($_POST['lesson_description'] ?? ''));
$type        = trim((string)($_POST['lesson_type'] ?? ''));
$videoLink   = trim((string)($_POST['video_link'] ?? ''));

if ($title === '') {
    die('عنوان الدرس مطلوب');
}

if ($courseId <= 0) {
    die('يرجى اختيار المقرر');
}

/* نوع الدرس — مطابق لـ ENUM */
$allowedTypes = ['pdf', 'ppt', 'video', 'link'];

if (!in_array($type, $allowedTypes, true)) {
    die('نوع الدرس غير صالح');
}

/* رابط الفيديو — إن وُجد يجب أن يكون رابطاً صحيحاً */
if ($videoLink !== '' && !filter_var($videoLink, FILTER_VALIDATE_URL)) {
    die('رابط الفيديو غير صالح');
}

/*
====================================================================
حماية: التأكد أن المقرر مسند لهذا المعلم فعلاً
بدونها يستطيع أي معلم إضافة دروس في مقررات لا يدرّسها
====================================================================
*/
$stmt = $db->prepare("
    SELECT COUNT(*) FROM course_assignments
    WHERE teacher_id = ? AND course_id = ?
");
$stmt->execute([$teacherId, $courseId]);

if ((int)$stmt->fetchColumn() === 0) {

    Logger::log(
        'lessons',
        'store_denied',
        "محاولة إضافة درس في مقرر غير مسند للمعلم (course_id=$courseId)",
        'course',
        $courseId,
        'danger'
    );

    die('غير مصرح لك بإضافة دروس في هذا المقرر');
}

/* اسم المقرر — للسجل */
$stmt = $db->prepare("SELECT course_name FROM courses WHERE id = ?");
$stmt->execute([$courseId]);
$courseName = (string)$stmt->fetchColumn();

/*
====================================================================
🔴 رفع الملف — تأمين كامل
النسخة السابقة كانت تقبل أي ملف بأي امتداد بأي حجم:
    move_uploaded_file($_FILES['lesson_file']['tmp_name'], $uploadDir . $fileName);
أي معلم يرفع shell.php ثم يفتحه بالمتصفح → تنفيذ كود على الخادم!
====================================================================
*/
$filePath = '';
$fileInfo = '';

if (!empty($_FILES['lesson_file']['name'])) {

    $file = $_FILES['lesson_file'];

    /* (1) خطأ في الرفع؟ */
    if ($file['error'] !== UPLOAD_ERR_OK) {
        die('فشل رفع الملف');
    }

    /* (2) ملف مرفوع فعلاً عبر HTTP؟ */
    if (!is_uploaded_file($file['tmp_name'])) {
        die('ملف غير صالح');
    }

    /* (3) الحجم: 20 ميجابايت كحد أقصى */
    $maxSize = 20 * 1024 * 1024;

    if ((int)$file['size'] > $maxSize) {
        die('حجم الملف يتجاوز 20 ميجابايت');
    }

    /* (4) الامتداد: قائمة بيضاء صارمة */
    $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));

    $allowedExt = [
        'pdf',
        'ppt', 'pptx',
        'doc', 'docx',
        'xls', 'xlsx',
        'jpg', 'jpeg', 'png', 'gif',
        'zip',
    ];

    if (!in_array($ext, $allowedExt, true)) {

        Logger::log(
            'lessons',
            'upload_blocked',
            "محاولة رفع ملف بامتداد غير مسموح: ." . mb_substr($ext, 0, 20)
            . " | الاسم: " . mb_substr((string)$file['name'], 0, 100),
            null,
            null,
            'danger'
        );

        die('نوع الملف غير مسموح — المسموح: PDF, PPT, DOC, XLS, صور, ZIP');
    }

    /* (5) النوع الفعلي (MIME) — لا نثق بالامتداد وحده */
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = (string)$finfo->file($file['tmp_name']);

    $allowedMime = [
        'application/pdf',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'image/jpeg', 'image/png', 'image/gif',
        'application/zip', 'application/x-zip-compressed',
        'application/octet-stream',   /* بعض ملفات Office تُقرأ هكذا */
    ];

    if (!in_array($mime, $allowedMime, true)) {

        Logger::log(
            'lessons',
            'upload_blocked',
            "محاولة رفع ملف بنوع MIME غير مسموح: " . mb_substr($mime, 0, 60),
            null, null, 'danger'
        );

        die('محتوى الملف غير مطابق لنوعه');
    }

    /* (6) مجلد الرفع */
    $uploadDir = '../../uploads/lessons/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    /*
    (7) اسم عشوائي — لا نستخدم اسم الملف الأصلي إطلاقاً
    اسم المستخدم قد يحتوي: ../  أو  shell.php.pdf  أو رموزاً خطرة
    */
    $safeName = bin2hex(random_bytes(16)) . '.' . $ext;

    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $safeName)) {
        die('تعذر حفظ الملف');
    }

    $filePath = 'uploads/lessons/' . $safeName;

    $fileInfo = ' | ملف: ' . mb_substr((string)$file['name'], 0, 80)
              . ' (' . round((int)$file['size'] / 1024) . ' KB)';
}

/* ==================== الحفظ ==================== */
try {

    $model->create([
        'teacher_id'         => $teacherId,
        'course_id'          => $courseId,
        'lesson_title'       => $title,
        'lesson_description' => $description,
        'lesson_type'        => $type,
        'file_path'          => $filePath,
        'video_link'         => $videoLink,
    ]);

} catch (Throwable $ex) {

    /* حذف الملف المرفوع — لا نترك ملفات يتيمة */
    if ($filePath !== '' && is_file('../../' . $filePath)) {
        @unlink('../../' . $filePath);
    }

    Logger::log(
        'lessons',
        'store_failed',
        "فشل إضافة درس ($title) - مقرر ($courseName)",
        'course', $courseId, 'danger'
    );

    die('تعذر حفظ الدرس');
}

/* ==================== التسجيل ==================== */
Logger::log(
    'lessons',
    'create_lesson',
    "إضافة درس ($title) - مقرر ($courseName) - النوع ($type)"
    . $fileInfo
    . ($videoLink !== '' ? ' | فيديو: ' . mb_substr($videoLink, 0, 80) : ''),
    'course',
    $courseId,
    'info'
);

header("Location: " . BASE_URL . "/teacher/lessons/index.php");
exit;