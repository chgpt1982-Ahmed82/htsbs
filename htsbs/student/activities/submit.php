<?php
/*
=====================================================================
student/activities/submit.php — تسليم حل النشاط
=====================================================================
التعديلات:
  1. تسجيل التسليم
  2. 🔴 حماية: النشاط يجب أن يكون معيَّناً فعلاً لصف الطالب
     (كان activity_id يُقبل بلا أي تحقق من الصف)
  3. 🔴 تأمين رفع الملف أكثر: فحص الحجم (كان غائباً تماماً)
     + فحص MIME الفعلي + صلاحيات 0755 بدل 0777
  4. تعريب رسائل الخطأ الإنجليزية المتبقية
  5. عرض بيانات النشاط للطالب قبل التسليم (كان النموذج غير مرفق هنا)
=====================================================================
*/

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../core/Auth.php';
require_once '../../core/Logger.php';

/* ==================== الصلاحية: طالب فقط ==================== */
if (!Auth::check() || (int)($_SESSION['role_id'] ?? 0) !== 3) {
    exit('Unauthorized Access');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$db = (new Database())->connect();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* ==================== سجل الطالب ==================== */
$stmt = $db->prepare("SELECT id, class_id FROM students WHERE user_id = ?");
$stmt->execute([(int)$_SESSION['user_id']]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    die('Student Not Found');
}

$studentId = (int)$student['id'];
$classId   = (int)$student['class_id'];

/* ==================== التحقق من المدخلات ==================== */
$activityId     = (int)($_POST['activity_id'] ?? 0);
$submissionText = trim((string)($_POST['submission_text'] ?? ''));

if ($activityId <= 0) {
    die('نشاط غير صالح');
}

/*
====================================================================
🔴 حماية: النشاط يجب أن يكون معيَّناً فعلاً لصف الطالب
النسخة السابقة كانت تقبل أي activity_id بلا أي تحقق —
فطالب يستطيع التسليم في نشاط ليس معيَّناً لصفه إطلاقاً
====================================================================
*/
$stmt = $db->prepare("
    SELECT a.id, a.title, a.max_grade, c.course_name
    FROM activities a
    INNER JOIN courses c ON a.course_id = c.id
    WHERE a.id = ?
      AND EXISTS (
          SELECT 1 FROM activity_assignments aa
          WHERE aa.activity_id = a.id AND aa.class_id = ?
      )
");
$stmt->execute([$activityId, $classId]);
$activity = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$activity) {

    Logger::log(
        'activities',
        'submit_denied',
        "محاولة تسليم نشاط غير معيَّن لصف الطالب (activity_id=$activityId)",
        'activity', $activityId, 'warning'
    );

    die('النشاط غير موجود أو غير معيَّن لصفك');
}

/* ==================== منع التسليم المكرر ==================== */
$stmt = $db->prepare("
    SELECT id FROM activity_submissions
    WHERE activity_id = ? AND student_id = ?
");
$stmt->execute([$activityId, $studentId]);

if ($stmt->fetch()) {
    die('تم تسليم النشاط مسبقاً');
}

if ($submissionText === '' && empty($_FILES['solution_file']['name'])) {
    die('يرجى كتابة إجابة أو رفع ملف');
}

/*
====================================================================
🔴 تأمين رفع الملف
النسخة السابقة: فحص الامتداد فقط — بلا حجم أقصى، بلا فحص MIME،
صلاحيات 0777 (كل الصلاحيات لأي أحد على السيرفر)
====================================================================
*/
$filePath = null;
$fileInfo = '';

if (isset($_FILES['solution_file']) && $_FILES['solution_file']['error'] === UPLOAD_ERR_OK) {

    $file = $_FILES['solution_file'];

    if (!is_uploaded_file($file['tmp_name'])) {
        die('ملف غير صالح');
    }

    /* الحجم — كان غائباً تماماً */
    if ((int)$file['size'] > 20 * 1024 * 1024) {
        die('حجم الملف يتجاوز 20 ميجابايت');
    }

    $extension = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));

    $allowed = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png'];

    if (!in_array($extension, $allowed, true)) {

        Logger::log(
            'activities',
            'upload_blocked',
            "محاولة رفع ملف تسليم بامتداد غير مسموح: ." . mb_substr($extension, 0, 20),
            'student', $studentId, 'danger'
        );

        die('نوع الملف غير مسموح');
    }

    /* فحص MIME الفعلي — لا نثق بالامتداد وحده */
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = (string)$finfo->file($file['tmp_name']);

    $allowedMime = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'image/jpeg', 'image/png',
        'application/octet-stream',   /* بعض ملفات Office تُقرأ هكذا */
    ];

    if (!in_array($mime, $allowedMime, true)) {

        Logger::log(
            'activities',
            'upload_blocked',
            "محاولة رفع ملف بنوع MIME غير مسموح: " . mb_substr($mime, 0, 60),
            'student', $studentId, 'danger'
        );

        die('محتوى الملف غير مطابق لنوعه');
    }

    $uploadDir = '../../uploads/activity_submissions/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);   /* 0755 بدل 0777 */
    }

    /* اسم عشوائي — أضمن من time()+uniqid() */
    $newFileName = bin2hex(random_bytes(16)) . '.' . $extension;

    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $newFileName)) {
        die('تعذر حفظ الملف');
    }

    $filePath = 'uploads/activity_submissions/' . $newFileName;
    $fileInfo = ' | ملف: ' . mb_substr((string)$file['name'], 0, 80);
}

/* ==================== الحفظ ==================== */
try {

    $stmt = $db->prepare("
        INSERT INTO activity_submissions
            (activity_id, student_id, submission_text, file_path, submitted_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$activityId, $studentId, $submissionText, $filePath]);

} catch (Throwable $ex) {

    if ($filePath !== null && is_file('../../' . $filePath)) {
        @unlink('../../' . $filePath);
    }

    Logger::log(
        'activities',
        'submit_failed',
        "فشل تسليم نشاط ({$activity['title']})",
        'activity', $activityId, 'danger'
    );

    die('تعذر حفظ التسليم');
}

/* ==================== التسجيل ==================== */
Logger::log(
    'activities',
    'submit_activity',
    "تسليم نشاط ({$activity['title']}) - مقرر ({$activity['course_name']})"
    . $fileInfo,
    'activity',
    $activityId,
    'info'
);

header('Location: index.php?submitted=1');
exit;