<?php
/*
=====================================================================
teacher/activities/store.php — إضافة نشاط جديد
=====================================================================
🔴 خطأ قاتل مُلتَفٌّ عليه:
   Activity::getTeacherId() معرّفة private في الموديل
   (النسخة public معطّلة داخل تعليق)
   → $model->getTeacherId(...) يعطي Fatal error
   الحل هنا: استعلام مباشر بدل تعديل الموديل

التعديلات:
  1. تسجيل العملية
  2. حماية صلاحيات + التأكد أن المقرر مسند للمعلم
  3. التحقق من المدخلات (الدرجة العظمى، التاريخ)
  4. رفع مرفق النشاط بأمان (كان attachment = '' دائماً!)
=====================================================================
*/

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../core/Auth.php';
require_once '../../core/Logger.php';
require_once '../../app/models/Activity.php';

/* ==================== الصلاحية: معلم فقط ==================== */
if (!Auth::check() || (int)($_SESSION['role_id'] ?? 0) !== 2) {
    die('Access Denied');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/teacher/activities/index.php");
    exit;
}

$db = (new Database())->connect();

/* 🔴 استعلام مباشر — getTeacherId() في الموديل private */
$stmt = $db->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->execute([(int)$_SESSION['user_id']]);
$teacherId = (int)$stmt->fetchColumn();

if ($teacherId <= 0) {
    die('Teacher Not Found');
}

/* ==================== التحقق من المدخلات ==================== */
$courseId     = (int)($_POST['course_id'] ?? 0);
$title        = trim((string)($_POST['title'] ?? ''));
$instructions = trim((string)($_POST['instructions'] ?? ''));
$maxGrade     = (float)($_POST['max_grade'] ?? 0);
$dueDate      = trim((string)($_POST['due_date'] ?? ''));

if ($title === '') {
    die('عنوان النشاط مطلوب');
}

if ($courseId <= 0) {
    die('يرجى اختيار المقرر');
}

if ($maxGrade <= 0 || $maxGrade > 1000) {
    die('الدرجة العظمى يجب أن تكون بين 1 و 1000');
}

if ($dueDate === '') {
    $dueDate = date('Y-m-d H:i:s', strtotime('+7 days'));
}

/* ==================== حماية: المقرر مسند للمعلم؟ ==================== */
$stmt = $db->prepare("
    SELECT COUNT(*) FROM course_assignments
    WHERE teacher_id = ? AND course_id = ?
");
$stmt->execute([$teacherId, $courseId]);

if ((int)$stmt->fetchColumn() === 0) {

    Logger::log(
        'activities',
        'store_denied',
        "محاولة إضافة نشاط في مقرر غير مسند للمعلم (course_id=$courseId)",
        'course', $courseId, 'danger'
    );

    die('غير مصرح لك بإضافة أنشطة في هذا المقرر');
}

$stmt = $db->prepare("SELECT course_name FROM courses WHERE id = ?");
$stmt->execute([$courseId]);
$courseName = (string)$stmt->fetchColumn();

/*
====================================================================
رفع مرفق النشاط
النسخة السابقة كانت تمرّر 'attachment' => '' دائماً —
أي أن حقل المرفق في النموذج (إن وُجد) كان يُتجاهل تماماً!
====================================================================
*/
$attachment = '';
$fileInfo   = '';

if (!empty($_FILES['attachment']['name'])) {

    $file = $_FILES['attachment'];

    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        die('فشل رفع المرفق');
    }

    if ((int)$file['size'] > 20 * 1024 * 1024) {
        die('حجم المرفق يتجاوز 20 ميجابايت');
    }

    $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));

    $allowedExt = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx',
                   'jpg', 'jpeg', 'png', 'gif', 'zip', 'txt'];

    if (!in_array($ext, $allowedExt, true)) {

        Logger::log(
            'activities',
            'upload_blocked',
            "محاولة رفع مرفق بامتداد غير مسموح: ." . mb_substr($ext, 0, 20),
            null, null, 'danger'
        );

        die('نوع الملف غير مسموح');
    }

    $uploadDir = '../../uploads/activities/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    /* اسم عشوائي — لا نثق باسم المستخدم */
    $safeName = bin2hex(random_bytes(16)) . '.' . $ext;

    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $safeName)) {
        die('تعذر حفظ المرفق');
    }

    $attachment = 'uploads/activities/' . $safeName;
    $fileInfo   = ' | مرفق: ' . mb_substr((string)$file['name'], 0, 80);
}

/* ==================== الحفظ ==================== */
try {

    $model = new Activity();

    $model->create([
        'teacher_id'   => $teacherId,
        'course_id'    => $courseId,
        'title'        => $title,
        'instructions' => $instructions,
        'max_grade'    => $maxGrade,
        'due_date'     => $dueDate,
        'attachment'   => $attachment,
        'is_published' => 1,
    ]);

} catch (Throwable $ex) {

    if ($attachment !== '' && is_file('../../' . $attachment)) {
        @unlink('../../' . $attachment);
    }

    Logger::log(
        'activities',
        'store_failed',
        "فشل إضافة نشاط ($title) - مقرر ($courseName)",
        'course', $courseId, 'danger'
    );

    die('تعذر حفظ النشاط');
}

/* ==================== التسجيل ==================== */
Logger::log(
    'activities',
    'create_activity',
    "إضافة نشاط ($title) - مقرر ($courseName)"
    . " - الدرجة العظمى ($maxGrade)"
    . " - آخر موعد ($dueDate)"
    . $fileInfo,
    'course',
    $courseId,
    'info'
);

header("Location: " . BASE_URL . "/teacher/activities/index.php");
exit;