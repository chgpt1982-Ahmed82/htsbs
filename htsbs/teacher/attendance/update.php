<?php
/*
=====================================================================
teacher/attendance/update.php — تعديل سجل حضور واحد
=====================================================================
⚠️ النسخة السابقة كانت مفتوحة تماماً:
   لا حماية صلاحيات، لا تحقق من الملكية، لا تحقق من صحة الحالة.
   أي مستخدم يرسل POST بـ attendance_id يعدّل حضور أي طالب في المدرسة!
=====================================================================
*/

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../core/Auth.php';
require_once '../../core/Logger.php';
require_once '../../app/models/Attendance.php';
require_once '../../app/models/Notification.php';

/* ==================== الصلاحية: معلم فقط ==================== */
if (!Auth::check() || (int)($_SESSION['role_id'] ?? 0) !== 2) {
    die('Access Denied');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/teacher/attendance/index.php");
    exit;
}

$db = (new Database())->connect();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$notification = new Notification();

/* ==================== المدخلات ==================== */
$attendanceId = (int)($_POST['attendance_id'] ?? 0);
$status       = trim((string)($_POST['status'] ?? ''));
$note         = trim((string)($_POST['notes'] ?? ''));

if ($attendanceId <= 0) {
    die('Attendance ID Not Found');
}

$allowed = ['Present', 'Absent', 'Late', 'Excused'];

if (!in_array($status, $allowed, true)) {
    die('حالة الحضور غير صالحة');
}

$statusLabels = [
    'Present' => 'حاضر',
    'Absent'  => 'غائب',
    'Late'    => 'متأخر',
    'Excused' => 'غياب بعذر',
];

/* ==================== سجل المعلم ==================== */
$stmt = $db->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->execute([(int)$_SESSION['user_id']]);
$teacherId = (int)$stmt->fetchColumn();

if ($teacherId <= 0) {
    die('Teacher Not Found');
}

/*
====================================================================
✅ نقرأ السجل القديم قبل التعديل
مع التأكد أن الصف مسند لهذا المعلم (حماية من التلاعب بالمعرّف)
====================================================================
*/
$stmt = $db->prepare("
    SELECT a.*,
           u.full_name,
           s.user_id AS student_user_id,
           cl.class_name
    FROM attendance a
    INNER JOIN students s ON a.student_id = s.id
    INNER JOIN users u    ON s.user_id = u.id
    INNER JOIN classes cl ON a.class_id = cl.id
    WHERE a.id = ?
      AND EXISTS (
          SELECT 1 FROM course_assignments ca
          WHERE ca.teacher_id = ?
            AND ca.class_id = a.class_id
      )
");
$stmt->execute([$attendanceId, $teacherId]);
$old = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$old) {

    Logger::log(
        'attendance',
        'update_denied',
        "محاولة تعديل سجل حضور لصف غير مسند للمعلم (attendance_id=$attendanceId)",
        null,
        null,
        'danger'
    );

    die('السجل غير موجود أو لا تملك صلاحية تعديله');
}

$oldStatus = (string)$old['status'];
$changed   = ($oldStatus !== $status);

/* ==================== التحديث ==================== */
try {

    $model = new Attendance();

    $model->update($attendanceId, [
        'status' => $status,
        'notes'  => $note !== '' ? $note : null,
    ]);

} catch (Throwable $ex) {

    Logger::log(
        'attendance',
        'update_failed',
        "فشل تعديل سجل حضور (attendance_id=$attendanceId)",
        null, null, 'danger'
    );

    die('تعذر حفظ التعديل');
}

/*
====================================================================
التسجيل: توثيق الحالة القديمة والجديدة
تغيير "غائب" إلى "حاضر" حدث حساس — قد يمحو غياباً موثّقاً
====================================================================
*/
Logger::log(
    'attendance',
    'update_attendance',
    "تعديل حضور {$old['full_name']} — صف ({$old['class_name']}) "
    . "بتاريخ {$old['attendance_date']}: "
    . ($changed
        ? ($statusLabels[$oldStatus] ?? $oldStatus)
          . ' ← '
          . ($statusLabels[$status] ?? $status)
        : 'بلا تغيير في الحالة (' . ($statusLabels[$status] ?? $status) . ')')
    . ($note !== '' ? " | ملاحظة: " . mb_substr($note, 0, 100) : ''),
    'student',
    (int)$old['student_id'],
    'warning'
);

/* ==================== إشعار الطالب وولي الأمر ==================== */
if ($changed) {

    $statusAr    = $statusLabels[$status] ?? $status;
    $oldStatusAr = $statusLabels[$oldStatus] ?? $oldStatus;

    $notification->create(
        (int)$old['student_user_id'],
        'تعديل حالة الحضور',
        "تم تعديل حالتك بتاريخ {$old['attendance_date']} "
        . "من ($oldStatusAr) إلى ($statusAr)"
        . ($note !== '' ? " | ملاحظة: $note" : ''),
        'attendance'
    );

    $parentStmt = $db->prepare("
        SELECT p.user_id
        FROM parent_student ps
        INNER JOIN parents p ON ps.parent_id = p.id
        WHERE ps.student_id = ?
    ");
    $parentStmt->execute([(int)$old['student_id']]);

    foreach ($parentStmt->fetchAll(PDO::FETCH_ASSOC) as $parent) {

        $notification->create(
            (int)$parent['user_id'],
            'تعديل حضور الطالب',
            "{$old['full_name']} — تعديل حالة يوم {$old['attendance_date']}: "
            . "من ($oldStatusAr) إلى ($statusAr)",
            'attendance'
        );
    }
}

header("Location: index.php?updated=1");
exit;