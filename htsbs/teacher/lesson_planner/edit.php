<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';

require_once '../../app/models/LessonPlanner.php';
require_once '../../app/helpers/LessonPlanRenderer.php';

if (!isset($_SESSION['user_id'])) {

    header("Location: " . BASE_URL . "/login.php");
    exit;

}

if ($_SESSION['role_id'] != 2) {

    die("Access Denied");

}

$db = (new Database())->connect();

$lessonPlanner = new LessonPlanner();

/*
==================================================
رقم التحضير
==================================================
*/

$id = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;

if ($id <= 0) {

    die("رقم التحضير غير صحيح.");

}

/*
==================================================
جلب التحضير
==================================================
*/

$stmt = $db->prepare("

SELECT *

FROM lesson_plans

WHERE id = ?

LIMIT 1

");

$stmt->execute([$id]);

$lesson = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$lesson) {

    die("التحضير غير موجود.");

}

/*
==================================================
قراءة JSON
==================================================
*/

$lessonData = [];

if (!empty($lesson['lesson_plan_json'])) {

    $lessonData = json_decode(

        $lesson['lesson_plan_json'],

        true

    );

    if (!is_array($lessonData)) {

        $lessonData = [];

    }

}

/*
==================================================
رسائل النظام
==================================================
*/

$success = $_SESSION['success'] ?? '';

$error = $_SESSION['error'] ?? '';

unset($_SESSION['success']);
unset($_SESSION['error']);

include '../../app/views/layouts/header.php';

?>

<div class="container-fluid mt-4">

<div class="row">

<?php include '../../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>

        <h2 class="fw-bold">

            <i class="bi bi-pencil-square"></i>

            تعديل التحضير

        </h2>

        <p class="text-muted">

            يمكنك تعديل جميع أجزاء التحضير قبل الحفظ.

        </p>

    </div>

    <div>

        <a href="view.php?id=<?= $lesson['id']; ?>"

           class="btn btn-secondary">

            <i class="bi bi-arrow-right-circle"></i>

            رجوع

        </a>

    </div>

</div>

<?php if($success): ?>

<div class="alert alert-success">

    <?= htmlspecialchars($success); ?>

</div>

<?php endif; ?>

<?php if($error): ?>

<div class="alert alert-danger">

    <?= htmlspecialchars($error); ?>

</div>

<?php endif; ?>

<form

method="post"

action="update.php"

>

<input

type="hidden"

name="id"

value="<?= $lesson['id']; ?>">

<input

type="hidden"

name="version_no"

value="<?= $lesson['version_no']; ?>">

<!-- ==================================================
معلومات الدرس
================================================== -->

<div class="card shadow border-0 mb-4">

    <div class="card-header bg-primary text-white">

        <h5 class="mb-0">

            <i class="bi bi-book-fill"></i>

            معلومات الدرس

        </h5>

    </div>

    <div class="card-body">

        <div class="row">

            <div class="col-md-6 mb-3">

                <label class="form-label">

                    المادة

                </label>

                <input
                    type="text"
                    class="form-control"
                    name="lesson_info[subject]"
                    value="<?= htmlspecialchars($lessonData['lesson_info']['subject'] ?? '') ?>">

            </div>

            <div class="col-md-6 mb-3">

                <label class="form-label">

                    الصف

                </label>

                <input
                    type="text"
                    class="form-control"
                    name="lesson_info[grade]"
                    value="<?= htmlspecialchars($lessonData['lesson_info']['grade'] ?? '') ?>">

            </div>

            <div class="col-md-6 mb-3">

                <label class="form-label">

                    الوحدة

                </label>

                <input
                    type="text"
                    class="form-control"
                    name="lesson_info[unit]"
                    value="<?= htmlspecialchars($lessonData['lesson_info']['unit'] ?? '') ?>">

            </div>

            <div class="col-md-6 mb-3">

                <label class="form-label">

                    عنوان الدرس

                </label>

                <input
                    type="text"
                    class="form-control"
                    name="lesson_info[lesson_title]"
                    value="<?= htmlspecialchars($lessonData['lesson_info']['lesson_title'] ?? '') ?>">

            </div>

            <div class="col-md-6 mb-3">

                <label class="form-label">

                    مدة الحصة

                </label>

                <input
                    type="text"
                    class="form-control"
                    name="lesson_info[duration]"
                    value="<?= htmlspecialchars($lessonData['lesson_info']['duration'] ?? '') ?>">

            </div>

            <div class="col-md-6 mb-3">

                <label class="form-label">

                    مستوى الطلبة

                </label>

                <select
                    class="form-select"
                    name="student_level">

                    <option value="ضعيف"

                        <?= ($lesson['student_level']=='ضعيف') ? 'selected' : '' ?>>

                        ضعيف

                    </option>

                    <option value="متوسط"

                        <?= ($lesson['student_level']=='متوسط') ? 'selected' : '' ?>>

                        متوسط

                    </option>

                    <option value="متقدم"

                        <?= ($lesson['student_level']=='متقدم') ? 'selected' : '' ?>>

                        متقدم

                    </option>

                </select>

            </div>

        </div>

    </div>

</div>

<!-- ==================================================
أهداف التعلم
================================================== -->

<div class="card shadow border-0 mb-4">

    <div class="card-header bg-success text-white">

        <h5 class="mb-0">

            <i class="bi bi-bullseye"></i>

            أهداف التعلم

        </h5>

    </div>

    <div class="card-body">

        <?php

        $objectives = $lessonData['objectives'] ?? [];

        if (count($objectives) == 0) {

            $objectives = ['', '', ''];

        }

        ?>

        <?php foreach($objectives as $index => $objective): ?>

        <div class="mb-3">

            <label class="form-label">

                الهدف <?= $index + 1 ?>

            </label>

            <textarea

                class="form-control"

                rows="2"

                name="objectives[]"><?= htmlspecialchars($objective) ?></textarea>

        </div>

        <?php endforeach; ?>

    </div>

</div>


<!-- ==================================================
النشاط الاستهلالي
================================================== -->

<div class="card shadow border-0 mb-4">

    <div class="card-header bg-warning">

        <h5 class="mb-0">

            <i class="bi bi-lightbulb-fill"></i>

            النشاط الاستهلالي

        </h5>

    </div>

    <div class="card-body">

        <div class="mb-3">

            <label class="form-label">

                النشاط

            </label>

            <textarea

                class="form-control"

                rows="3"

                name="warmup[title]"><?= htmlspecialchars($lessonData['warmup']['title'] ?? '') ?></textarea>

        </div>

        <div class="row">

            <div class="col-md-6 mb-3">

                <label class="form-label">

                    دور المعلم

                </label>

                <textarea

                    class="form-control"

                    rows="4"

                    name="warmup[teacher_role]"><?= htmlspecialchars($lessonData['warmup']['teacher_role'] ?? '') ?></textarea>

            </div>

            <div class="col-md-6 mb-3">

                <label class="form-label">

                    دور الطلبة

                </label>

                <textarea

                    class="form-control"

                    rows="4"

                    name="warmup[student_role]"><?= htmlspecialchars($lessonData['warmup']['student_role'] ?? '') ?></textarea>

            </div>

        </div>

        <div class="row">

            <div class="col-md-6 mb-3">

                <label class="form-label">

                    الوسائل

                </label>

                <input

                    type="text"

                    class="form-control"

                    name="warmup[resources]"

                    value="<?= htmlspecialchars($lessonData['warmup']['resources'] ?? '') ?>">

            </div>

            <div class="col-md-6 mb-3">

                <label class="form-label">

                    الزمن

                </label>

                <input

                    type="text"

                    class="form-control"

                    name="warmup[time]"

                    value="<?= htmlspecialchars($lessonData['warmup']['time'] ?? '') ?>">

            </div>

        </div>

    </div>

</div>


<!-- ==================================================
مقدمة الدرس
================================================== -->

<div class="card shadow border-0 mb-4">

    <div class="card-header bg-info text-white">

        <h5 class="mb-0">

            <i class="bi bi-play-circle-fill"></i>

            مقدمة الدرس

        </h5>

    </div>

    <div class="card-body">

        <textarea

            class="form-control"

            rows="6"

            name="introduction[content]"><?= htmlspecialchars($lessonData['introduction']['content'] ?? '') ?></textarea>

    </div>

</div>

<!-- ==================================================
الهدف الأول
================================================== -->

<div class="card shadow border-0 mb-4">

    <div class="card-header bg-primary text-white">

        <h5 class="mb-0">

            <i class="bi bi-1-circle-fill"></i>

            الهدف الأول

        </h5>

    </div>

    <div class="card-body">

        <div class="mb-3">

            <label class="form-label">

                الهدف

            </label>

            <textarea

                class="form-control"

                rows="3"

                name="objective1[goal]"><?= htmlspecialchars($lessonData['objective1']['goal'] ?? '') ?></textarea>

        </div>

        <div class="mb-3">

            <label class="form-label">

                استراتيجية التدريس

            </label>

            <textarea

                class="form-control"

                rows="3"

                name="objective1[strategy]"><?= htmlspecialchars($lessonData['objective1']['strategy'] ?? '') ?></textarea>

        </div>

        <div class="row">

            <div class="col-md-6">

                <div class="mb-3">

                    <label class="form-label">

                        النشاط الأول

                    </label>

                    <textarea

                        class="form-control"

                        rows="6"

                        name="objective1[activity1]"><?= htmlspecialchars($lessonData['objective1']['activity1'] ?? '') ?></textarea>

                </div>

            </div>

            <div class="col-md-6">

                <div class="mb-3">

                    <label class="form-label">

                        النشاط الثاني

                    </label>

                    <textarea

                        class="form-control"

                        rows="6"

                        name="objective1[activity2]"><?= htmlspecialchars($lessonData['objective1']['activity2'] ?? '') ?></textarea>

                </div>

            </div>

        </div>

        <div class="mb-3">

            <label class="form-label">

                التقويم

            </label>

            <textarea

                class="form-control"

                rows="4"

                name="objective1[assessment]"><?= htmlspecialchars($lessonData['objective1']['assessment'] ?? '') ?></textarea>

        </div>

    </div>

</div>

<!-- ==================================================
الهدف الثاني
================================================== -->

<div class="card shadow border-0 mb-4">

    <div class="card-header bg-success text-white">

        <h5 class="mb-0">

            <i class="bi bi-2-circle-fill"></i>

            الهدف الثاني

        </h5>

    </div>

    <div class="card-body">

        <div class="mb-3">

            <label class="form-label">

                الهدف

            </label>

            <textarea

                class="form-control"

                rows="3"

                name="objective2[goal]"><?= htmlspecialchars($lessonData['objective2']['goal'] ?? '') ?></textarea>

        </div>

        <div class="mb-3">

            <label class="form-label">

                استراتيجية التدريس

            </label>

            <textarea

                class="form-control"

                rows="3"

                name="objective2[strategy]"><?= htmlspecialchars($lessonData['objective2']['strategy'] ?? '') ?></textarea>

        </div>

        <div class="row">

            <div class="col-md-6">

                <div class="mb-3">

                    <label class="form-label">

                        النشاط الأول

                    </label>

                    <textarea

                        class="form-control"

                        rows="6"

                        name="objective2[activity1]"><?= htmlspecialchars($lessonData['objective2']['activity1'] ?? '') ?></textarea>

                </div>

            </div>

            <div class="col-md-6">

                <div class="mb-3">

                    <label class="form-label">

                        النشاط الثاني

                    </label>

                    <textarea

                        class="form-control"

                        rows="6"

                        name="objective2[activity2]"><?= htmlspecialchars($lessonData['objective2']['activity2'] ?? '') ?></textarea>

                </div>

            </div>

        </div>

        <div class="mb-3">

            <label class="form-label">

                التقويم

            </label>

            <textarea

                class="form-control"

                rows="4"

                name="objective2[assessment]"><?= htmlspecialchars($lessonData['objective2']['assessment'] ?? '') ?></textarea>

        </div>

    </div>

</div>


<!-- ==================================================
الخاتمة
================================================== -->

<div class="card shadow border-0 mb-4">

    <div class="card-header bg-dark text-white">

        <h5 class="mb-0">

            <i class="bi bi-check2-circle"></i>

            الخاتمة

        </h5>

    </div>

    <div class="card-body">

        <textarea

            class="form-control"

            rows="5"

            name="conclusion"><?= htmlspecialchars($lessonData['conclusion'] ?? '') ?></textarea>

    </div>

</div>


<!-- ==================================================
الواجب المنزلي
================================================== -->

<div class="card shadow border-0 mb-4">

    <div class="card-header bg-secondary text-white">

        <h5 class="mb-0">

            <i class="bi bi-house-check-fill"></i>

            الواجب المنزلي

        </h5>

    </div>

    <div class="card-body">

        <textarea

            class="form-control"

            rows="5"

            name="homework"><?= htmlspecialchars($lessonData['homework'] ?? '') ?></textarea>

    </div>

</div>


<!-- ==================================================
وسائل التعليم
================================================== -->

<div class="card shadow border-0 mb-4">

    <div class="card-header bg-dark text-white">

        <h5 class="mb-0">

            <i class="bi bi-easel2-fill"></i>

            وسائل التعليم

        </h5>

    </div>

    <div class="card-body">

        <textarea

            class="form-control"

            rows="5"

            name="resources"><?= htmlspecialchars(implode("\n", $lessonData['resources'] ?? [])); ?></textarea>

        <small class="text-muted">

            اكتب كل وسيلة في سطر مستقل.

        </small>

    </div>

</div>


<!-- ==================================================
مهارات القرن الحادي والعشرين
================================================== -->

<div class="card shadow border-0 mb-4">

    <div class="card-header bg-primary text-white">

        <h5 class="mb-0">

            <i class="bi bi-lightning-fill"></i>

            مهارات القرن الحادي والعشرين

        </h5>

    </div>

    <div class="card-body">

        <textarea

            class="form-control"

            rows="5"

            name="skills"><?= htmlspecialchars(implode("\n", $lessonData['skills'] ?? [])); ?></textarea>

        <small class="text-muted">

            كل مهارة في سطر مستقل.

        </small>

    </div>

</div>


<!-- ==================================================
القيم
================================================== -->

<div class="card shadow border-0 mb-4">

    <div class="card-header bg-success text-white">

        <h5 class="mb-0">

            <i class="bi bi-heart-fill"></i>

            القيم

        </h5>

    </div>

    <div class="card-body">

        <textarea

            class="form-control"

            rows="5"

            name="values"><?= htmlspecialchars(implode("\n", $lessonData['values'] ?? [])); ?></textarea>

        <small class="text-muted">

            كل قيمة في سطر مستقل.

        </small>

    </div>

</div>


<!-- ==================================================
التمايز
================================================== -->

<div class="card shadow border-0 mb-4">

    <div class="card-header bg-warning">

        <h5 class="mb-0">

            <i class="bi bi-people-fill"></i>

            التمايز

        </h5>

    </div>

    <div class="card-body">

        <div class="row">

            <div class="col-md-4">

                <label class="form-label">

                    الطلبة المتفوقون

                </label>

                <textarea

                    class="form-control"

                    rows="6"

                    name="differentiation[advanced]"><?= htmlspecialchars($lessonData['differentiation']['advanced'] ?? '') ?></textarea>

            </div>

            <div class="col-md-4">

                <label class="form-label">

                    الطلبة المتوسطون

                </label>

                <textarea

                    class="form-control"

                    rows="6"

                    name="differentiation[average]"><?= htmlspecialchars($lessonData['differentiation']['average'] ?? '') ?></textarea>

            </div>

            <div class="col-md-4">

                <label class="form-label">

                    الطلبة الذين يحتاجون دعماً

                </label>

                <textarea

                    class="form-control"

                    rows="6"

                    name="differentiation[support]"><?= htmlspecialchars($lessonData['differentiation']['support'] ?? '') ?></textarea>

            </div>

        </div>

    </div>

</div>

<!-- ==================================================
التقويم الختامي
================================================== -->

<div class="card shadow border-0 mb-4">

    <div class="card-header bg-danger text-white">

        <h5 class="mb-0">

            <i class="bi bi-clipboard-check-fill"></i>

            التقويم الختامي

        </h5>

    </div>

    <div class="card-body">

        <div class="row">

            <!-- الأسئلة الشفهية -->

            <div class="col-lg-6 mb-4">

                <label class="form-label fw-bold">

                    الأسئلة الشفهية

                </label>

                <textarea

                    class="form-control"

                    rows="8"

                    name="final_assessment[oral]"><?= htmlspecialchars(implode("\n", $lessonData['final_assessment']['oral'] ?? [])); ?></textarea>

                <small class="text-muted">

                    اكتب كل سؤال في سطر مستقل.

                </small>

            </div>

            <!-- الأسئلة الكتابية -->

            <div class="col-lg-6 mb-4">

                <label class="form-label fw-bold">

                    الأسئلة الكتابية

                </label>

                <textarea

                    class="form-control"

                    rows="8"

                    name="final_assessment[written]"><?= htmlspecialchars(implode("\n", $lessonData['final_assessment']['written'] ?? [])); ?></textarea>

                <small class="text-muted">

                    اكتب كل سؤال في سطر مستقل.

                </small>

            </div>

        </div>

        <div class="mb-3">

            <label class="form-label fw-bold">

                المهمة الأدائية

            </label>

            <textarea

                class="form-control"

                rows="5"

                name="final_assessment[performance_task]"><?= htmlspecialchars($lessonData['final_assessment']['performance_task'] ?? '') ?></textarea>

        </div>

    </div>

</div>


<!-- ==================================================
أزرار الحفظ
================================================== -->

<div class="card shadow border-0 mb-5">

    <div class="card-body">

        <div class="d-flex justify-content-between flex-wrap gap-2">

            <a

                href="view.php?id=<?= $lesson['id']; ?>"

                class="btn btn-secondary btn-lg">

                <i class="bi bi-arrow-right-circle"></i>

                رجوع

            </a>

            <div>

                <button

                    type="reset"

                    class="btn btn-warning btn-lg">

                    <i class="bi bi-arrow-counterclockwise"></i>

                    إعادة تعيين

                </button>

                <button

                    type="submit"

                    class="btn btn-success btn-lg">

                    <i class="bi bi-save-fill"></i>

                    حفظ التعديلات

                </button>

            </div>

        </div>

    </div>

</div>

</form>

</div>

</div>

</div>

<?php include '../../app/views/layouts/footer.php'; ?>





