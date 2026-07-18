<?php
/*
=====================================================================
teacher/announcements/store.php — نشر إعلان للصفوف
=====================================================================
التعديلات:
  1. تسجيل نشر الإعلان (يصل لطلاب وأولياء أمور — يستحق التوثيق)
  2. حماية صلاحيات + تصفية الصفوف (فقط صفوف المعلم)
  3. Transaction — لا إعلان بلا ربط بالصفوف
  4. تعريب الإشعارات (كانت إنجليزية)
  5. تضمين نص الإعلان في الإشعار بدل العنوان فقط
=====================================================================
*/

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../core/Auth.php';
require_once '../../core/Logger.php';
require_once '../../app/models/Announcement.php';
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

$announcementModel = new Announcement();
$notificationModel = new Notification();

/* سجل المعلم */
$stmt = $db->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->execute([(int)$_SESSION['user_id']]);
$teacherId = (int)$stmt->fetchColumn();

if ($teacherId <= 0) {
    die('Teacher Not Found');
}

/* ==================== التحقق من المدخلات ==================== */
$title    = trim((string)($_POST['title'] ?? ''));
$message  = trim((string)($_POST['message'] ?? ''));
$classIds = $_POST['class_ids'] ?? [];

if ($title === '') {
    die('عنوان الإعلان مطلوب');
}

if ($message === '') {
    die('نص الإعلان مطلوب');
}

if (empty($classIds) || !is_array($classIds)) {
    die('يرجى اختيار صف واحد على الأقل');
}

/*
====================================================================
تصفية الصفوف — فقط صفوف هذا المعلم
بدونها يستطيع المعلم بث إعلان لأي صف في المدرسة (بتعديل class_ids)
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

    $announcementId = (int)$announcementModel->create([
        'title'      => $title,
        'message'    => $message,
        'role'       => 'student',
        'created_by' => $teacherId,
    ]);

    if ($announcementId <= 0) {
        throw new Exception('فشل إنشاء الإعلان');
    }

    $announcementModel->assignClasses($announcementId, $validClassIds);

    $db->commit();

} catch (Throwable $ex) {

    if ($db->inTransaction()) {
        $db->rollBack();
    }

    Logger::log(
        'announcements',
        'store_failed',
        "فشل نشر إعلان ($title)",
        null, null, 'danger'
    );

    die('تعذر نشر الإعلان');
}

/*
====================================================================
الإشعارات (خارج Transaction)
تعريب: كانت "New Announcement" و "School Announcement"
وتضمين مقتطف من نص الإعلان بدل العنوان وحده
====================================================================
*/
$snippet = mb_substr($message, 0, 120) . (mb_strlen($message) > 120 ? '…' : '');

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
    $studentRows = $studentStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($studentRows as $student) {
        $notificationModel->create(
            (int)$student['id'],
            'إعلان جديد: ' . $title,
            $snippet,
            'announcement'
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
            'إعلان من المدرسة: ' . $title,
            $snippet,
            'announcement'
        );
    }
}

/* أسماء الصفوف — للسجل */
$classNames = [];
$in = implode(',', array_fill(0, count($validClassIds), '?'));
$stmt = $db->prepare("SELECT class_name FROM classes WHERE id IN ($in)");
$stmt->execute($validClassIds);
$classNames = $stmt->fetchAll(PDO::FETCH_COLUMN);

/* ==================== التسجيل ==================== */
Logger::log(
    'announcements',
    'create_announcement',
    "نشر إعلان ($title)"
    . ' | الصفوف: ' . implode('، ', $classNames)
    . " | وصل إلى $studentCount طالباً"
    . ' | النص: ' . mb_substr($message, 0, 150),
    'announcement',
    $announcementId,
    'info'
);

header("Location: index.php?success=1");
exit;