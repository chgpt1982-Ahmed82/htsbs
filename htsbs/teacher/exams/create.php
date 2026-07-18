<?php

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../core/Auth.php';

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

$stmt = $db->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->execute([(int)$_SESSION['user_id']]);
$teacherId = (int)$stmt->fetchColumn();

if ($teacherId <= 0) {
    die('Teacher Not Found');
}

/*
🔴 المقررات المسندة للمعلم فقط
النسخة السابقة: SELECT ... FROM courses (كل مقررات النظام!)
فيستطيع المعلم إنشاء اختبار في مقرر لا يدرّسه
*/
$stmt = $db->prepare("
    SELECT DISTINCT c.id, c.course_name
    FROM course_assignments ca
    INNER JOIN courses c ON ca.course_id = c.id
    WHERE ca.teacher_id = ?
    ORDER BY c.course_name
");
$stmt->execute([$teacherId]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* صفوف المعلم — لاختيارها عند الإنشاء */
$stmt = $db->prepare("
    SELECT DISTINCT c.id, c.class_name
    FROM course_assignments ca
    INNER JOIN classes c ON ca.class_id = c.id
    WHERE ca.teacher_id = ?
    ORDER BY c.class_name
");
$stmt->execute([$teacherId]);
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
⚠️ حذفنا كتلة INSERT من هنا:
create.php صفحة عرض — الحفظ يتم في store.php (فيه الحماية والتسجيل)
النسخة السابقة كانت تحفظ هنا AND في store.php (ازدواج!)
*/

include '../../app/views/layouts/header.php';
?>

<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">
<div class="card shadow">

<div class="card-header bg-primary text-white">

<h4 class="mb-0">

<i class="bi bi-pencil-square"></i>

إضافة اختبار

</h4>

</div>

<div class="card-body">

<form method="POST" action="store.php">

<div class="mb-3">

<label class="form-label">

المقرر الدراسي

</label>

<select
name="course_id"
class="form-select"
required>

<option value="">

اختر المقرر

</option>

<?php foreach($courses as $course): ?>

<option value="<?= $course['id']; ?>">

<?= e($course['course_name']); ?>

</option>

<?php endforeach; ?>

</select>

</div>

<div class="mb-3">

<label class="form-label">

اسم الاختبار

</label>

<input
type="text"
name="exam_name"
class="form-control"
required>

</div>

<div class="mb-3">

<label class="form-label">

نوع الاختبار

</label>

<select
name="exam_type"
class="form-select"
required>

<option value="Quiz">

اختبار قصير

</option>

<option value="Midterm">

اختبار نصفي

</option>

<option value="Final">

اختبار نهائي

</option>

<option value="Practical">

اختبار عملي

</option>

</select>

</div>

<div class="mb-3">

<label class="form-label">

تاريخ الاختبار

</label>

<input
type="date"
name="exam_date"
class="form-control"
required>

</div>

<div class="mb-3">

<label class="form-label">

الدرجة النهائية

</label>

<input
type="number"
name="max_marks"
class="form-control"
required>

</div>

<div class="mb-3">
    <label class="form-label">الصفوف المستهدفة <span class="text-danger">*</span></label>

    <?php if (!$classes): ?>
        <div class="alert alert-warning">لا توجد صفوف مسندة إليك</div>
    <?php endif; ?>

    <div class="row g-2">
        <?php foreach ($classes as $class): ?>
            <div class="col-md-4">
                <div class="form-check border rounded p-2">
                    <input class="form-check-input" type="checkbox"
                           name="class_ids[]" value="<?= (int)$class['id']; ?>"
                           id="class<?= (int)$class['id']; ?>">
                    <label class="form-check-label" for="class<?= (int)$class['id']; ?>">
                        <?= e($class['class_name']); ?>
                    </label>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>


<button
type="submit"
class="btn btn-primary">

<i class="bi bi-save"></i>

حفظ الاختبار

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