<?php
/*
=====================================================================
teacher/gradebook/store.php — رصد الدرجات (دفعة واحدة لعدة طلاب)
=====================================================================
التعديلات:
  1. تسجيل عملية الرصد في السجلات (Logger) — عملية حساسة يُنازَع عليها
  2. حماية صلاحيات: معلم فقط + التأكد أنه يدرّس هذا المقرر فعلاً
  3. التحقق من صحة الدرجات (لا سالبة، لا تتجاوز الدرجة العظمى)
  4. Transaction — إما تُرصد كل الدرجات أو لا شيء
  5. إشعارات بالعربية بدل الإنجليزية
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
    header("Location: " . BASE_URL . "/teacher/gradebook/index.php");
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

/* ==================== التحقق من المدخلات ==================== */
$courseId       = (int)($_POST['course_id'] ?? 0);
$assessmentType = trim((string)($_POST['assessment_type'] ?? ''));
$title          = trim((string)($_POST['title'] ?? ''));
$maxScore       = (float)($_POST['max_score'] ?? 0);
$scores         = $_POST['score'] ?? [];

if ($courseId <= 0 || $title === '') {
    die('المقرر وعنوان التقييم مطلوبان');
}

if ($maxScore <= 0) {
    die('الدرجة العظمى يجب أن تكون أكبر من صفر');
}

if (!is_array($scores) || !$scores) {
    die('لم تُدخل أي درجات');
}

/*
====================================================================
حماية: التأكد أن المعلم يدرّس هذا المقرر فعلاً
بدونها يستطيع أي معلم رصد درجات في مقرر لا يدرّسه (تلاعب بالمعرّف)
====================================================================
*/
$stmt = $db->prepare("
    SELECT COUNT(*) FROM course_assignments
    WHERE teacher_id = ? AND course_id = ?
");
$stmt->execute([$teacherId, $courseId]);

if ((int)$stmt->fetchColumn() === 0) {

    Logger::log(
        'gradebook',
        'store_denied',
        "محاولة رصد درجات في مقرر غير مسند للمعلم (course_id=$courseId)",
        'course',
        $courseId,
        'danger'
    );

    die('غير مصرح لك برصد درجات في هذا المقرر');
}

/* اسم المقرر — للسجل والإشعارات */
$stmt = $db->prepare("SELECT course_name FROM courses WHERE id = ?");
$stmt->execute([$courseId]);
$courseName = (string)$stmt->fetchColumn();

/*
====================================================================
الرصد داخل Transaction
فشل جزئي كان يترك بعض الطلاب مرصودين وبعضهم لا
====================================================================
*/
$saved   = 0;
$skipped = 0;
$names   = [];   // أسماء الطلاب المرصودة (لتفاصيل السجل)

try {

    $db->beginTransaction();

    foreach ($scores as $studentId => $score) {

        $studentId = (int)$studentId;
        $score     = trim((string)$score);

        /* خانة فارغة = تخطٍّ مقصود */
        if ($score === '' || $studentId <= 0) {
            continue;
        }

        $score = (float)$score;

        /* التحقق من نطاق الدرجة */
        if ($score < 0 || $score > $maxScore) {
            $skipped++;
            continue;
        }

        /* ==================== بيانات الطالب ==================== */
        $studentStmt = $db->prepare("
            SELECT s.user_id, u.full_name
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

        /* ==================== إدراج الدرجة ==================== */
        $stmt = $db->prepare("
            INSERT INTO gradebook
                (student_id, teacher_id, course_id,
                 assessment_type, title, score, max_score)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $studentId,
            $teacherId,
            $courseId,
            $assessmentType,
            $title,
            $score,
            $maxScore,
        ]);

        $saved++;

        /* أول 5 أسماء فقط — حتى لا يتضخم حقل التفاصيل */
        if (count($names) < 5) {
            $names[] = $student['full_name'] . ": $score";
        }

        /* ==================== إشعار الطالب ==================== */
        $notification->create(
            (int)$student['user_id'],
            'درجة جديدة',
            "تم رصد درجة جديدة في ($title) - مقرر $courseName: $score / $maxScore",
            'grade'
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

            $notification->create(
                (int)$parent['user_id'],
                'درجة جديدة للطالب',
                $student['full_name']
                . " حصل على درجة في ($title) - مقرر $courseName: $score / $maxScore",
                'grade'
            );
        }
    }

    $db->commit();

} catch (Throwable $ex) {

    if ($db->inTransaction()) {
        $db->rollBack();
    }

    Logger::log(
        'gradebook',
        'store_failed',
        "فشل رصد الدرجات - مقرر $courseName ($title)",
        'course',
        $courseId,
        'danger'
    );

    die('تعذر رصد الدرجات — لم يتم حفظ أي درجة');
}

/*
====================================================================
تسجيل العملية
الدرجات أكثر ما يُنازَع عليه لاحقاً — السجل هو دليلك الوحيد
====================================================================
*/
Logger::log(
    'gradebook',
    'store_grades',
    "رصد درجات: مقرر ($courseName) - التقييم ($title)"
    . ($assessmentType !== '' ? " - النوع ($assessmentType)" : '')
    . " - الدرجة العظمى ($maxScore)"
    . " | عدد الطلاب: $saved"
    . ($skipped > 0 ? " | تخطّي: $skipped" : '')
    . ($names ? ' | ' . implode('، ', $names) . (count($names) >= 5 ? '...' : '') : ''),
    'course',
    $courseId,
    'warning'
);

header("Location: report.php?course_id=" . $courseId);
exit;