<?php
/*
=====================================================================
teacher/calendar/store.php — إضافة حدث للتقويم وتعيينه للصفوف
=====================================================================
التعديلات:
  1. تسجيل العملية
  2. حماية صلاحيات + تصفية الصفوف (فقط صفوف المعلم)
  3. التحقق من النوع (ENUM) والتواريخ (النهاية بعد البداية)
  4. Transaction — لا حدث بلا ربط بالصفوف
  5. تعريب الإشعارات (كانت إنجليزية)
=====================================================================
*/

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../core/Auth.php';
require_once '../../core/Logger.php';
require_once '../../app/models/Calendar.php';
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

$calendarModel     = new Calendar();
$notificationModel = new Notification();

/* سجل المعلم */
$stmt = $db->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->execute([(int)$_SESSION['user_id']]);
$teacherId = (int)$stmt->fetchColumn();

if ($teacherId <= 0) {
    die('Teacher Not Found');
}

/* ==================== التحقق من المدخلات ==================== */
$title       = trim((string)($_POST['title'] ?? ''));
$description = trim((string)($_POST['description'] ?? ''));
$eventType   = trim((string)($_POST['event_type'] ?? ''));
$startDate   = trim((string)($_POST['start_date'] ?? ''));
$endDate     = trim((string)($_POST['end_date'] ?? ''));
$classIds    = $_POST['class_ids'] ?? [];

if ($title === '') {
    die('عنوان الحدث مطلوب');
}

/* نوع الحدث — مطابق لـ ENUM */
$allowedTypes = ['Exam', 'Assignment', 'Holiday', 'Event', 'Announcement'];

if (!in_array($eventType, $allowedTypes, true)) {
    die('نوع الحدث غير صالح');
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)
    || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
    die('تواريخ الحدث غير صالحة');
}

/* النهاية يجب ألا تسبق البداية */
if (strtotime($endDate) < strtotime($startDate)) {
    die('تاريخ النهاية يجب أن يكون بعد تاريخ البداية أو مساوياً له');
}

if (empty($classIds) || !is_array($classIds)) {
    die('يرجى اختيار صف واحد على الأقل');
}

/*
====================================================================
تصفية الصفوف — فقط صفوف هذا المعلم
====================================================================
*/
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

if (!$validClassIds) {
    die('الصفوف المختارة غير مسندة إليك');
}

/* ==================== الإنشاء داخل Transaction ==================== */
try {

    $db->beginTransaction();

    $eventId = (int)$calendarModel->create([
        'title'       => $title,
        'description' => $description,
        'event_type'  => $eventType,
        'start_date'  => $startDate,
        'end_date'    => $endDate,
        'created_by'  => $teacherId,
    ]);

    if ($eventId <= 0) {
        throw new Exception('فشل إنشاء الحدث');
    }

    $calendarModel->assignClasses($eventId, $validClassIds);

    $db->commit();

} catch (Throwable $ex) {

    if ($db->inTransaction()) {
        $db->rollBack();
    }

    Logger::log(
        'calendar',
        'store_failed',
        "فشل إضافة حدث ($title)",
        null, null, 'danger'
    );

    die('تعذر حفظ الحدث');
}

/*
====================================================================
الإشعارات (خارج Transaction) — معرّبة
====================================================================
*/
$typeLabels = [
    'Exam'         => 'اختبار',
    'Assignment'   => 'واجب',
    'Holiday'      => 'إجازة',
    'Event'        => 'فعالية',
    'Announcement' => 'إعلان',
];
$typeAr = $typeLabels[$eventType] ?? $eventType;

$message = "$typeAr: $title ($startDate"
         . ($endDate !== $startDate ? " إلى $endDate" : '') . ')';

$studentCount = 0;

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
            'حدث جديد في التقويم',
            $message,
            'calendar'
        );
        $studentCount++;
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
            'حدث في تقويم المدرسة',
            $message,
            'calendar'
        );
    }
}

/* أسماء الصفوف — للسجل */
$in = implode(',', array_fill(0, count($validClassIds), '?'));
$stmt = $db->prepare("SELECT class_name FROM classes WHERE id IN ($in)");
$stmt->execute($validClassIds);
$classNames = $stmt->fetchAll(PDO::FETCH_COLUMN);

/* ==================== التسجيل ==================== */
Logger::log(
    'calendar',
    'create_event',
    "إضافة حدث ($title) - النوع ($typeAr)"
    . " - من $startDate إلى $endDate"
    . ' | الصفوف: ' . implode('، ', $classNames)
    . " | وصل إلى $studentCount طالباً",
    'event',
    $eventId,
    'info'
);

header('Location: index.php?success=1');
exit;