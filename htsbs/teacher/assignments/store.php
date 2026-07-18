<?php
/*
=====================================================================
teacher/assignments/store.php — إنشاء واجب جديد
=====================================================================
التعديلات:
  1. تسجيل العملية
  2. 🔴 teacher_id يؤخذ من الجلسة لا من $_POST
     (كان (int)$_POST['teacher_id'] — أي معلم ينشئ واجباً باسم غيره!)
  3. التأكد أن المقرر مسند للمعلم
  4. تأمين رفع الملف (اسم عشوائي + فحص MIME)
=====================================================================
*/

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../core/Auth.php';
require_once '../../core/Logger.php';

/* ==================== الصلاحية: معلم فقط ==================== */
if (!Auth::check() || (int)($_SESSION['role_id'] ?? 0) !== 2) {
    exit('Unauthorized Access');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$db = (new Database())->connect();

/*
🔴 المعلم من الجلسة — لا من $_POST
النسخة السابقة: $teacherId = (int)$_POST['teacher_id']
أي معلم يعدّل الحقل المخفي وينشئ واجباً منسوباً لمعلم آخر
*/
$stmt = $db->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->execute([(int)$_SESSION['user_id']]);
$teacherId = (int)$stmt->fetchColumn();

if ($teacherId <= 0) {
    die('Teacher Not Found');
}

/* ==================== التحقق من المدخلات ==================== */
$courseId    = (int)($_POST['course_id'] ?? 0);
$title       = trim((string)($_POST['title'] ?? ''));
$description = trim((string)($_POST['description'] ?? ''));
$dueDate     = !empty($_POST['due_date']) ? (string)$_POST['due_date'] : null;

if ($title === '') {
    die('عنوان الواجب مطلوب');
}

if ($courseId <= 0) {
    die('يرجى اختيار المقرر');
}

/* ==================== حماية: المقرر مسند للمعلم؟ ==================== */
$stmt = $db->prepare("
    SELECT COUNT(*) FROM course_assignments
    WHERE teacher_id = ? AND course_id = ?
");
$stmt->execute([$teacherId, $courseId]);

if ((int)$stmt->fetchColumn() === 0) {

    Logger::log(
        'assignments',
        'store_denied',
        "محاولة إنشاء واجب في مقرر غير مسند للمعلم (course_id=$courseId)",
        'course', $courseId, 'danger'
    );

    die('غير مصرح لك بإنشاء واجبات في هذا المقرر');
}

$stmt = $db->prepare("SELECT course_name FROM courses WHERE id = ?");
$stmt->execute([$courseId]);
$courseName = (string)$stmt->fetchColumn();

/* ==================== رفع الملف ==================== */
$filePath = null;
$fileInfo = '';

if (isset($_FILES['assignment_file']) && $_FILES['assignment_file']['error'] === UPLOAD_ERR_OK) {

    $file = $_FILES['assignment_file'];

    if (!is_uploaded_file($file['tmp_name'])) {
        die('ملف غير صالح');
    }

    if ((int)$file['size'] > 20 * 1024 * 1024) {
        die('حجم الملف يتجاوز 20 ميجابايت');
    }

    $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));

    /* أُضيفت pptx/xlsx للأنواع المسموحة */
    $allowed = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'zip', 'rar'];

    if (!in_array($ext, $allowed, true)) {

        Logger::log(
            'assignments',
            'upload_blocked',
            "محاولة رفع ملف واجب بامتداد غير مسموح: ." . mb_substr($ext, 0, 20),
            null, null, 'danger'
        );

        die('نوع الملف غير مسموح');
    }

    $uploadDir = '../../uploads/assignments/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    /* اسم عشوائي — لا نستخدم اسم المستخدم */
    $fileName    = bin2hex(random_bytes(16)) . '.' . $ext;
    $destination = $uploadDir . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        die('تعذر حفظ الملف');
    }

    $filePath = 'uploads/assignments/' . $fileName;
    $fileInfo = ' | ملف: ' . mb_substr((string)$file['name'], 0, 80);
}

/* ==================== الحفظ ==================== */
try {

    $stmt = $db->prepare("
        INSERT INTO assignments
            (teacher_id, course_id, title, description, due_date, file_path)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $teacherId, $courseId, $title, $description, $dueDate, $filePath,
    ]);

    $newId = (int)$db->lastInsertId();

} catch (Throwable $ex) {

    if ($filePath !== '' && $filePath !== null && is_file('../../' . $filePath)) {
        @unlink('../../' . $filePath);
    }

    Logger::log(
        'assignments',
        'store_failed',
        "فشل إنشاء واجب ($title) - مقرر ($courseName)",
        'course', $courseId, 'danger'
    );

    die('تعذر حفظ الواجب');
}

/* ==================== التسجيل ==================== */
Logger::log(
    'assignments',
    'create_assignment',
    "إنشاء واجب ($title) - مقرر ($courseName)"
    . ($dueDate ? " - التسليم ($dueDate)" : '')
    . $fileInfo,
    'assignment',
    $newId,
    'info'
);

$_SESSION['success'] = 'تم إنشاء الواجب بنجاح';

header('Location: ' . BASE_URL . '/teacher/assignments/index.php');
exit;