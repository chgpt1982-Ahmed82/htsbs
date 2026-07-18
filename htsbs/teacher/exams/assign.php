<?php
/*
=====================================================================
teacher/exams/assign.php — تعيين اختبار موجود للصفوف
🔴 النسخة السابقة كانت مكسورة:
   header('Location') قبل كتلة الإشعارات، و $classId خارج الحلقة
   → الإشعارات تصل لصف واحد فقط (آخر قيمة)، وبعد إرسال الرأس
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

if (!function_exists('e')) {
    function e($v): string {
        return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

$db = (new Database())->connect();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* سجل المعلم */
$stmt = $db->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->execute([(int)$_SESSION['user_id']]);
$teacherId = (int)$stmt->fetchColumn();

if ($teacherId <= 0) {
    die('Teacher Not Found');
}

$examId = (int)($_GET['id'] ?? 0);

if ($examId <= 0) {
    die('Exam Not Found');
}

/* الاختبار — مع التأكد من الملكية (موجود أصلاً، جيد) */
$stmt = $db->prepare("
    SELECT exam_name FROM exams WHERE id = ? AND teacher_id = ?
");
$stmt->execute([$examId, $teacherId]);
$exam = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$exam) {
    die('Exam Not Found');
}

/* صفوف المعلم */
$stmt = $db->prepare("
    SELECT DISTINCT c.id, c.class_name
    FROM course_assignments ca
    INNER JOIN classes c ON ca.class_id = c.id
    WHERE ca.teacher_id = ?
    ORDER BY c.class_name
");
$stmt->execute([$teacherId]);
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* التعيينات الحالية */
$stmt = $db->prepare("SELECT class_id FROM exam_assignments WHERE exam_id = ?");
$stmt->execute([$examId]);
$currentAssignments = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

$success = false;

/* ==================== حفظ التعيين ==================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $classIds = $_POST['class_ids'] ?? [];

    /* تصفية — صفوف المعلم فقط */
    $allowedClasses = array_map('intval', array_column($classes, 'id'));

    $validClassIds = [];
    foreach ((array)$classIds as $cid) {
        $cid = (int)$cid;
        if ($cid > 0 && in_array($cid, $allowedClasses, true)) {
            $validClassIds[] = $cid;
        }
    }

    /* الصفوف المضافة حديثاً — لإشعارها فقط */
    $newlyAdded = array_diff($validClassIds, $currentAssignments);

    try {

        $db->beginTransaction();

        $stmt = $db->prepare("DELETE FROM exam_assignments WHERE exam_id = ?");
        $stmt->execute([$examId]);

        $insert = $db->prepare("
            INSERT INTO exam_assignments (exam_id, class_id) VALUES (?, ?)
        ");

        $notify = $db->prepare("
            INSERT INTO notifications (user_id, title, message, type)
            VALUES (?, ?, ?, ?)
        ");

        foreach ($validClassIds as $classId) {

            $insert->execute([$examId, $classId]);

            /* نُشعر الصفوف المضافة حديثاً فقط (داخل الحلقة الصحيحة!) */
            if (!in_array($classId, $newlyAdded, true)) {
                continue;
            }

            $studentsStmt = $db->prepare("SELECT user_id FROM students WHERE class_id = ?");
            $studentsStmt->execute([$classId]);

            foreach ($studentsStmt->fetchAll(PDO::FETCH_ASSOC) as $student) {
                $notify->execute([
                    (int)$student['user_id'],
                    'اختبار جديد',
                    'تم تعيين الاختبار "' . $exam['exam_name'] . '" لصفك الدراسي',
                    'exam',
                ]);
            }
        }

        $db->commit();

    } catch (Throwable $ex) {

        if ($db->inTransaction()) {
            $db->rollBack();
        }

        Logger::log('exams', 'assign_failed',
            "فشل تعيين الاختبار ({$exam['exam_name']})", null, null, 'danger');

        die('تعذر حفظ التعيين');
    }

    /* أسماء الصفوف — للسجل */
    $classNames = [];
    if ($validClassIds) {
        $in = implode(',', array_fill(0, count($validClassIds), '?'));
        $stmt = $db->prepare("SELECT class_name FROM classes WHERE id IN ($in)");
        $stmt->execute($validClassIds);
        $classNames = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /* ==================== التسجيل ==================== */
    Logger::log(
        'exams',
        'assign_exam',
        "تعيين اختبار ({$exam['exam_name']})"
        . ' | الصفوف: ' . ($classNames ? implode('، ', $classNames) : 'لا شيء')
        . (count($newlyAdded) > 0 ? ' | مضاف: ' . count($newlyAdded) : ''),
        'exam',
        $examId,
        'info'
    );

    $currentAssignments = $validClassIds;
    $success = true;
}

include '../../app/views/layouts/header.php';

?>


<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">
<div class="card shadow">

<div class="card-header bg-success text-white">

<h4 class="mb-0">

<i class="bi bi-diagram-3-fill"></i>

تعيين الاختبار للصفوف

</h4>

</div>

<div class="card-body">
<?php if ($success): ?>
<div class="alert alert-success">
تم حفظ التعيين بنجاح
</div>
<?php endif; ?>
<div class="alert alert-info">

<strong>

<?= e(
$exam['exam_name']
); ?>

</strong>

</div>

<form method="POST">

<div class="mb-3">

<label class="form-label">

اختر الصفوف

</label>

<?php foreach($classes as $class): ?>

<div class="form-check">

<input
class="form-check-input"
type="checkbox"
name="class_ids[]"
value="<?= $class['id']; ?>"

<?= in_array(
$class['id'],
$currentAssignments
)
? 'checked'
: ''; ?>

id="class<?= $class['id']; ?>">

<label
class="form-check-label"
for="class<?= $class['id']; ?>">

<?= e(
$class['class_name']
); ?>

</label>

</div>

<?php endforeach; ?>

</div>

<button
type="submit"
class="btn btn-success">

<i class="bi bi-save"></i>

حفظ التعيين

</button>

<a
href="index.php"
class="btn btn-secondary">

رجوع

</a>

</form>

</div>

</div>

</div>

</div>

</div>

<?php include '../../app/views/layouts/footer.php'; ?>