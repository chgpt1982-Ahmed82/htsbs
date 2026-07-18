<?php
/*
=====================================================================
teacher/exams/store.php — إنشاء اختبار وتعيينه للصفوف وإشعار الطلاب
=====================================================================
التعديلات:
  1. تسجيل العملية
  2. حماية صلاحيات + التأكد أن المقرر والصفوف مسندة للمعلم
  3. التحقق من المدخلات (النوع ENUM، الدرجة، التاريخ)
  4. Transaction — لا اختبار بلا تعيينات ولا العكس
  5. تعريب الإشعارات (كانت إنجليزية)
=====================================================================
*/

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../core/Auth.php';
require_once '../../core/Logger.php';
require_once '../../app/models/Exam.php';
require_once '../../app/models/Notification.php';

/* ==================== الصلاحية: معلم فقط ==================== */
if (!Auth::check() || (int)($_SESSION['role_id'] ?? 0) !== 2) {
    die('Access Denied');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: create.php');
    exit;
}

$db = (new Database())->connect();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$examModel         = new Exam();
$notificationModel = new Notification();

/* سجل المعلم */
$stmt = $db->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->execute([(int)$_SESSION['user_id']]);
$teacherId = (int)$stmt->fetchColumn();

if ($teacherId <= 0) {
    die('Teacher Not Found');
}

/* ==================== التحقق من المدخلات ==================== */
$courseId = (int)($_POST['course_id'] ?? 0);
$examName = trim((string)($_POST['exam_name'] ?? ''));
$examType = trim((string)($_POST['exam_type'] ?? ''));
$examDate = trim((string)($_POST['exam_date'] ?? ''));
$maxMarks = (float)($_POST['max_marks'] ?? 0);
$classIds = $_POST['class_ids'] ?? [];

if ($examName === '') {
    die('اسم الاختبار مطلوب');
}

if ($courseId <= 0) {
    die('يرجى اختيار المقرر');
}

/* نوع الاختبار — مطابق لـ ENUM */
$allowedTypes = ['Quiz', 'Midterm', 'Final', 'Practical'];

if (!in_array($examType, $allowedTypes, true)) {
    die('نوع الاختبار غير صالح');
}

if ($maxMarks <= 0 || $maxMarks > 1000) {
    die('الدرجة النهائية يجب أن تكون بين 1 و 1000');
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $examDate)) {
    die('تاريخ الاختبار غير صالح');
}

/* ==================== حماية: المقرر مسند للمعلم؟ ==================== */
$stmt = $db->prepare("
    SELECT COUNT(*) FROM course_assignments
    WHERE teacher_id = ? AND course_id = ?
");
$stmt->execute([$teacherId, $courseId]);

if ((int)$stmt->fetchColumn() === 0) {

    Logger::log(
        'exams',
        'store_denied',
        "محاولة إنشاء اختبار في مقرر غير مسند للمعلم (course_id=$courseId)",
        'course', $courseId, 'danger'
    );

    die('غير مصرح لك بإنشاء اختبار في هذا المقرر');
}

$stmt = $db->prepare("SELECT course_name FROM courses WHERE id = ?");
$stmt->execute([$courseId]);
$courseName = (string)$stmt->fetchColumn();

/* ==================== تصفية الصفوف — فقط صفوف المعلم ==================== */
$stmt = $db->prepare("SELECT DISTINCT class_id FROM course_assignments WHERE teacher_id = ?");
$stmt->execute([$teacherId]);
$allowedClasses = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

$validClassIds = [];

foreach ((array)$classIds as $cid) {
    $cid = (int)$cid;
    if ($cid > 0 && in_array($cid, $allowedClasses, true)) {
        $validClassIds[] = $cid;
    }
}

/* ==================== الإنشاء داخل Transaction ==================== */
try {

    $db->beginTransaction();

    $examId = (int)$examModel->create([
        'teacher_id' => $teacherId,
        'course_id'  => $courseId,
        'exam_name'  => $examName,
        'exam_type'  => $examType,
        'exam_date'  => $examDate,
        'max_marks'  => $maxMarks,
    ]);

    if ($examId <= 0) {
        throw new Exception('فشل إنشاء الاختبار');
    }

    /* ربط الصفوف */
    $insert = $db->prepare("
        INSERT INTO exam_assignments (exam_id, class_id) VALUES (?, ?)
    ");

    foreach ($validClassIds as $cid) {
        $insert->execute([$examId, $cid]);
    }

    $db->commit();

} catch (Throwable $ex) {

    if ($db->inTransaction()) {
        $db->rollBack();
    }

    Logger::log(
        'exams',
        'store_failed',
        "فشل إنشاء اختبار ($examName) - مقرر ($courseName)",
        'course', $courseId, 'danger'
    );

    die('تعذر إنشاء الاختبار');
}

/*
====================================================================
الإشعارات (خارج Transaction — فشلها لا يُلغي الاختبار)
تعريب: كانت "New Exam Available" و "has been scheduled"
====================================================================
*/
$typeLabels = [
    'Quiz' => 'اختبار قصير', 'Midterm' => 'نصفي',
    'Final' => 'نهائي', 'Practical' => 'عملي',
];
$typeAr = $typeLabels[$examType] ?? $examType;

foreach ($validClassIds as $classId) {

    /* إشعار الطلاب */
    $studentStmt = $db->prepare("
        SELECT u.id
        FROM students s
        INNER JOIN users u ON s.user_id = u.id
        WHERE s.class_id = ?
    ");
    $studentStmt->execute([$classId]);

    foreach ($studentStmt->fetchAll(PDO::FETCH_ASSOC) as $student) {
        $notificationModel->create(
            (int)$student['id'],
            'اختبار جديد',
            "تم جدولة اختبار ($typeAr): $examName - مقرر $courseName بتاريخ $examDate",
            'exam'
        );
    }

    /* إشعار أولياء الأمور */
    $parentStmt = $db->prepare("
        SELECT DISTINCT p.user_id
        FROM students s
        INNER JOIN parent_student ps ON s.id = ps.student_id
        INNER JOIN parents p ON ps.parent_id = p.id
        WHERE s.class_id = ?
    ");
    $parentStmt->execute([$classId]);

    foreach ($parentStmt->fetchAll(PDO::FETCH_ASSOC) as $parent) {
        $notificationModel->create(
            (int)$parent['user_id'],
            'إشعار اختبار',
            "تم جدولة اختبار ($typeAr): $examName بتاريخ $examDate",
            'exam'
        );
    }
}

/* ==================== التسجيل ==================== */
$classCount = count($validClassIds);

Logger::log(
    'exams',
    'create_exam',
    "إنشاء اختبار ($examName) - مقرر ($courseName)"
    . " - النوع ($typeAr)"
    . " - الدرجة ($maxMarks)"
    . " - التاريخ ($examDate)"
    . " - الصفوف: $classCount",
    'exam',
    $examId,
    'info'
);

header("Location: index.php?success=1");
exit;