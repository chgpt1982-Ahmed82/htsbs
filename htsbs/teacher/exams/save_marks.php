<?php
/*
=====================================================================
teacher/exams/save_marks.php — حفظ درجات الاختبار
=====================================================================
التعديلات:
  1. تسجيل العملية في السجلات — مع توثيق الدرجة القديمة ← الجديدة
     (saveResult تعمل UPSERT: تُنشئ أو تُعدّل، فالتعديل يمر من هنا)
  2. حماية صلاحيات: معلم فقط + الاختبار من إنشائه هو (exams.teacher_id)
  3. التحقق من صحة الدرجات (لا سالبة، لا تتجاوز max_marks)
  4. Transaction — الكل أو لا شيء
  5. إشعارات بالعربية بدل الإنجليزية
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
    header("Location: " . BASE_URL . "/teacher/exams/index.php");
    exit;
}

$db = (new Database())->connect();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$examModel         = new Exam();
$notificationModel = new Notification();

$examId = (int)($_POST['exam_id'] ?? 0);
$marks  = $_POST['marks'] ?? [];

if ($examId <= 0) {
    die('Exam ID Not Found');
}

if (!is_array($marks) || !$marks) {
    die('لم تُدخل أي درجات');
}

/* ==================== بيانات الاختبار ==================== */
$exam = $examModel->find($examId);

if (!$exam) {
    die('Exam Not Found');
}

/* سجل المعلم */
$stmt = $db->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->execute([(int)$_SESSION['user_id']]);
$teacherId = (int)$stmt->fetchColumn();

if ($teacherId <= 0) {
    die('Teacher Not Found');
}

/*
====================================================================
حماية: الاختبار يجب أن يكون من إنشاء هذا المعلم
بدونها يستطيع أي معلم رصد درجات في اختبار معلم آخر
(بتعديل exam_id في المتصفح)
====================================================================
*/
if ((int)$exam['teacher_id'] !== $teacherId) {

    Logger::log(
        'exams',
        'save_marks_denied',
        "محاولة رصد درجات في اختبار لا يملكه المعلم (exam_id=$examId)",
        'exam',
        $examId,
        'danger'
    );

    die('غير مصرح لك برصد درجات هذا الاختبار');
}

$examName = (string)$exam['exam_name'];
$maxMarks = (float)$exam['max_marks'];

if ($maxMarks <= 0) {
    die('الدرجة العظمى للاختبار غير صالحة');
}

/*
====================================================================
✅ نقرأ الدرجات القديمة قبل الحفظ
saveResult تعمل UPSERT — فقد تكون هذه عملية تعديل لا رصد أول
وبلا القيمة القديمة يصبح السجل عديم القيمة
====================================================================
*/
$stmt = $db->prepare("SELECT student_id, marks_obtained FROM exam_results WHERE exam_id = ?");
$stmt->execute([$examId]);

$oldMarks = [];

foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $oldMarks[(int)$r['student_id']] = (float)$r['marks_obtained'];
}

/*
====================================================================
الحفظ داخل Transaction
====================================================================
*/
$saved   = 0;   // درجات جديدة
$updated = 0;   // درجات معدّلة
$skipped = 0;
$changes = [];  // تفاصيل التغييرات (لأول 5 طلاب)

try {

    $db->beginTransaction();

    foreach ($marks as $studentId => $mark) {

        $studentId = (int)$studentId;
        $mark      = trim((string)$mark);

        /* خانة فارغة = تخطٍّ مقصود */
        if ($mark === '' || $studentId <= 0) {
            continue;
        }

        $mark = (float)$mark;

        /* التحقق من نطاق الدرجة */
        if ($mark < 0 || $mark > $maxMarks) {
            $skipped++;
            continue;
        }

        $remarks = trim((string)($_POST['remarks'][$studentId] ?? ''));

        /* ==================== بيانات الطالب ==================== */
        $studentStmt = $db->prepare("
            SELECT s.id, s.user_id, u.full_name
            FROM students s
            INNER JOIN users u ON s.user_id = u.id
            WHERE s.id = ?
        ");
        $studentStmt->execute([$studentId]);
        $student = $studentStmt->fetch(PDO::FETCH_ASSOC);

        if (!$student) {
            $skipped++;
            continue;
        }

        /* هل كانت له درجة سابقة؟ */
        $hadOld  = array_key_exists($studentId, $oldMarks);
        $oldMark = $hadOld ? $oldMarks[$studentId] : null;

        /* ==================== الحفظ (UPSERT) ==================== */
        $examModel->saveResult($examId, $studentId, $mark, $remarks);

        /* هل تغيّرت الدرجة فعلاً؟ */
        $changed = !$hadOld || (abs((float)$oldMark - $mark) > 0.001);

        if ($hadOld) {
            if ($changed) {
                $updated++;
                if (count($changes) < 5) {
                    $changes[] = "{$student['full_name']}: $oldMark ← $mark";
                }
            }
        } else {
            $saved++;
            if (count($changes) < 5) {
                $changes[] = "{$student['full_name']}: $mark";
            }
        }

        /* لا نُشعر الطالب إن لم تتغير درجته */
        if (!$changed) {
            continue;
        }

        /* ==================== إشعار الطالب ==================== */
        $notificationModel->create(
            (int)$student['user_id'],
            $hadOld ? 'تعديل نتيجة اختبار' : 'نتيجة اختبار',
            $hadOld
                ? "تم تعديل نتيجتك في ($examName): من $oldMark إلى $mark / $maxMarks"
                : "نتيجتك في ($examName): $mark / $maxMarks"
                  . ($remarks !== '' ? " | ملاحظات المعلم: $remarks" : ''),
            'exam'
        );

        /* ==================== إشعار أولياء الأمور ==================== */
        $parentStmt = $db->prepare("
            SELECT p.user_id
            FROM parent_student ps
            INNER JOIN parents p ON ps.parent_id = p.id
            WHERE ps.student_id = ?
        ");
        $parentStmt->execute([$studentId]);

        foreach ($parentStmt->fetchAll(PDO::FETCH_ASSOC) as $parent) {

            $notificationModel->create(
                (int)$parent['user_id'],
                $hadOld ? 'تعديل نتيجة الطالب' : 'نتيجة اختبار الطالب',
                "{$student['full_name']} - ($examName): "
                . ($hadOld ? "من $oldMark إلى $mark" : "$mark")
                . " / $maxMarks",
                'exam'
            );
        }
    }

    $db->commit();

} catch (Throwable $ex) {

    if ($db->inTransaction()) {
        $db->rollBack();
    }

    Logger::log(
        'exams',
        'save_marks_failed',
        "فشل رصد درجات الاختبار ($examName)",
        'exam',
        $examId,
        'danger'
    );

    die('تعذر حفظ الدرجات — لم يتم حفظ أي درجة');
}

/*
====================================================================
تسجيل العملية
نميّز بين الرصد الأول والتعديل — التعديل أخطر ويستحق التوثيق
====================================================================
*/
Logger::log(
    'exams',
    $updated > 0 ? 'update_marks' : 'save_marks',
    "رصد درجات اختبار ($examName) - الدرجة العظمى ($maxMarks)"
    . " | جديدة: $saved"
    . ($updated > 0 ? " | معدّلة: $updated" : '')
    . ($skipped > 0 ? " | تخطّي: $skipped" : '')
    . ($changes ? ' | ' . implode('، ', $changes) . (count($changes) >= 5 ? '...' : '') : ''),
    'exam',
    $examId,
    'warning'
);

header("Location: report.php?id=" . $examId . "&success=1");
exit;