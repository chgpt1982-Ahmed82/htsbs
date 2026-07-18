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
    die("ليس لديك صلاحية الوصول.");
}

$db = (new Database())->connect();

$notificationModel = new Notification();
$count = $notificationModel->unreadCount($_SESSION['user_id']);

/*
==================================================
جلب معرف المعلم
==================================================
*/

$stmt = $db->prepare("
SELECT id
FROM teachers
WHERE user_id = ?
");

$stmt->execute([$_SESSION['user_id']]);
$teacher = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$teacher) {
    die('Teacher not found');
}

$teacherId = $teacher['id'];

/*
==================================================
المواد الخاصة بالمعلم
==================================================
*/

$stmt = $db->prepare("
SELECT c.id, c.course_name
FROM course_assignments ca
INNER JOIN courses c ON c.id = ca.course_id
WHERE ca.teacher_id = ?
GROUP BY c.id
ORDER BY c.course_name
");

$stmt->execute([$teacherId]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
==================================================
الصفوف الخاصة بالمعلم
==================================================
*/

$stmt = $db->prepare("
SELECT cl.id, cl.class_name
FROM course_assignments ca
INNER JOIN classes cl ON cl.id = ca.class_id
WHERE ca.teacher_id = ?
GROUP BY cl.id
ORDER BY cl.class_name
");

$stmt->execute([$teacherId]);
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../app/views/layouts/header.php';

?>

<div class="container-fluid">

<div class="row">

<?php include '../../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">

<?php if (isset($_SESSION['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <?= $_SESSION['error']; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['error']); ?>
<?php endif; ?>

<div class="card shadow border-0 mb-4">

<div class="card-header bg-primary text-white">
<h4 class="mb-0">
    <i class="bi bi-journal-richtext"></i>
    إنشاء تخطيط درس عميق
</h4>
</div>

<div class="card-body">

<form action="generate.php" method="POST">

<!-- ==========================================
القسم الأول: المعلومات الأساسية
========================================== -->

<div class="alert alert-primary border-0 mb-4">
    <h5 class="mb-0"><i class="bi bi-info-circle-fill"></i> المعلومات الأساسية</h5>
</div>

<div class="row">

    <div class="col-md-6 mb-3">
        <label class="form-label fw-bold">المادة الدراسية</label>
        <select name="subject_id" class="form-select" required>
            <option value="">اختر المادة</option>
            <?php foreach ($courses as $course): ?>
            <option value="<?= $course['id']; ?>">
                <?= htmlspecialchars($course['course_name']); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label fw-bold">الصف الدراسي</label>
        <select name="class_id" class="form-select" required>
            <option value="">اختر الصف</option>
            <?php foreach ($classes as $class): ?>
            <option value="<?= $class['id']; ?>">
                <?= htmlspecialchars($class['class_name']); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label fw-bold">الوحدة الدراسية</label>
        <input type="text" name="unit_name" class="form-control" placeholder="مثال: الوحدة الثالثة - المعادلات" required>
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label fw-bold">عنوان الدرس</label>
        <input type="text" name="lesson_title" class="form-control" placeholder="مثال: حل المعادلات التربيعية" required>
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label fw-bold">تاريخ التنفيذ</label>
        <input type="date" name="lesson_date" class="form-control" value="<?= date('Y-m-d'); ?>">
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label fw-bold">زمن الحصة</label>
        <select name="lesson_duration" class="form-select">
            <option value="45" selected>45 دقيقة</option>
            <option value="50">50 دقيقة</option>
            <option value="60">60 دقيقة</option>
        </select>
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label fw-bold">مستوى الطلبة</label>
        <select name="student_level" class="form-select">
            <option value="متوسط" selected>متوسط</option>
            <option value="متقدم">متقدم</option>
            <option value="يحتاج دعم">يحتاج دعم</option>
            <option value="متفاوت">متفاوت المستويات</option>
        </select>
    </div>

</div>

<hr class="my-4">

<!-- ==========================================
الأهداف السلوكية
========================================== -->

<div class="alert alert-success border-0 mb-3">
    <h5 class="mb-0"><i class="bi bi-bullseye"></i> الأهداف السّلوكيّة <small class="text-muted fs-6">(الهدف الثاني في مستوى التحليل أو التركيب أو التقويم)</small></h5>
</div>

<div class="row">
    <div class="col-12 mb-3">
        <label class="form-label fw-bold">الهدف الأول</label>
        <input type="text" name="objective_1" class="form-control" placeholder="يحلل الطالب ... من خلال ..." required>
    </div>
    <div class="col-12 mb-3">
        <label class="form-label fw-bold">الهدف الثاني</label>
        <input type="text" name="objective_2" class="form-control" placeholder="يقيّم الطالب ... بمعيار ...">
    </div>
</div>

<hr class="my-4">

<!-- ==========================================
المهارات الأساسية
========================================== -->

<div class="alert alert-warning border-0 mb-3">
    <h5 class="mb-0"><i class="bi bi-tools"></i> المهارات الأساسية اللازمة <small class="text-muted fs-6">(في ضوء التقويم التشخيصي)</small></h5>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label fw-bold">المهارة الأولى</label>
        <input type="text" name="skill_1" class="form-control" placeholder="مثال: حل المعادلات من الدرجة الأولى">
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label fw-bold">المهارة الثانية</label>
        <input type="text" name="skill_2" class="form-control" placeholder="مثال: استخراج الجذور التربيعية">
    </div>
</div>

<hr class="my-4">

<!-- ==========================================
طريقة التدريس والوسائل
========================================== -->

<div class="alert alert-info border-0 mb-3">
    <h5 class="mb-0"><i class="bi bi-easel-fill"></i> طريقة التدريس والوسائل التعليمية</h5>
</div>

<div class="row">

    <div class="col-md-4 mb-3">
        <label class="form-label fw-bold">طريقة التدريس الأساسية</label>
        <select name="teaching_method" class="form-select">
            <option value="التعلم التعاوني">التعلم التعاوني</option>
            <option value="الاستقصاء والاكتشاف">الاستقصاء والاكتشاف</option>
            <option value="حل المشكلات">حل المشكلات</option>
            <option value="التعلم بالمشروع">التعلم بالمشروع</option>
            <option value="المناقشة والحوار">المناقشة والحوار</option>
            <option value="التعليم المتمايز">التعليم المتمايز</option>
            <option value="STEM">STEM</option>
            <option value="الفصل المعكوس">الفصل المعكوس</option>
        </select>
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label fw-bold">أسلوب التعزيز والتحفيز</label>
        <input type="text" name="reinforcement" class="form-control" placeholder="مثال: الدوجو - التصفيق - الشارات">
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label fw-bold">توظيف تكنولوجيا التعليم</label>
        <input type="text" name="technology" class="form-control" placeholder="مثال: السبورة الذكية، Kahoot">
    </div>

</div>

<div class="mb-3">
    <label class="form-label fw-bold">الوسائل التعليمية المستخدمة</label>
    <div class="row">
        <div class="col-md-3">
            <div class="form-check"><input class="form-check-input" type="checkbox" name="resources[]" value="السبورة الذكية" id="r1"><label class="form-check-label" for="r1">السبورة الذكية</label></div>
            <div class="form-check"><input class="form-check-input" type="checkbox" name="resources[]" value="PowerPoint" id="r2"><label class="form-check-label" for="r2">PowerPoint</label></div>
            <div class="form-check"><input class="form-check-input" type="checkbox" name="resources[]" value="فيديو تعليمي" id="r3"><label class="form-check-label" for="r3">فيديو تعليمي</label></div>
        </div>
        <div class="col-md-3">
            <div class="form-check"><input class="form-check-input" type="checkbox" name="resources[]" value="أوراق عمل" id="r4"><label class="form-check-label" for="r4">أوراق عمل</label></div>
            <div class="form-check"><input class="form-check-input" type="checkbox" name="resources[]" value="مجسمات" id="r5"><label class="form-check-label" for="r5">مجسمات</label></div>
            <div class="form-check"><input class="form-check-input" type="checkbox" name="resources[]" value="صور تعليمية" id="r6"><label class="form-check-label" for="r6">صور تعليمية</label></div>
        </div>
        <div class="col-md-3">
            <div class="form-check"><input class="form-check-input" type="checkbox" name="resources[]" value="Kahoot" id="r7"><label class="form-check-label" for="r7">Kahoot</label></div>
            <div class="form-check"><input class="form-check-input" type="checkbox" name="resources[]" value="Quizizz" id="r8"><label class="form-check-label" for="r8">Quizizz</label></div>
            <div class="form-check"><input class="form-check-input" type="checkbox" name="resources[]" value="ChatGPT" id="r9"><label class="form-check-label" for="r9">ChatGPT / AI</label></div>
        </div>
        <div class="col-md-3">
            <div class="form-check"><input class="form-check-input" type="checkbox" name="resources[]" value="محاكاة تفاعلية" id="r10"><label class="form-check-label" for="r10">محاكاة تفاعلية</label></div>
            <div class="form-check"><input class="form-check-input" type="checkbox" name="resources[]" value="بطاقات تعليمية" id="r11"><label class="form-check-label" for="r11">بطاقات تعليمية</label></div>
            <div class="form-check"><input class="form-check-input" type="checkbox" name="resources[]" value="مختبر" id="r12"><label class="form-check-label" for="r12">مختبر</label></div>
        </div>
    </div>
</div>

<hr class="my-4">

<!-- ==========================================
محتوى الدرس والتمهيد
========================================== -->

<div class="alert alert-secondary border-0 mb-3">
    <h5 class="mb-0"><i class="bi bi-play-circle-fill"></i> التمهيد وإجراءات الدرس</h5>
</div>

<div class="mb-3">
    <label class="form-label fw-bold">وصف الدرس ومحتواه الأساسي</label>
    <textarea name="lesson_description" class="form-control" rows="4" placeholder="اكتب وصفاً مختصراً للمحتوى المراد تدريسه..." required></textarea>
</div>

<div class="mb-3">
    <label class="form-label fw-bold">نواتج التعلم المتوقعة</label>
    <textarea name="learning_outcomes" class="form-control" rows="3" placeholder="كل ناتج تعلم في سطر مستقل..."></textarea>
</div>

<div class="mb-3">
    <label class="form-label fw-bold">الكلمات المفتاحية</label>
    <input type="text" name="keywords" class="form-control" placeholder="مثال: تحليل، استنتاج، تقييم، تطبيق">
</div>

<hr class="my-4">

<!-- ==========================================
سياسة التمايز 6G6Y
========================================== -->

<div class="alert alert-danger border-0 mb-3">
    <h5 class="mb-0"><i class="bi bi-diagram-3-fill"></i> سياسة التمايز <span class="badge bg-danger">6G6Y</span></h5>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label fw-bold text-success"><i class="bi bi-square-fill text-success"></i> بطاقة التحدي (الورقة الخضراء) - للمتفوقين</label>
        <textarea name="challenge_card" class="form-control border-success" rows="3" placeholder="أنشطة تحدي للطلبة المنجزين مبكراً (أول 6 طلبة)..."></textarea>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label fw-bold text-warning"><i class="bi bi-square-fill text-warning"></i> بطاقة المساعدة (الورقة الصفراء) - للمتعثرين</label>
        <textarea name="support_card" class="form-control border-warning" rows="3" placeholder="بطاقات لتحديد خطوات الحل، أو مساعدات أكاديمية (6 طلبة متعثرين)..."></textarea>
    </div>
</div>

<hr class="my-4">

<!-- ==========================================
الربط والإثراء
========================================== -->

<div class="alert alert-dark border-0 mb-3">
    <h5 class="mb-0"><i class="bi bi-globe2"></i> الربط والإثراء</h5>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label fw-bold">الربط بالامتحانات الوطنية / IELTS</label>
        <textarea name="national_exams_link" class="form-control" rows="2" placeholder="اذكر نوع الأسئلة أو المهارات المرتبطة بالامتحانات الوطنية..."></textarea>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label fw-bold">الإثراء المنزلي (الواجب)</label>
        <textarea name="homework" class="form-control" rows="2" placeholder="الإثراء المنزلي أو الواجب المقترح..."></textarea>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label fw-bold">الربط بتراث مملكة البحرين والمواطنة</label>
        <textarea name="bahrain_link" class="form-control" rows="2" placeholder="الربط بتراث البحرين، المواطنة المحلية أو العالمية..."></textarea>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label fw-bold">المرافق المدرسية المستخدمة</label>
        <div class="row">
            <div class="col-6">
                <div class="form-check"><input class="form-check-input" type="checkbox" name="facilities[]" value="مركز مصادر التعلم" id="f1"><label class="form-check-label" for="f1">مركز مصادر التعلم</label></div>
                <div class="form-check"><input class="form-check-input" type="checkbox" name="facilities[]" value="الصف الإلكتروني" id="f2"><label class="form-check-label" for="f2">الصف الإلكتروني</label></div>
                <div class="form-check"><input class="form-check-input" type="checkbox" name="facilities[]" value="مختبر الحاسوب" id="f3"><label class="form-check-label" for="f3">مختبر الحاسوب</label></div>
                <div class="form-check"><input class="form-check-input" type="checkbox" name="facilities[]" value="معمل الكيمياء" id="f4"><label class="form-check-label" for="f4">معمل الكيمياء</label></div>
            </div>
            <div class="col-6">
                <div class="form-check"><input class="form-check-input" type="checkbox" name="facilities[]" value="معمل الفيزياء" id="f5"><label class="form-check-label" for="f5">معمل الفيزياء</label></div>
                <div class="form-check"><input class="form-check-input" type="checkbox" name="facilities[]" value="معمل الأحياء" id="f6"><label class="form-check-label" for="f6">معمل الأحياء</label></div>
                <div class="form-check"><input class="form-check-input" type="checkbox" name="facilities[]" value="مرسم الفنون" id="f7"><label class="form-check-label" for="f7">مرسم الفنون</label></div>
                <div class="form-check"><input class="form-check-input" type="checkbox" name="facilities[]" value="غرفة الموسيقى" id="f8"><label class="form-check-label" for="f8">غرفة الموسيقى</label></div>
            </div>
        </div>
    </div>
</div>

<hr class="my-4">

<!-- ==========================================
إعدادات الذكاء الاصطناعي
========================================== -->

<div class="alert alert-primary border-0 mb-3">
    <h5 class="mb-0"><i class="bi bi-cpu-fill"></i> إعدادات إنشاء التخطيط</h5>
</div>

<div class="row">

    <div class="col-md-4 mb-3">
        <label class="form-label fw-bold">طريقة إنشاء التخطيط</label>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="generator" value="ai" id="ai" checked>
            <label class="form-check-label" for="ai">بالذكاء الاصطناعي</label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="generator" value="manual" id="manual">
            <label class="form-check-label" for="manual">يدوياً</label>
        </div>
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label fw-bold">نموذج الذكاء الاصطناعي</label>
        <select name="ai_model" class="form-select">
            <option value="gpt-5.5" selected>GPT-5.5</option>
            <option value="gpt-5">GPT-5</option>
            <option value="gpt-4.1">GPT-4.1</option>
        </select>
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label fw-bold">حالة التخطيط</label>
        <select name="status" class="form-select">
            <option value="draft" selected>مسودة</option>
            <option value="published">منشور</option>
        </select>
    </div>

</div>

<div class="mb-4" id="aiPromptSection">
    <label class="form-label fw-bold">تعليمات إضافية للذكاء الاصطناعي <span class="badge bg-secondary">اختياري</span></label>
    <textarea name="ai_prompt" class="form-control" rows="4"
    placeholder="مثال:
- ركز على مهارات التفكير الناقد.
- استخدم سياق مملكة البحرين.
- أضف أسئلة وقفة تقويمية مكتوبة.
- اجعل سياق الوقفة التقويمية مختلفاً عن سياق الأنشطة التكوينية.
- التزم بنموذج التخطيط العميق (وقفتان تقويميتان، تمايز 6G6Y)."></textarea>
</div>

<div class="alert alert-info">
    <i class="bi bi-lightbulb-fill"></i>
    سيتم إنشاء تخطيط درس عميق يشمل:
    <ul class="mt-2 mb-0">
        <li>أهداف سلوكية في مستوى التحليل أو التركيب أو التقويم (Bloom)</li>
        <li>التمهيد ومراقبة سلوك الطلبة</li>
        <li>إجراءات الهدف الأول (15 دقيقة)</li>
        <li><strong>وقفة تقويمية مكتوبة للهدف الأول</strong> (5 دقائق) + تغذية راجعة (5 دقائق)</li>
        <li>إجراءات الهدف الثاني (15 دقيقة) + <strong>سياسة التمايز 6G6Y</strong></li>
        <li><strong>وقفة تقويمية مكتوبة للهدف الثاني</strong> (5 دقائق) + تغذية راجعة (5 دقائق)</li>
        <li>الخاتمة والتطبيق الواقعي</li>
        <li>مهارات القرن الحادي والعشرين ومؤشرات الانهماك</li>
        <li>الربط بتراث مملكة البحرين والمواطنة</li>
        <li>الإجراءات مع فئات الطلبة المختلفة (مبادرة خذ بيدي)</li>
    </ul>
</div>

<div class="d-flex justify-content-between mt-4">

    <a href="index.php" class="btn btn-secondary btn-lg">
        <i class="bi bi-arrow-right-circle"></i> رجوع
    </a>

    <div>
        <button type="submit" name="action" value="draft" class="btn btn-warning btn-lg">
            <i class="bi bi-save"></i> حفظ كمسودة
        </button>
        <button type="submit" name="action" value="generate" class="btn btn-primary btn-lg ms-2">
            <i class="bi bi-stars"></i> إنشاء التخطيط بالذكاء الاصطناعي
        </button>
    </div>

</div>

</form>

</div>

</div>

</div>

</div>

</div>

<!-- Loading Modal -->
<div class="modal fade" id="loadingModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-5">
                <div class="spinner-border text-primary" style="width:4rem;height:4rem;"></div>
                <h4 class="mt-4">جاري إنشاء التخطيط العميق...</h4>
                <p class="text-muted">يرجى الانتظار قليلاً أثناء قيام الذكاء الاصطناعي بإنشاء التخطيط المفصّل.</p>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector("form");
    const aiRadio = document.getElementById("ai");
    const manualRadio = document.getElementById("manual");
    const aiPromptSection = document.getElementById("aiPromptSection");

    function toggleAISection() {
        aiPromptSection.style.display = aiRadio.checked ? "block" : "none";
    }

    toggleAISection();
    aiRadio.addEventListener("change", toggleAISection);
    manualRadio.addEventListener("change", toggleAISection);

    form.addEventListener("submit", function () {
        if (aiRadio.checked) {
            const modal = new bootstrap.Modal(document.getElementById("loadingModal"));
            modal.show();
        }
    });
});
</script>

<style>
.form-label { font-weight: bold; }
.form-control, .form-select { border-radius: 10px; }
.card { border-radius: 15px; }
.card-header { font-size: 18px; font-weight: bold; }
.btn { border-radius: 10px; }
.form-check { margin-bottom: 8px; }
</style>

<?php include '../../app/views/layouts/footer.php'; ?>
