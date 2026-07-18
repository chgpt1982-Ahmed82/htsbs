<?php
/*
=====================================================================
teacher/assignments/assign.php — تعيين الواجب للصفوف
التعديلات في منطق المعالجة فقط (HTML بالأسفل دون تغيير):
  1. تسجيل التعيين
  2. Transaction (كان يحذف ثم يُدرج بلا حماية)
  3. تصفية الصفوف — فقط صفوف المعلم
  4. تعبئة teacher_id في جدول التعيينات
  5. عدم تكرار الإشعار إن كان الصف معيَّناً مسبقاً
=====================================================================
*/

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../core/Auth.php';
require_once '../../core/Logger.php';

/* ==================== الصلاحية: معلم فقط ==================== */
if (!Auth::check() || (int)($_SESSION['role_id'] ?? 0) !== 2) {
    header('Location: ../../login.php');
    exit;
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

/* بيانات الواجب — مع التأكد من الملكية (موجود أصلاً، جيد) */
$assignmentId = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("
    SELECT * FROM assignments WHERE id = ? AND teacher_id = ?
");
$stmt->execute([$assignmentId, $teacherId]);
$assignment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$assignment) {
    die('Assignment Not Found');
}

/* صفوف المعلم */
$stmt = $db->prepare("
    SELECT DISTINCT cl.id, cl.class_name
    FROM classes cl
    INNER JOIN course_assignments ca ON cl.id = ca.class_id
    WHERE ca.teacher_id = ?
    ORDER BY cl.class_name
");
$stmt->execute([$teacherId]);
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* التعيينات الحالية */
$stmt = $db->prepare("
    SELECT class_id FROM assignment_assignments WHERE assignment_id = ?
");
$stmt->execute([$assignmentId]);
$currentAssignments = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

$success = false;

/* ==================== حفظ التعيين ==================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $classIds = $_POST['class_ids'] ?? [];

    /* الصفوف المسموحة للمعلم */
    $allowedClasses = array_map('intval', array_column($classes, 'id'));

    /* تصفية — نتجاهل أي صف ليس للمعلم */
    $validClassIds = [];

    foreach ((array)$classIds as $cid) {
        $cid = (int)$cid;
        if ($cid > 0 && in_array($cid, $allowedClasses, true)) {
            $validClassIds[] = $cid;
        }
    }

    /* الصفوف المضافة حديثاً — لإشعارها فقط (لا نكرر إشعار القديمة) */
    $newlyAdded = array_diff($validClassIds, $currentAssignments);

    try {

        $db->beginTransaction();

        /* حذف التعيينات القديمة */
        $stmt = $db->prepare("DELETE FROM assignment_assignments WHERE assignment_id = ?");
        $stmt->execute([$assignmentId]);

        /* إدراج الجديدة — مع teacher_id */
        $insert = $db->prepare("
            INSERT INTO assignment_assignments (assignment_id, class_id, teacher_id)
            VALUES (?, ?, ?)
        ");

        $notify = $db->prepare("
            INSERT INTO notifications (user_id, title, message, type)
            VALUES (?, ?, ?, ?)
        ");

        foreach ($validClassIds as $classId) {

            $insert->execute([$assignmentId, $classId, $teacherId]);

            /* نُشعر طلاب الصفوف المضافة حديثاً فقط */
            if (!in_array($classId, $newlyAdded, true)) {
                continue;
            }

            $studentsStmt = $db->prepare("SELECT user_id FROM students WHERE class_id = ?");
            $studentsStmt->execute([$classId]);

            foreach ($studentsStmt->fetchAll(PDO::FETCH_ASSOC) as $student) {
                $notify->execute([
                    (int)$student['user_id'],
                    'واجب جديد',
                    'تم تعيين الواجب: ' . $assignment['title'],
                    'assignment',
                ]);
            }
        }

        $db->commit();

    } catch (Throwable $ex) {

        if ($db->inTransaction()) {
            $db->rollBack();
        }

        Logger::log(
            'assignments',
            'assign_failed',
            "فشل تعيين الواجب ({$assignment['title']})",
            null, null, 'danger'
        );

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
        'assignments',
        'assign_assignment',
        "تعيين واجب ({$assignment['title']})"
        . ' | الصفوف: ' . ($classNames ? implode('، ', $classNames) : 'لا شيء')
        . (count($newlyAdded) > 0 ? ' | مضاف: ' . count($newlyAdded) : ''),
        'assignment',
        $assignmentId,
        'info'
    );

    /* تحديث القائمة للعرض */
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

<div class="card-header bg-primary text-white">

    <h4 class="mb-0">

        <i class="bi bi-diagram-3-fill"></i>

        تعيين الواجب للصفوف

    </h4>

</div>

<div class="card-body">

<?php if (!empty($success)): ?>

<div class="alert alert-success">

    تم حفظ التعيين بنجاح

</div>

<?php endif; ?>

<div class="alert alert-info mb-4">

    <strong>

        <?= htmlspecialchars($assignment['title']); ?>

    </strong>

    <span class="ms-2 text-muted small">

        تاريخ التسليم:

        <?= date('d/m/Y', strtotime($assignment['due_date'])); ?>

    </span>

</div>

<form method="POST">

<div class="mb-3">

    <label class="form-label fw-semibold">

        اختر الصفوف

    </label>

    <?php foreach ($classes as $class): ?>

    <div class="form-check mb-2">

        <input
        class="form-check-input"
        type="checkbox"
        name="class_ids[]"
        value="<?= $class['id']; ?>"
        <?= in_array($class['id'], $currentAssignments) ? 'checked' : ''; ?>
        id="class<?= $class['id']; ?>">

        <label
        class="form-check-label"
        for="class<?= $class['id']; ?>">

            <?= htmlspecialchars($class['class_name']); ?>

        </label>

    </div>

    <?php endforeach; ?>

</div>

<hr>

<button
type="submit"
class="btn btn-primary">

    <i class="bi bi-save-fill"></i>

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
