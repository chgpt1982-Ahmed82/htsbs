<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../app/models/DeepLessonPlanner.php';
require_once '../../app/models/Notification.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/login.php");
    exit;
}

if ($_SESSION['role_id'] != 2) {
    die("Access Denied");
}

$db = (new Database())->connect();
$notificationModel = new Notification();
$count = $notificationModel->unreadCount($_SESSION['user_id']);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) die("رقم التخطيط غير صحيح.");

/*
==================================================
جلب التخطيط
==================================================
*/

$stmt = $db->prepare("
SELECT * FROM deep_lesson_plans
WHERE id = ? AND teacher_id = ?
LIMIT 1
");

$stmt->execute([$id, $_SESSION['user_id']]);
$lesson = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$lesson) die("التخطيط غير موجود.");

// Decode JSON plan
$planData = [];
if (!empty($lesson['lesson_plan_json'])) {
    $planData = json_decode($lesson['lesson_plan_json'], true) ?: [];
}

$resources  = json_decode($lesson['resources']  ?? '[]', true) ?: [];
$facilities = json_decode($lesson['facilities'] ?? '[]', true) ?: [];

/*
==================================================
المواد والصفوف
==================================================
*/

$stmt = $db->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$teacher = $stmt->fetch(PDO::FETCH_ASSOC);
$teacherId = $teacher['id'] ?? 0;

$stmt = $db->prepare("SELECT c.id, c.course_name FROM course_assignments ca INNER JOIN courses c ON c.id = ca.course_id WHERE ca.teacher_id = ? GROUP BY c.id ORDER BY c.course_name");
$stmt->execute([$teacherId]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->prepare("SELECT cl.id, cl.class_name FROM course_assignments ca INNER JOIN classes cl ON cl.id = ca.class_id WHERE ca.teacher_id = ? GROUP BY cl.id ORDER BY cl.class_name");
$stmt->execute([$teacherId]);
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../app/views/layouts/header.php';

// Helpers
$g1procs = $planData['goal_1_procedures'] ?? [];
$g2procs = $planData['goal_2_procedures'] ?? [];
$g1eval  = $planData['goal_1_evaluation'] ?? [];
$g2eval  = $planData['goal_2_evaluation'] ?? [];
$g2diff  = $planData['goal_2_differentiation'] ?? [];
$sc      = $planData['student_categories'] ?? [];
$skills21 = $planData['21st_century_skills'] ?? [];
$engag   = $planData['engagement_indicators'] ?? [];

?>

<div class="container-fluid">
<div class="row">
<?php include '../../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">

<div class="card shadow border-0 mb-4">
<div class="card-header bg-warning text-dark">
    <h4 class="mb-0"><i class="bi bi-pencil-square"></i> تعديل التخطيط العميق</h4>
</div>
<div class="card-body">

<form method="POST" action="update.php">
<input type="hidden" name="id" value="<?= $lesson['id']; ?>">

<!-- ========== المعلومات الأساسية ========== -->
<div class="alert alert-primary border-0 mb-3"><h6 class="mb-0"><i class="bi bi-info-circle-fill"></i> المعلومات الأساسية</h6></div>
<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label fw-bold">المادة</label>
        <select name="subject_id" class="form-select" required>
            <option value="">اختر المادة</option>
            <?php foreach ($courses as $c): ?>
            <option value="<?= $c['id']; ?>" <?= ($c['id'] == $lesson['subject_id']) ? 'selected' : ''; ?>><?= htmlspecialchars($c['course_name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label fw-bold">الصف</label>
        <select name="class_id" class="form-select" required>
            <option value="">اختر الصف</option>
            <?php foreach ($classes as $cl): ?>
            <option value="<?= $cl['id']; ?>" <?= ($cl['id'] == $lesson['class_id']) ? 'selected' : ''; ?>><?= htmlspecialchars($cl['class_name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label fw-bold">الوحدة</label>
        <input type="text" name="unit_name" class="form-control" value="<?= htmlspecialchars($lesson['unit_name']); ?>" required>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label fw-bold">عنوان الدرس</label>
        <input type="text" name="lesson_title" class="form-control" value="<?= htmlspecialchars($lesson['lesson_title']); ?>" required>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label fw-bold">تاريخ التنفيذ</label>
        <input type="date" name="lesson_date" class="form-control" value="<?= htmlspecialchars($lesson['lesson_date'] ?? ''); ?>">
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label fw-bold">زمن الحصة</label>
        <select name="lesson_duration" class="form-select">
            <option value="45" <?= ($lesson['lesson_duration'] == 45) ? 'selected' : ''; ?>>45 دقيقة</option>
            <option value="50" <?= ($lesson['lesson_duration'] == 50) ? 'selected' : ''; ?>>50 دقيقة</option>
            <option value="60" <?= ($lesson['lesson_duration'] == 60) ? 'selected' : ''; ?>>60 دقيقة</option>
        </select>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label fw-bold">مستوى الطلبة</label>
        <select name="student_level" class="form-select">
            <?php foreach (['متوسط','متقدم','يحتاج دعم','متفاوت'] as $level): ?>
            <option value="<?= $level; ?>" <?= ($lesson['student_level'] == $level) ? 'selected' : ''; ?>><?= $level; ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<!-- ========== الأهداف ========== -->
<hr><div class="alert alert-success border-0 mb-3"><h6 class="mb-0"><i class="bi bi-bullseye"></i> الأهداف السلوكية</h6></div>
<div class="row">
    <div class="col-12 mb-3">
        <label class="form-label fw-bold">الهدف الأول</label>
        <input type="text" name="objective_1" class="form-control" value="<?= htmlspecialchars($lesson['objective_1'] ?? ''); ?>">
    </div>
    <div class="col-12 mb-3">
        <label class="form-label fw-bold">الهدف الثاني</label>
        <input type="text" name="objective_2" class="form-control" value="<?= htmlspecialchars($lesson['objective_2'] ?? ''); ?>">
    </div>
</div>

<!-- ========== المهارات ========== -->
<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label fw-bold">المهارة الأساسية الأولى</label>
        <input type="text" name="skill_1" class="form-control" value="<?= htmlspecialchars($lesson['skill_1'] ?? ''); ?>">
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label fw-bold">المهارة الأساسية الثانية</label>
        <input type="text" name="skill_2" class="form-control" value="<?= htmlspecialchars($lesson['skill_2'] ?? ''); ?>">
    </div>
</div>

<!-- ========== طريقة التدريس ========== -->
<hr><div class="alert alert-info border-0 mb-3"><h6 class="mb-0"><i class="bi bi-easel-fill"></i> طريقة التدريس والوسائل</h6></div>
<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label fw-bold">طريقة التدريس</label>
        <select name="teaching_method" class="form-select">
            <?php foreach (['التعلم التعاوني','الاستقصاء والاكتشاف','حل المشكلات','التعلم بالمشروع','المناقشة والحوار','التعليم المتمايز','STEM','الفصل المعكوس'] as $m): ?>
            <option value="<?= $m; ?>" <?= ($lesson['teaching_method'] == $m) ? 'selected' : ''; ?>><?= $m; ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label fw-bold">أسلوب التعزيز</label>
        <input type="text" name="reinforcement" class="form-control" value="<?= htmlspecialchars($lesson['reinforcement'] ?? ''); ?>">
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label fw-bold">تكنولوجيا التعليم</label>
        <input type="text" name="technology" class="form-control" value="<?= htmlspecialchars($lesson['technology'] ?? ''); ?>">
    </div>
</div>

<!-- ========== إجراءات الدرس (تحرير مباشر) ========== -->
<hr><div class="alert alert-secondary border-0 mb-3"><h6 class="mb-0"><i class="bi bi-play-circle-fill"></i> إجراءات الدرس (تحرير مباشر)</h6></div>

<div class="mb-3">
    <label class="form-label fw-bold">التمهيد</label>
    <textarea name="introduction" class="form-control" rows="4"><?= htmlspecialchars($planData['introduction'] ?? ''); ?></textarea>
</div>

<div class="mb-3">
    <label class="form-label fw-bold">إجراءات الهدف الأول (كل إجراء في سطر)</label>
    <textarea name="goal_1_procedures" class="form-control" rows="6"><?= htmlspecialchars(implode("\n", $g1procs)); ?></textarea>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label fw-bold text-danger">نص الوقفة التقويمية الأولى</label>
        <textarea name="goal_1_eval_question" class="form-control" rows="3"><?= htmlspecialchars($g1eval['question'] ?? ''); ?></textarea>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label fw-bold">الإجابة النموذجية للهدف الأول</label>
        <textarea name="goal_1_eval_answer" class="form-control" rows="3"><?= htmlspecialchars($g1eval['model_answer'] ?? ''); ?></textarea>
    </div>
</div>

<div class="mb-3">
    <label class="form-label fw-bold">إجراءات الهدف الثاني (كل إجراء في سطر)</label>
    <textarea name="goal_2_procedures" class="form-control" rows="6"><?= htmlspecialchars(implode("\n", $g2procs)); ?></textarea>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label fw-bold text-success">بطاقة التحدي (الخضراء)</label>
        <textarea name="green_card" class="form-control border-success" rows="3"><?= htmlspecialchars($g2diff['green_card'] ?? $lesson['challenge_card'] ?? ''); ?></textarea>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label fw-bold text-warning">بطاقة المساعدة (الصفراء)</label>
        <textarea name="yellow_card" class="form-control border-warning" rows="3"><?= htmlspecialchars($g2diff['yellow_card'] ?? $lesson['support_card'] ?? ''); ?></textarea>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label fw-bold text-danger">نص الوقفة التقويمية الثانية</label>
        <textarea name="goal_2_eval_question" class="form-control" rows="3"><?= htmlspecialchars($g2eval['question'] ?? ''); ?></textarea>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label fw-bold">الإجابة النموذجية للهدف الثاني</label>
        <textarea name="goal_2_eval_answer" class="form-control" rows="3"><?= htmlspecialchars($g2eval['model_answer'] ?? ''); ?></textarea>
    </div>
</div>

<div class="mb-3">
    <label class="form-label fw-bold">الخاتمة</label>
    <textarea name="conclusion" class="form-control" rows="3"><?= htmlspecialchars($planData['conclusion'] ?? ''); ?></textarea>
</div>

<!-- ========== الإثراء ========== -->
<hr><div class="alert alert-dark border-0 mb-3"><h6 class="mb-0"><i class="bi bi-globe2"></i> الإثراء والروابط</h6></div>
<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label fw-bold">الإثراء المنزلي</label>
        <textarea name="homework" class="form-control" rows="2"><?= htmlspecialchars($lesson['homework'] ?? ''); ?></textarea>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label fw-bold">الربط بالامتحانات الوطنية</label>
        <textarea name="national_exams_link" class="form-control" rows="2"><?= htmlspecialchars($lesson['national_exams_link'] ?? ''); ?></textarea>
    </div>
    <div class="col-12 mb-3">
        <label class="form-label fw-bold">الربط بتراث البحرين</label>
        <textarea name="bahrain_link" class="form-control" rows="2"><?= htmlspecialchars($lesson['bahrain_link'] ?? ''); ?></textarea>
    </div>
</div>

<!-- ========== الحالة ========== -->
<hr>
<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label fw-bold">حالة التخطيط</label>
        <select name="status" class="form-select">
            <option value="draft" <?= ($lesson['status'] == 'draft') ? 'selected' : ''; ?>>مسودة</option>
            <option value="published" <?= ($lesson['status'] == 'published') ? 'selected' : ''; ?>>منشور</option>
            <option value="archived" <?= ($lesson['status'] == 'archived') ? 'selected' : ''; ?>>مؤرشف</option>
        </select>
    </div>
</div>

<div class="d-flex gap-2 mt-3">
    <button type="submit" class="btn btn-success btn-lg">
        <i class="bi bi-save-fill"></i> حفظ التعديلات
    </button>
    <a href="view.php?id=<?= $lesson['id']; ?>" class="btn btn-secondary btn-lg">
        رجوع للعرض
    </a>
</div>

</form>

</div>
</div>

</div>
</div>
</div>

<style>
.form-label { font-weight: bold; }
.form-control, .form-select { border-radius: 10px; }
.card { border-radius: 14px; }
.card-header { font-size: 16px; font-weight: bold; }
</style>

<?php include '../../app/views/layouts/footer.php'; ?>
