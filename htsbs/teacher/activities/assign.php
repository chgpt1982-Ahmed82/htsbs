<?php
/*
=====================================================================
teacher/activities/assign.php — تعيين الأنشطة للصفوف
=====================================================================
🔴 خطأ خطير في النسخة السابقة:
   DELETE FROM activity_assignments WHERE activity_id = ?
   يُنفَّذ **بلا أي تحقق من ملكية النشاط**!
   أي معلم يستطيع حذف تعيينات أنشطة معلمين آخرين بتغيير activity_id

التعديلات:
  1. تسجيل التعيين (مع الصفوف المضافة والمحذوفة)
  2. التحقق من ملكية النشاط قبل DELETE
  3. التحقق أن الصفوف المختارة من صفوف المعلم
  4. Transaction — لا نحذف التعيينات القديمة ثم نفشل في الإدراج!
  5. تعبئة teacher_id في جدول التعيينات (كان يُترك NULL)
  6. عرض التعيينات الحالية عند اختيار النشاط
=====================================================================
*/

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../core/Auth.php';
require_once '../../core/Logger.php';

/* ==================== الصلاحية: معلم فقط ==================== */
if (!Auth::check() || (int)($_SESSION['role_id'] ?? 0) !== 2) {
    exit('Unauthorized');
}

if (!function_exists('e')) {
    function e($v): string {
        return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

$db = (new Database())->connect();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* ==================== سجل المعلم ==================== */
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

    $activityId = (int)($_POST['activity_id'] ?? 0);
    $classIds   = $_POST['class_ids'] ?? [];

    if ($activityId <= 0) {

        $error = 'يرجى اختيار النشاط';

    } else {

        /*
        ================================================================
        🔴 حماية جوهرية: النشاط يجب أن يكون من إنشاء هذا المعلم
        النسخة السابقة كانت تحذف التعيينات مباشرة بلا أي تحقق —
        أي معلم يمحو تعيينات أنشطة زملائه بتغيير activity_id!
        ================================================================
        */
        $stmt = $db->prepare("
            SELECT a.id, a.title, c.course_name
            FROM activities a
            INNER JOIN courses c ON a.course_id = c.id
            WHERE a.id = ? AND a.teacher_id = ?
        ");
        $stmt->execute([$activityId, $teacherId]);
        $activity = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$activity) {

            Logger::log(
                'activities',
                'assign_denied',
                "محاولة تعيين نشاط لا يملكه المعلم (activity_id=$activityId)",
                null, null, 'danger'
            );

            $error = 'غير مصرح لك بتعيين هذا النشاط';

        } else {

            /* الصفوف المسموحة لهذا المعلم */
            $stmt = $db->prepare("
                SELECT DISTINCT class_id
                FROM course_assignments
                WHERE teacher_id = ?
            ");
            $stmt->execute([$teacherId]);
            $allowedClasses = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

            /* تصفية الصفوف المرسلة — نتجاهل أي صف ليس للمعلم */
            $validClassIds = [];

            foreach ((array)$classIds as $cid) {
                $cid = (int)$cid;
                if ($cid > 0 && in_array($cid, $allowedClasses, true)) {
                    $validClassIds[] = $cid;
                }
            }

            /* التعيينات القديمة — للمقارنة في السجل */
            $stmt = $db->prepare("
                SELECT class_id FROM activity_assignments WHERE activity_id = ?
            ");
            $stmt->execute([$activityId]);
            $oldClassIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

            /*
            ================================================================
            Transaction: النسخة السابقة كانت تحذف أولاً ثم تُدرج.
            لو فشل الإدراج في المنتصف → تضيع كل التعيينات القديمة!
            ================================================================
            */
            try {

                $db->beginTransaction();

                $stmt = $db->prepare("DELETE FROM activity_assignments WHERE activity_id = ?");
                $stmt->execute([$activityId]);

                $insert = $db->prepare("
                    INSERT INTO activity_assignments (activity_id, class_id, teacher_id)
                    VALUES (?, ?, ?)
                ");

                foreach ($validClassIds as $cid) {
                    /* teacher_id كان يُترك NULL في النسخة السابقة */
                    $insert->execute([$activityId, $cid, $teacherId]);
                }

                $db->commit();

            } catch (Throwable $ex) {

                if ($db->inTransaction()) {
                    $db->rollBack();
                }

                Logger::log(
                    'activities',
                    'assign_failed',
                    "فشل تعيين النشاط ({$activity['title']})",
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
                'activities',
                'assign_activity',
                "تعيين نشاط ({$activity['title']}) - مقرر ({$activity['course_name']})"
                . ' | الصفوف: ' . ($classNames ? implode('، ', $classNames) : 'لا شيء')
                . (count($added) > 0 ? ' | مضاف: ' . count($added) : '')
                . (count($removed) > 0 ? ' | مُزال: ' . count($removed) : ''),
                'activity',
                $activityId,
                count($removed) > 0 ? 'warning' : 'info'
            );

            $success = true;
        }
    }
}

/* ==================== أنشطة المعلم ==================== */
$stmt = $db->prepare("
    SELECT a.id, a.title, c.course_name
    FROM activities a
    INNER JOIN courses c ON a.course_id = c.id
    WHERE a.teacher_id = ?
    ORDER BY c.course_name, a.title
");
$stmt->execute([$teacherId]);
$activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
التعيينات الحالية لكل نشاط — لعرضها فور اختيار النشاط
(النسخة السابقة كانت تعرض مربعات فارغة دائماً،
 فيظن المعلم أن النشاط غير معيَّن ويعيد التعيين من الصفر)
*/
$stmt = $db->prepare("
    SELECT aa.activity_id, aa.class_id
    FROM activity_assignments aa
    INNER JOIN activities a ON aa.activity_id = a.id
    WHERE a.teacher_id = ?
");
$stmt->execute([$teacherId]);

$currentMap = [];

foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $currentMap[(int)$r['activity_id']][] = (int)$r['class_id'];
}

include '../../app/views/layouts/header.php';
?>

<div class="container-fluid">
<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">

<div class="card shadow border-0">

    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">
            <i class="bi bi-diagram-3-fill"></i> تعيين الأنشطة للصفوف
        </h5>
    </div>

    <div class="card-body">

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle"></i> تم حفظ التعيين بنجاح
                <button class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle"></i> <?= e($error); ?>
                <button class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="alert alert-info small">
            <i class="bi bi-info-circle"></i>
            حفظ التعيين <strong>يستبدل</strong> التعيينات السابقة للنشاط.
            الصفوف غير المختارة سيُزال النشاط عنها.
        </div>

        <form method="POST">

            <!-- ==================== النشاط ==================== -->

            <div class="mb-3">

                <label class="form-label fw-bold">
                    اختر النشاط <span class="text-danger">*</span>
                </label>

                <select name="activity_id" id="activitySelect"
                        class="form-select" required
                        onchange="loadCurrent()">

                    <option value="">— اختر النشاط —</option>

                    <?php foreach ($activities as $activity): ?>
                        <option value="<?= (int)$activity['id']; ?>"
                                data-classes="<?= e(implode(',', $currentMap[(int)$activity['id']] ?? [])); ?>">
                            <?= e($activity['course_name']); ?>
                            — <?= e($activity['title']); ?>
                        </option>
                    <?php endforeach; ?>

                </select>

            </div>

            <!-- ==================== الصفوف ==================== -->

            <div class="mb-3">

                <label class="form-label fw-bold">اختر الصفوف</label>

                <?php if (!$classes): ?>
                    <div class="alert alert-warning small">
                        لا توجد صفوف مسندة إليك
                    </div>
                <?php endif; ?>

                <div class="row g-2">

                    <?php foreach ($classes as $class): ?>
                        <div class="col-md-4">
                            <div class="form-check border rounded p-2">
                                <input class="form-check-input class-cb"
                                       type="checkbox"
                                       name="class_ids[]"
                                       value="<?= (int)$class['id']; ?>"
                                       id="class<?= (int)$class['id']; ?>">
                                <label class="form-check-label"
                                       for="class<?= (int)$class['id']; ?>">
                                    <?= e($class['class_name']); ?>
                                </label>
                            </div>
                        </div>
                    <?php endforeach; ?>

                </div>

            </div>

            <hr class="my-4">

            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save-fill"></i> حفظ التعيين
            </button>

            <a href="index.php" class="btn btn-secondary">رجوع</a>

        </form>

    </div>

</div>

</div>
</div>
</div>

<script>
/* عرض التعيينات الحالية فور اختيار النشاط */
function loadCurrent() {

    const select = document.getElementById('activitySelect');
    const option = select.options[select.selectedIndex];

    const current = (option.dataset.classes || '')
        .split(',')
        .filter(v => v !== '');

    document.querySelectorAll('.class-cb').forEach(cb => {
        cb.checked = current.includes(cb.value);
    });
}

loadCurrent();
</script>

<?php include '../../app/views/layouts/footer.php'; ?>