<?php
/*
=====================================================================
teacher/attendance/save.php — حفظ حضور صف كامل (UPSERT)
=====================================================================
التعديلات:
  1. 🔴 إصلاح خطأ قاتل: course_id مفقود من INSERT
     (العمود NOT NULL في الجدول — الحفظ يفشل أو يُدرج 0)
  2. تسجيل العملية — مع توثيق التعديلات (غائب ← حاضر)
  3. حماية صلاحيات: معلم فقط + الصف مسند إليه فعلاً
  4. التحقق من صحة الحالة (ENUM)
  5. Transaction — الكل أو لا شيء
  6. إشعارات بالعربية + إشعار عند تغيير حالة سابقة
=====================================================================
*/

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../core/Auth.php';
require_once '../../core/Logger.php';
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

/* ==================== سجل المعلم ==================== */
$stmt = $db->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->execute([(int)$_SESSION['user_id']]);
$teacher = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$teacher) {
    die('Teacher Not Found');
}

$teacherId = (int)$teacher['id'];

/* ==================== المدخلات ==================== */
$classId  = (int)($_POST['class_id'] ?? 0);
$courseId = (int)($_POST['course_id'] ?? 0);   /* ← كان مفقوداً تماماً */
$statuses = $_POST['status'] ?? [];
$notes    = $_POST['notes'] ?? [];

/* التاريخ: من النموذج أو اليوم */
$date = trim((string)($_POST['attendance_date'] ?? ''));

if ($date === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $date = date('Y-m-d');
}

if ($classId <= 0) {
    die('يرجى تحديد الصف');
}

if (!is_array($statuses) || !$statuses) {
    die('لم تُسجَّل أي حالة حضور');
}

/*
====================================================================
حماية: التأكد أن الصف مسند لهذا المعلم فعلاً
بدونها يستطيع أي معلم تسجيل حضور صفوف لا يدرّسها
====================================================================
*/
$stmt = $db->prepare("
    SELECT course_id FROM course_assignments
    WHERE teacher_id = ? AND class_id = ?
    " . ($courseId > 0 ? "AND course_id = ?" : "") . "
    LIMIT 1
");

$params = $courseId > 0
    ? [$teacherId, $classId, $courseId]
    : [$teacherId, $classId];

$stmt->execute($params);
$assignment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$assignment) {

    Logger::log(
        'attendance',
        'save_denied',
        "محاولة تسجيل حضور لصف غير مسند للمعلم (class_id=$classId)",
        'class',
        $classId,
        'danger'
    );

    die('غير مصرح لك بتسجيل حضور هذا الصف');
}

/*
🔴 إصلاح الخطأ القاتل:
جدول attendance فيه course_id NOT NULL بلا قيمة افتراضية،
لكن INSERT الأصلي لم يمرّره → فشل SQL أو إدراج course_id = 0
الآن نأخذه من الإسناد إن لم يُرسل من النموذج
*/
if ($courseId <= 0) {
    $courseId = (int)$assignment['course_id'];
}

/* اسم الصف — للسجل */
$stmt = $db->prepare("SELECT class_name FROM classes WHERE id = ?");
$stmt->execute([$classId]);
$className = (string)$stmt->fetchColumn();

/* الحالات المسموحة — مطابقة لـ ENUM في قاعدة البيانات */
$allowed = ['Present', 'Absent', 'Late', 'Excused'];

$statusLabels = [
    'Present' => 'حاضر',
    'Absent'  => 'غائب',
    'Late'    => 'متأخر',
    'Excused' => 'غياب بعذر',
];

/*
====================================================================
✅ الحالات القديمة — تُقرأ قبل الحفظ
هذا الملف يعمل UPSERT، فقد يكون تعديلاً لا تسجيلاً أول
(معلم يغيّر "غائب" إلى "حاضر" — حدث يستحق التوثيق!)
====================================================================
*/
$stmt = $db->prepare("
    SELECT student_id, status FROM attendance
    WHERE class_id = ? AND attendance_date = ?
");
$stmt->execute([$classId, $date]);

$oldStatuses = [];

foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $oldStatuses[(int)$r['student_id']] = (string)$r['status'];
}

/* ==================== الحفظ داخل Transaction ==================== */
$counts  = ['Present' => 0, 'Absent' => 0, 'Late' => 0, 'Excused' => 0];
$saved   = 0;
$updated = 0;
$changes = [];   /* تفاصيل التغييرات (لأول 5 طلاب) */

try {

    $db->beginTransaction();

    foreach ($statuses as $studentId => $status) {

        $studentId = (int)$studentId;
        $status    = trim((string)$status);

        if ($studentId <= 0 || $status === '') {
            continue;
        }

        /* التحقق من صحة الحالة */
        if (!in_array($status, $allowed, true)) {
            continue;
        }

        $note = trim((string)($notes[$studentId] ?? ''));

        $oldStatus = $oldStatuses[$studentId] ?? null;
        $hadOld    = ($oldStatus !== null);
        $changed   = !$hadOld || ($oldStatus !== $status);

        /* ==================== UPSERT ==================== */
        $check = $db->prepare("
            SELECT id FROM attendance
            WHERE student_id = ? AND attendance_date = ?
        ");
        $check->execute([$studentId, $date]);
        $existing = $check->fetch(PDO::FETCH_ASSOC);

        if ($existing) {

            $update = $db->prepare("
                UPDATE attendance
                SET status = ?, teacher_id = ?, class_id = ?, course_id = ?, notes = ?
                WHERE id = ?
            ");
            $update->execute([
                $status, $teacherId, $classId, $courseId,
                $note !== '' ? $note : null,
                (int)$existing['id'],
            ]);

            if ($changed) {
                $updated++;
            }

        } else {

            /* 🔴 course_id مضاف — كان مفقوداً */
            $insert = $db->prepare("
                INSERT INTO attendance
                    (student_id, teacher_id, class_id, course_id,
                     attendance_date, status, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $insert->execute([
                $studentId, $teacherId, $classId, $courseId,
                $date, $status,
                $note !== '' ? $note : null,
            ]);

            $saved++;
        }

        $counts[$status]++;

        /* بيانات الطالب — للسجل والإشعار */
        $studentStmt = $db->prepare("
            SELECT s.user_id, u.full_name
            FROM students s
            INNER JOIN users u ON s.user_id = u.id
            WHERE s.id = ?
        ");
        $studentStmt->execute([$studentId]);
        $student = $studentStmt->fetch(PDO::FETCH_ASSOC);

        if (!$student) {
            continue;
        }

        /* توثيق التغييرات (تعديل حالة سابقة) */
        if ($hadOld && $changed && count($changes) < 5) {
            $changes[] = $student['full_name'] . ': '
                       . ($statusLabels[$oldStatus] ?? $oldStatus)
                       . ' ← '
                       . ($statusLabels[$status] ?? $status);
        }

        /*
        ================================================================
        الإشعارات
        - الغياب والتأخر: يُشعَر بهما دائماً
        - تعديل حالة سابقة: يُشعَر به أيضاً (غائب ← حاضر مثلاً)
        ================================================================
        */
        $needNotify = in_array($status, ['Absent', 'Late'], true)
                   || ($hadOld && $changed);

        if (!$needNotify) {
            continue;
        }

        $statusAr = $statusLabels[$status] ?? $status;

        /* إشعار الطالب */
        $notification->create(
            (int)$student['user_id'],
            ($hadOld && $changed) ? 'تعديل حالة الحضور' : 'تنبيه حضور',
            ($hadOld && $changed)
                ? "تم تعديل حالتك ليوم $date من ("
                  . ($statusLabels[$oldStatus] ?? $oldStatus)
                  . ") إلى ($statusAr)"
                : "تم تسجيلك ($statusAr) بتاريخ $date",
            'attendance'
        );

        /* إشعار أولياء الأمور */
        $parentStmt = $db->prepare("
            SELECT p.user_id
            FROM parent_student ps
            INNER JOIN parents p ON ps.parent_id = p.id
            WHERE ps.student_id = ?
        ");
        $parentStmt->execute([$studentId]);

        foreach ($parentStmt->fetchAll(PDO::FETCH_ASSOC) as $parent) {

            $notification->create(
                (int)$parent['user_id'],
                ($hadOld && $changed) ? 'تعديل حضور الطالب' : 'تنبيه حضور الطالب',
                $student['full_name'] . ' — '
                . (($hadOld && $changed)
                    ? "تعديل حالة يوم $date إلى ($statusAr)"
                    : "تم تسجيله ($statusAr) بتاريخ $date"),
                'attendance'
            );
        }
    }

    $db->commit();

} catch (Throwable $ex) {

    if ($db->inTransaction()) {
        $db->rollBack();
    }

    Logger::log(
        'attendance',
        'save_failed',
        "فشل حفظ حضور صف ($className) بتاريخ $date",
        'class',
        $classId,
        'danger'
    );

    die('تعذر حفظ الحضور — لم يُحفظ أي سجل');
}

/*
====================================================================
تسجيل العملية
نميّز بين التسجيل الأول والتعديل — التعديل أخطر
====================================================================
*/
Logger::log(
    'attendance',
    $updated > 0 ? 'update_attendance' : 'save_attendance',
    "حضور صف ($className) بتاريخ $date | "
    . "حاضر: {$counts['Present']} | "
    . "غائب: {$counts['Absent']} | "
    . "متأخر: {$counts['Late']} | "
    . "بعذر: {$counts['Excused']}"
    . ($updated > 0 ? " | معدّلة: $updated" : '')
    . ($changes ? ' | ' . implode('، ', $changes) . (count($changes) >= 5 ? '...' : '') : ''),
    'class',
    $classId,
    $updated > 0 ? 'warning' : 'info'
);

header("Location: mark.php?class_id=" . $classId . "&success=1");
exit;