<?php
/*
=====================================================================
teacher/lessons/assign.php — تعيين الدروس للصفوف
=====================================================================
التعديلات في منطق المعالجة فقط (HTML بالأسفل دون تغيير):
  1. تسجيل التعيين
  2. 🔴 التحقق من ملكية الدرس قبل DELETE
     (كان أي معلم يمحو تعيينات دروس زملائه بتغيير lesson_id)
  3. تصفية الصفوف — فقط صفوف المعلم
  4. Transaction (كان يحذف ثم يُدرج بلا حماية)
  5. عرض التعيينات الحالية فور اختيار الدرس
=====================================================================
*/

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../core/Auth.php';
require_once '../../core/Logger.php';

/* ==================== الصلاحية: معلم فقط ==================== */
if (!Auth::check() || (int)($_SESSION['role_id'] ?? 0) !== 2) {
    header("Location: ../../login.php");
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

$success = false;
$error   = null;

/* ==================== حفظ التعيين ==================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $lessonId = (int)($_POST['lesson_id'] ?? 0);
    $classIds = $_POST['class_ids'] ?? [];

    if ($lessonId <= 0) {

        $error = 'يرجى اختيار الدرس';

    } else {

        /*
        ================================================================
        🔴 حماية: الدرس يجب أن يكون من إنشاء هذا المعلم
        النسخة السابقة كانت تحذف التعيينات مباشرة بلا أي تحقق
        ================================================================
        */
        $stmt = $db->prepare("
            SELECT l.id, l.lesson_title, c.course_name
            FROM lessons l
            INNER JOIN courses c ON l.course_id = c.id
            WHERE l.id = ? AND l.teacher_id = ?
        ");
        $stmt->execute([$lessonId, $teacherId]);
        $lesson = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$lesson) {

            Logger::log(
                'lessons',
                'assign_denied',
                "محاولة تعيين درس لا يملكه المعلم (lesson_id=$lessonId)",
                null, null, 'danger'
            );

            $error = 'غير مصرح لك بتعيين هذا الدرس';

        } else {

            /* الصفوف المسموحة للمعلم */
            $stmt = $db->prepare("
                SELECT DISTINCT class_id FROM course_assignments WHERE teacher_id = ?
            ");
            $stmt->execute([$teacherId]);
            $allowedClasses = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

            /* تصفية الصفوف المرسلة */
            $validClassIds = [];
            foreach ((array)$classIds as $cid) {
                $cid = (int)$cid;
                if ($cid > 0 && in_array($cid, $allowedClasses, true)) {
                    $validClassIds[] = $cid;
                }
            }

            /* التعيينات القديمة — للمقارنة في السجل */
            $stmt = $db->prepare("
                SELECT class_id FROM lesson_assignments WHERE lesson_id = ?
            ");
            $stmt->execute([$lessonId]);
            $oldClassIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

            /*
            ============================================================
            Transaction: كان يحذف أولاً ثم يُدرج
            لو فشل الإدراج → تضيع كل التعيينات القديمة
            ============================================================
            */
            try {

                $db->beginTransaction();

                $stmt = $db->prepare("DELETE FROM lesson_assignments WHERE lesson_id = ?");
                $stmt->execute([$lessonId]);

                $insert = $db->prepare("
                    INSERT INTO lesson_assignments (lesson_id, class_id) VALUES (?, ?)
                ");

                foreach ($validClassIds as $cid) {
                    $insert->execute([$lessonId, $cid]);
                }

                $db->commit();

            } catch (Throwable $ex) {

                if ($db->inTransaction()) {
                    $db->rollBack();
                }

                Logger::log(
                    'lessons',
                    'assign_failed',
                    "فشل تعيين الدرس ({$lesson['lesson_title']})",
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

            $added   = array_diff($validClassIds, $oldClassIds);
            $removed = array_diff($oldClassIds, $validClassIds);

            /* ==================== التسجيل ==================== */
            Logger::log(
                'lessons',
                'assign_lesson',
                "تعيين درس ({$lesson['lesson_title']}) - مقرر ({$lesson['course_name']})"
                . ' | الصفوف: ' . ($classNames ? implode('، ', $classNames) : 'لا شيء')
                . (count($added) > 0 ? ' | مضاف: ' . count($added) : '')
                . (count($removed) > 0 ? ' | مُزال: ' . count($removed) : ''),
                'lesson',
                $lessonId,
                count($removed) > 0 ? 'warning' : 'info'
            );

            $success = true;
        }
    }
}

/* ==================== دروس المعلم ==================== */
$stmt = $db->prepare("
    SELECT l.id, l.lesson_title, c.course_name
    FROM lessons l
    INNER JOIN courses c ON l.course_id = c.id
    WHERE l.teacher_id = ?
    ORDER BY c.course_name, l.lesson_title
");
$stmt->execute([$teacherId]);
$lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ==================== صفوف المعلم ==================== */
$stmt = $db->prepare("
    SELECT DISTINCT cl.id, cl.class_name
    FROM classes cl
    INNER JOIN course_assignments ca ON cl.id = ca.class_id
    WHERE ca.teacher_id = ?
    ORDER BY cl.class_name
");
$stmt->execute([$teacherId]);
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
التعيينات الحالية لكل درس — لعرضها فور اختيار الدرس
(كانت المربعات فارغة دائماً، فيظن المعلم أن الدرس غير معيَّن)
*/
$stmt = $db->prepare("
    SELECT la.lesson_id, la.class_id
    FROM lesson_assignments la
    INNER JOIN lessons l ON la.lesson_id = l.id
    WHERE l.teacher_id = ?
");
$stmt->execute([$teacherId]);

$currentMap = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $currentMap[(int)$r['lesson_id']][] = (int)$r['class_id'];
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
تعيين الدروس للصفوف
</h4>

</div>

<div class="card-body">

<?php if(!empty($success)): ?>

<div class="alert alert-success">
تم حفظ التعيين بنجاح
</div>

<?php endif; ?>

<form method="POST">

<div class="mb-3">

<select name="lesson_id" class="form-select" required id="lessonSelect" onchange="loadCurrent()">

<option value="">اختر الدرس</option>

<?php foreach($lessons as $lesson): ?>
<option value="<?= (int)$lesson['id']; ?>"
        data-classes="<?= e(implode(',', $currentMap[(int)$lesson['id']] ?? [])); ?>">
    <?= e($lesson['lesson_title']); ?> - <?= e($lesson['course_name']); ?>
</option>
<?php endforeach; ?>

</select>

</div>

<div class="mb-3">

    <label class="form-label">
        اختر الصفوف
    </label>

<?php foreach($classes as $class): ?>

<div class="form-check">



<label
class="form-check-label"
for="class<?= $class['id']; ?>">

<?= e($class['class_name']); ?>

</label>
<input class="form-check-input class-cb"
       type="checkbox"
       name="class_ids[]"
       value="<?= (int)$class['id']; ?>"
       id="class<?= (int)$class['id']; ?>">
</div>

<?php endforeach; ?>

</div>

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
<script>
function loadCurrent() {
    const sel = document.getElementById('lessonSelect');
    const opt = sel.options[sel.selectedIndex];
    const current = (opt.dataset.classes || '').split(',').filter(v => v !== '');
    document.querySelectorAll('.class-cb').forEach(cb => {
        cb.checked = current.includes(cb.value);
    });
}
loadCurrent();
</script>
<?php include '../../app/views/layouts/footer.php'; ?>
