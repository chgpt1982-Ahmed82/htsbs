<?php
echo $_SESSION['user_id'];

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../app/models/LessonPlanner.php';
require_once '../../app/models/Notification.php';

if (!isset($_SESSION['user_id'])) {

    header("Location: " . BASE_URL . "/login.php");
    exit;

}

if ($_SESSION['role_id'] != 2) {

    die("Access Denied");

}

$db = (new Database())->connect();

$lessonPlanner = new LessonPlanner();

$notificationModel = new Notification();

$count = $notificationModel->unreadCount(
    $_SESSION['user_id']
);

/*
==================================================
المواد الخاصة بالمعلم
==================================================
*/
$stmt = $db->prepare("
    SELECT id
    FROM teachers
    WHERE user_id = ?
");

$stmt->execute([
    $_SESSION['user_id']
]);

$teacher = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$teacher) {
    die('Teacher not found');
}

$teacherId = $teacher['id'];

$stmt = $db->prepare("

SELECT

    c.id,
    c.course_name

FROM course_assignments ca

INNER JOIN courses c
ON c.id = ca.course_id

WHERE ca.teacher_id = ?

GROUP BY c.id

ORDER BY c.course_name

");

$stmt->execute([
    $teacherId
]);

$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);







/*
==================================================
الصفوف الخاصة بالمعلم
==================================================
*/

$stmt = $db->prepare("

SELECT

    cl.id,
    cl.class_name

FROM course_assignments ca

INNER JOIN classes cl
ON cl.id = ca.class_id

WHERE ca.teacher_id = ?

GROUP BY cl.id

ORDER BY cl.class_name

");

$stmt->execute([
    $teacherId
]);

$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../app/views/layouts/header.php';

?>

<div class="container-fluid">

<?php include '../../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">

<div class="card shadow border-0 mb-4">

<div class="card-header bg-primary text-white">

<h4 class="mb-0">

<i class="bi bi-stars"></i>

إنشاء تحضير درس جديد

</h4>

</div>

<div class="card-body">

<form
action="generate.php"
method="POST">

<div class="row">

<!-- المادة -->

<div class="col-md-6 mb-3">

<label class="form-label">

المادة

</label>

<select
name="subject_id"
class="form-select"
required>

<option value="">

اختر المادة

</option>

<?php foreach($courses as $course): ?>

<option
value="<?= $course['id']; ?>">

<?= htmlspecialchars($course['course_name']); ?>

</option>

<?php endforeach; ?>

</select>

</div>

<!-- الصف -->

<div class="col-md-6 mb-3">

<label class="form-label">

الصف

</label>

<select
name="class_id"
class="form-select"
required>

<option value="">

اختر الصف

</option>

<?php foreach($classes as $class): ?>

<option
value="<?= $class['id']; ?>">

<?= htmlspecialchars($class['class_name']); ?>

</option>

<?php endforeach; ?>

</select>

</div>

<!-- الوحدة -->

<div class="col-md-6 mb-3">

<label class="form-label">

الوحدة

</label>

<input
type="text"
name="unit_name"
class="form-control"
required>

</div>

<!-- عنوان الدرس -->

<div class="col-md-6 mb-3">

<label class="form-label">

عنوان الدرس

</label>

<input
type="text"
name="lesson_title"
class="form-control"
required>

</div>



<!-- وصف الدرس -->

<div class="col-md-12 mb-3">

    <label class="form-label">

        وصف الدرس

    </label>

    <textarea
    name="lesson_description"
    class="form-control"
    rows="4"
    placeholder="اكتب وصفاً مختصراً للدرس..."
    required></textarea>

</div>

<!-- نواتج التعلم -->

<div class="col-md-12 mb-3">

    <label class="form-label">

        نواتج التعلم

    </label>

    <textarea
    name="learning_outcomes"
    class="form-control"
    rows="4"
    placeholder="كل ناتج تعلم في سطر مستقل..."
    required></textarea>

</div>

<!-- الكلمات المفتاحية -->

<div class="col-md-6 mb-3">

    <label class="form-label">

        الكلمات المفتاحية

    </label>

    <input
    type="text"
    name="keywords"
    class="form-control"
    placeholder="مثال: الخوارزميات، البرمجة، المتغيرات">

</div>

<!-- زمن الحصة -->

<div class="col-md-3 mb-3">

    <label class="form-label">

        زمن الحصة

    </label>

    <select
    name="lesson_duration"
    class="form-select">

        <option value="45" selected>

            45 دقيقة

        </option>

        <option value="50">

            50 دقيقة

        </option>

        <option value="60">

            60 دقيقة

        </option>

    </select>

</div>

<!-- مستوى الطلبة -->

<div class="col-md-3 mb-3">

    <label class="form-label">

        مستوى الطلبة

    </label>

    <select
    name="student_level"
    class="form-select">

        <option value="متوسط" selected>

            متوسط

        </option>

        <option value="متقدم">

            متقدم

        </option>

        <option value="يحتاج دعم">

            يحتاج دعم

        </option>

        <option value="متفاوت">

            متفاوت المستويات

        </option>

    </select>

</div>

<hr class="my-4">

<h5 class="text-primary mb-3">

<i class="bi bi-easel-fill"></i>

وسائل التعليم

</h5>

<div class="row">

<div class="col-md-3">

<div class="form-check">

<input
class="form-check-input"
type="checkbox"
name="resources[]"
value="السبورة الذكية"
id="r1">

<label
class="form-check-label"
for="r1">

السبورة الذكية

</label>

</div>

<div class="form-check">

<input
class="form-check-input"
type="checkbox"
name="resources[]"
value="PowerPoint"
id="r2">

<label
class="form-check-label"
for="r2">

PowerPoint

</label>

</div>

<div class="form-check">

<input
class="form-check-input"
type="checkbox"
name="resources[]"
value="فيديو تعليمي"
id="r3">

<label
class="form-check-label"
for="r3">

فيديو تعليمي

</label>

</div>

</div>

<div class="col-md-3">

<div class="form-check">

<input
class="form-check-input"
type="checkbox"
name="resources[]"
value="صور تعليمية"
id="r4">

<label
class="form-check-label"
for="r4">

صور تعليمية

</label>

</div>

<div class="form-check">

<input
class="form-check-input"
type="checkbox"
name="resources[]"
value="أوراق عمل"
id="r5">

<label
class="form-check-label"
for="r5">

أوراق عمل

</label>

</div>

<div class="form-check">

<input
class="form-check-input"
type="checkbox"
name="resources[]"
value="مجسمات"
id="r6">

<label
class="form-check-label"
for="r6">

مجسمات

</label>

</div>

</div>

<div class="col-md-3">

<div class="form-check">

<input
class="form-check-input"
type="checkbox"
name="resources[]"
value="ChatGPT"
id="r7">

<label
class="form-check-label"
for="r7">

ChatGPT

</label>

</div>

<div class="form-check">

<input
class="form-check-input"
type="checkbox"
name="resources[]"
value="Gemini"
id="r8">

<label
class="form-check-label"
for="r8">

Gemini

</label>

</div>

<div class="form-check">

<input
class="form-check-input"
type="checkbox"
name="resources[]"
value="Copilot"
id="r9">

<label
class="form-check-label"
for="r9">

Microsoft Copilot

</label>

</div>

</div>

<div class="col-md-3">

<div class="form-check">

<input
class="form-check-input"
type="checkbox"
name="resources[]"
value="Kahoot"
id="r10">

<label
class="form-check-label"
for="r10">

Kahoot

</label>

</div>

<div class="form-check">

<input
class="form-check-input"
type="checkbox"
name="resources[]"
value="Quizizz"
id="r11">

<label
class="form-check-label"
for="r11">

Quizizz

</label>

</div>

<div class="form-check">

<input
class="form-check-input"
type="checkbox"
name="resources[]"
value="محاكاة تفاعلية"
id="r12">

<label
class="form-check-label"
for="r12">

محاكاة تفاعلية

</label>

</div>

</div>

</div>

<hr class="my-4">

<h5 class="text-success mb-3">
    
    


<i class="bi bi-cpu-fill"></i>

طريقة إنشاء التحضير

</h5>

<div class="row">

<div class="col-md-6">

<div class="form-check">

<input
class="form-check-input"
type="radio"
name="generator"
value="ai"
checked
id="ai">

<label
class="form-check-label"
for="ai">

إنشاء التحضير باستخدام الذكاء الاصطناعي

</label>

</div>

</div>

<div class="col-md-6">

<div class="form-check">

<input
class="form-check-input"
type="radio"
name="generator"
value="manual"
id="manual">

<label
class="form-check-label"
for="manual">

إنشاء التحضير يدوياً

</label>

</div>

</div>

</div>




<hr class="my-4">

<h5 class="text-danger mb-3">

    <i class="bi bi-stars"></i>

    تعليمات إضافية للذكاء الاصطناعي

</h5>

<div class="mb-4">

    <label class="form-label">

        Prompt إضافي (اختياري)

    </label>

    <textarea
        name="ai_prompt"
        class="form-control"
        rows="5"
        placeholder="مثال:
- ركز على التعلم التعاوني.
- استخدم استراتيجيات STEM.
- اجعل الأنشطة مناسبة للمتعلمين المتفوقين.
- أضف أسئلة تفكير ناقد.
- استخدم أسلوب وزارة التربية والتعليم بمملكة البحرين.
"></textarea>

    <div class="form-text">

        يمكن كتابة أي تعليمات إضافية ليقوم الذكاء الاصطناعي بتخصيص التحضير.

    </div>

</div>

<hr class="my-4">

<h5 class="text-primary mb-3">

    <i class="bi bi-gear-fill"></i>

    إعدادات إنشاء التحضير

</h5>

<div class="row">

    <!-- نموذج الذكاء الاصطناعي -->

    <div class="col-md-4 mb-3">

        <label class="form-label">

            نموذج الذكاء الاصطناعي

        </label>

        <select
            name="ai_model"
            class="form-select">

            <option value="gpt-5.5" selected>

                GPT-5.5

            </option>

            <option value="gpt-5">

                GPT-5

            </option>

            <option value="gpt-4.1">

                GPT-4.1

            </option>

        </select>

    </div>

    <!-- لغة التحضير -->

    <div class="col-md-4 mb-3">

        <label class="form-label">

            لغة التحضير

        </label>

        <select
            name="language"
            class="form-select">

            <option value="ar" selected>

                العربية

            </option>

            <option value="en">

                English

            </option>

        </select>

    </div>

    <!-- حالة الحفظ -->

    <div class="col-md-4 mb-3">

        <label class="form-label">

            حالة التحضير

        </label>

        <select
            name="status"
            class="form-select">

            <option value="Draft" selected>

                مسودة

            </option>

            <option value="Published">

                منشور

            </option>

        </select>

    </div>

</div>

<hr class="my-4">

<div class="alert alert-info">

    <i class="bi bi-lightbulb-fill"></i>

    سيتم إنشاء تحضير احترافي يشمل:

    <ul class="mt-2 mb-0">

        <li>الأهداف التعليمية (SMART)</li>

        <li>النشاط الاستهلالي</li>

        <li>المقدمة</li>

        <li>استراتيجيات التدريس</li>

        <li>الأنشطة الصفية</li>

        <li>التقويم البنائي والختامي</li>

        <li>الواجب المنزلي</li>

        <li>التمايز</li>

        <li>مهارات القرن الحادي والعشرين</li>

        <li>القيم</li>

        <li>توزيع زمن الحصة (45 دقيقة)</li>

    </ul>

</div>

<div class="d-flex justify-content-between mt-4">

    <a
        href="index.php"
        class="btn btn-secondary btn-lg">

        <i class="bi bi-arrow-right-circle"></i>

        رجوع

    </a>

    <div>

        <button
            type="submit"
            name="action"
            value="draft"
            class="btn btn-warning btn-lg">

            <i class="bi bi-save"></i>

            حفظ كمسودة

        </button>

        <button
            type="submit"
            name="action"
            value="generate"
            class="btn btn-primary btn-lg ms-2">

            <i class="bi bi-stars"></i>

            إنشاء التحضير بالذكاء الاصطناعي

        </button>

    </div>

</div>

        </form>

    </div>

</div>

</div>

</div>

<!-- ==========================================
Loading Modal
========================================== -->

<div class="modal fade"
     id="loadingModal"
     data-bs-backdrop="static"
     data-bs-keyboard="false"
     tabindex="-1">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <div class="modal-body text-center p-5">

                <div class="spinner-border text-primary"
                     style="width:4rem;height:4rem;">

                </div>

                <h4 class="mt-4">

                    جاري إنشاء التحضير...

                </h4>

                <p class="text-muted">

                    يرجى الانتظار قليلاً أثناء قيام الذكاء الاصطناعي
                    بإنشاء التحضير الاحترافي.

                </p>

            </div>

        </div>

    </div>

</div>

<script>

document.addEventListener("DOMContentLoaded", function () {

    const form = document.querySelector("form");

    const aiRadio = document.getElementById("ai");

    const manualRadio = document.getElementById("manual");

    const aiSection = document.querySelector("textarea[name='ai_prompt']")
                              .closest(".mb-4");

    function toggleAISection() {

        if (aiRadio.checked) {

            aiSection.style.display = "block";

        } else {

            aiSection.style.display = "none";

        }

    }

    toggleAISection();

    aiRadio.addEventListener("change", toggleAISection);

    manualRadio.addEventListener("change", toggleAISection);

    form.addEventListener("submit", function () {

        if (aiRadio.checked) {

            const modal = new bootstrap.Modal(
                document.getElementById("loadingModal")
            );

            modal.show();

        }

    });

});

</script>

<style>

.form-label{

    font-weight:bold;

}

.form-control,
.form-select{

    border-radius:10px;

}

.card{

    border-radius:15px;

}

.card-header{

    font-size:20px;

    font-weight:bold;

}

.btn{

    border-radius:10px;

}

.form-check{

    margin-bottom:10px;

}

.alert ul{

    padding-right:20px;

}

.spinner-border{

    animation-duration:.8s;

}

</style>

<?php include '../../app/views/layouts/footer.php'; ?>




