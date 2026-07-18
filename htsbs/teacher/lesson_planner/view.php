<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!empty($_SESSION['success_message'])) {

    $successMessage = $_SESSION['success_message'];

    unset($_SESSION['success_message']);

} else {

    $successMessage = '';

}

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../app/models/Notification.php';
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

$notificationModel = new Notification();

$count = $notificationModel->unreadCount(
    $_SESSION['user_id']
);

/*
==========================================
رقم التحضير
==========================================
*/

$id = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;

if ($id <= 0) {

    die("رقم التحضير غير صحيح.");

}

/*
==========================================
جلب التحضير
==========================================
*/

$stmt = $db->prepare("

SELECT

lp.*,

c.course_name,

cl.class_name,

u.full_name

FROM lesson_plans lp

LEFT JOIN courses c
ON c.id = lp.subject_id

LEFT JOIN classes cl
ON cl.id = lp.class_id

LEFT JOIN users u
ON u.id = lp.teacher_id

WHERE

lp.id = ?

AND

lp.teacher_id = ?

LIMIT 1

");

$stmt->execute([

    $id,

    $_SESSION['user_id']

]);

$lesson = $stmt->fetch(PDO::FETCH_ASSOC);

//echo "<pre>";

//var_dump($lesson['lesson_plan_json']);

//echo "</pre>";

//exit;

if (!$lesson) {

    die("التحضير غير موجود.");

}


if (!$lesson) {

    die("التحضير غير موجود.");

}

/*
==================================================
Decode Lesson JSON
==================================================
*/

$lessonJson = [];

if (!empty($lesson['lesson_plan_json'])) {

    $lessonJson = json_decode(

        $lesson['lesson_plan_json'],

        true

    );

    if (json_last_error() !== JSON_ERROR_NONE) {

        $lessonJson = [];

    }

}

/*
==========================================
قيم افتراضية
==========================================
*/

$lesson['course_name'] =
$lesson['course_name'] ?? '';

$lesson['class_name'] =
$lesson['class_name'] ?? '';

$lesson['unit_name'] =
$lesson['unit_name'] ?? '';

$lesson['lesson_title'] =
$lesson['lesson_title'] ?? '';

$lesson['lesson_description'] =
$lesson['lesson_description'] ?? '';

$lesson['learning_outcomes'] =
$lesson['learning_outcomes'] ?? '';

$lesson['keywords'] =
$lesson['keywords'] ?? '';

$lesson['resources'] =
$lesson['resources'] ?? '';

$lesson['student_level'] =
$lesson['student_level'] ?? '';

$lesson['lesson_plan'] =
$lesson['lesson_plan'] ?? '';





include '../../app/views/layouts/header.php';

?>
<?php if ($successMessage): ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>

document.addEventListener('DOMContentLoaded', function () {

    Swal.fire({

        icon: 'success',

        title: 'تم بنجاح',

        text: <?= json_encode($successMessage) ?>,

        confirmButtonColor: '#198754',

        confirmButtonText: 'موافق'

    });

});

</script>

<?php endif; ?>


<div class="container-fluid">

<div class="row">

<?php include '../../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">

<div class="card shadow border-0 mb-4">

<div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">

<h4 class="mb-0">

<i class="bi bi-journal-richtext"></i>

عرض التحضير الدراسي

</h4>

<span class="badge bg-light text-dark">

رقم التحضير #

<?= $lesson['id']; ?>

</span>

</div>

<div class="card-body">
    
<!-- ==========================================
معلومات الدرس
========================================== -->

<div class="row mb-4">

    <div class="col-lg-8">

        <div class="card shadow-sm border-0 h-100">

            <div class="card-header bg-success text-white">

                <i class="bi bi-book"></i>

                معلومات الدرس

            </div>

            <div class="card-body">

                <div class="row">

                    <div class="col-md-6 mb-3">

                        <label class="fw-bold">

                            المادة

                        </label>

                        <div>

                            <?= htmlspecialchars($lesson['course_name']); ?>

                        </div>

                    </div>

                    <div class="col-md-6 mb-3">

                        <label class="fw-bold">

                            الصف

                        </label>

                        <div>

                            <?= htmlspecialchars($lesson['class_name']); ?>

                        </div>

                    </div>

                    <div class="col-md-6 mb-3">

                        <label class="fw-bold">

                            الوحدة

                        </label>

                        <div>

                            <?= htmlspecialchars($lesson['unit_name']); ?>

                        </div>

                    </div>

                    <div class="col-md-6 mb-3">

                        <label class="fw-bold">

                            عنوان الدرس

                        </label>

                        <div>

                            <?= htmlspecialchars($lesson['lesson_title']); ?>

                        </div>

                    </div>

                    <div class="col-md-6 mb-3">

                        <label class="fw-bold">

                            زمن الحصة

                        </label>

                        <div>

                            <?= $lesson['lesson_duration']; ?>

                            دقيقة

                        </div>

                    </div>

                    <div class="col-md-6 mb-3">

                        <label class="fw-bold">

                            مستوى الطلبة

                        </label>

                        <div>

                            <?= htmlspecialchars($lesson['student_level']); ?>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <!-- معلومات الذكاء الاصطناعي -->

    <div class="col-lg-4">

        <div class="card shadow-sm border-0 h-100">

            <div class="card-header bg-primary text-white">

                <i class="bi bi-cpu-fill"></i>

                معلومات الذكاء الاصطناعي

            </div>

            <div class="card-body">

                <table class="table table-borderless mb-0">

                    <tr>

                        <th width="40%">

                            النموذج

                        </th>

                        <td>

                            <?= htmlspecialchars($lesson['ai_model']); ?>

                        </td>

                    </tr>

                    <tr>

                        <th>

                            الحالة

                        </th>

                        <td>

                            <?php

                            if($lesson['status']=="draft"){

                                echo '<span class="badge bg-warning">مسودة</span>';

                            }elseif($lesson['status']=="published"){

                                echo '<span class="badge bg-success">منشور</span>';

                            }else{

                                echo '<span class="badge bg-secondary">مؤرشف</span>';

                            }

                            ?>

                        </td>

                    </tr>

                    <tr>

                        <th>

                            الإصدار

                        </th>

                        <td>

                            <?= $lesson['version_no']; ?>

                        </td>

                    </tr>

                    <tr>

                        <th>

                            المفضلة

                        </th>

                        <td>

                            <?= $lesson['is_favorite'] ? '⭐ نعم' : 'لا'; ?>

                        </td>

                    </tr>

                    <tr>

                        <th>

                            عدد Tokens

                        </th>

                        <td>

                            <?= number_format($lesson['tokens_used']); ?>

                        </td>

                    </tr>

                    <tr>

                        <th>

                            زمن التوليد

                        </th>

                        <td>

                            <?= $lesson['generation_time']; ?>

                            ثانية

                        </td>

                    </tr>

                    <tr>

                        <th>

                            أنشأه

                        </th>

                        <td>

                            <?= htmlspecialchars($lesson['full_name']); ?>

                        </td>

                    </tr>

                    <tr>

                        <th>

                            تاريخ الإنشاء

                        </th>

                        <td>

                            <?= date('Y-m-d H:i',strtotime($lesson['created_at'])); ?>

                        </td>

                    </tr>

                </table>

            </div>

        </div>

    </div>

</div>

<!-- ==========================================
الوصف
========================================== -->

<div class="card shadow border-0 mb-4">

    <div class="card-header bg-info text-white">

        <i class="bi bi-card-text"></i>

        وصف الدرس

    </div>

    <div class="card-body">

        <?= nl2br(htmlspecialchars($lesson['lesson_description'])); ?>

    </div>

</div>

<!-- ==========================================
نواتج التعلم
========================================== -->

<div class="card shadow border-0 mb-4">

    <div class="card-header bg-warning">

        <i class="bi bi-bullseye"></i>

        نواتج التعلم

    </div>

    <div class="card-body">

        <?= nl2br(htmlspecialchars($lesson['learning_outcomes'])); ?>

    </div>

</div>

<!-- ==========================================
الكلمات المفتاحية
========================================== -->

<div class="card shadow border-0 mb-4">

    <div class="card-header bg-secondary text-white">

        <i class="bi bi-tags"></i>

        الكلمات المفتاحية

    </div>

    <div class="card-body">

        <?= nl2br(htmlspecialchars($lesson['keywords'])); ?>

    </div>

</div>

<!-- ==========================================
وسائل التعليم
========================================== -->

<div class="card shadow border-0 mb-4">

    <div class="card-header bg-dark text-white">

        <i class="bi bi-easel2"></i>

        وسائل التعليم

    </div>

    <div class="card-body">

        <?= nl2br(htmlspecialchars($lesson['resources'])); ?>

    </div>

</div>

<!-- ==========================================
التحضير الذي أنشأه الذكاء الاصطناعي
========================================== -->

<div class="card shadow border-0 mb-4">

    <div class="card-header bg-primary text-white">

        <i class="bi bi-stars"></i>

        التحضير الدراسي

    </div>

    <div class="card-body lesson-plan-body">

       <?php

if (!empty($lessonJson)) {

    echo LessonPlanRenderer::render(

        $lessonJson,

        [

            'mode' => 'view'

        ]

    );

} else {

?>

<div class="alert alert-warning">

    <h5>

        لا يوجد تحضير محفوظ بصيغة JSON

    </h5>

    <hr>

    <pre style="white-space: pre-wrap;">

<?= htmlspecialchars($lesson['lesson_plan']) ?>

    </pre>

</div>

<?php } ?>

    </div>

</div>

<!-- ==========================================
ملاحظات المعلم
========================================== -->

<div class="card shadow border-0 mb-4">

    <div class="card-header bg-success text-white">

        <i class="bi bi-pencil-square"></i>

        ملاحظات المعلم

    </div>

    <div class="card-body">

        <?php if(empty($lesson['notes'])): ?>

            <div class="text-muted">

                لا توجد ملاحظات.

            </div>

        <?php else: ?>

            <?= nl2br(htmlspecialchars($lesson['notes'])); ?>

        <?php endif; ?>

    </div>

</div>

<!-- ==========================================
Prompt المستخدم
========================================== -->

<div class="card shadow border-0 mb-4">

    <div class="card-header bg-dark text-white">

        <i class="bi bi-cpu"></i>

        تعليمات الذكاء الاصطناعي (Prompt)

    </div>

    <div class="card-body">

        <?php if(empty($lesson['ai_prompt'])): ?>

            <span class="text-muted">

                لم تتم إضافة تعليمات إضافية.

            </span>

        <?php else: ?>

            <?= nl2br(htmlspecialchars($lesson['ai_prompt'])); ?>

        <?php endif; ?>

    </div>

</div>

<!-- ==========================================
إحصائيات التحضير
========================================== -->

<div class="row mb-4">

    <div class="col-md-3">

        <div class="card dashboard-stat-card text-center shadow">

            <div class="card-body">

                <i class="bi bi-lightning-charge-fill
                          text-warning"
                   style="font-size:40px"></i>

                <h3 class="mt-3">

                    <?= $lesson['generation_time']; ?>

                </h3>

                <p>

                    ثانية

                </p>

            </div>

        </div>

    </div>

    <div class="col-md-3">

        <div class="card dashboard-stat-card text-center shadow">

            <div class="card-body">

                <i class="bi bi-cpu-fill
                          text-primary"
                   style="font-size:40px"></i>

                <h3 class="mt-3">

                    <?= number_format($lesson['tokens_used']); ?>

                </h3>

                <p>

                    Tokens

                </p>

            </div>

        </div>

    </div>

    <div class="col-md-3">

        <div class="card dashboard-stat-card text-center shadow">

            <div class="card-body">

                <i class="bi bi-file-earmark-pdf-fill
                          text-danger"
                   style="font-size:40px"></i>

                <h3 class="mt-3">

                    <?= $lesson['exported_pdf']; ?>

                </h3>

                <p>

                    مرات تصدير PDF

                </p>

            </div>

        </div>

    </div>

    <div class="col-md-3">

        <div class="card dashboard-stat-card text-center shadow">

            <div class="card-body">

                <i class="bi bi-printer-fill
                          text-success"
                   style="font-size:40px"></i>

                <h3 class="mt-3">

                    <?= $lesson['printed_count']; ?>

                </h3>

                <p>

                    مرات الطباعة

                </p>

            </div>

        </div>

    </div>

</div>

<!-- ==========================================
شريط العمليات
========================================== -->

<div class="card shadow border-0 mb-4">

    <div class="card-header bg-secondary text-white">

        <i class="bi bi-tools"></i>

        العمليات

    </div>

    <div class="card-body">

        <div class="d-flex flex-wrap gap-2 justify-content-center">

            <!-- تعديل -->

            <a
                href="edit.php?id=<?= $lesson['id']; ?>"
                class="btn btn-warning">

                <i class="bi bi-pencil-square"></i>

                تعديل

            </a>

            <!-- إعادة التوليد -->

            <a
                href="generate.php?id=<?= $lesson['id']; ?>&regenerate=1"
                class="btn btn-info text-white">

                <i class="bi bi-stars"></i>

                إعادة التوليد بالذكاء الاصطناعي

            </a>

            <!-- PDF -->

            <a
                href="export_pdf.php?id=<?= $lesson['id']; ?>"
                target="_blank"
                class="btn btn-danger">

                <i class="bi bi-file-earmark-pdf-fill"></i>

                PDF

            </a>

            <!-- Word -->

            <a
                href="export_word.php?id=<?= $lesson['id']; ?>"
                class="btn btn-primary">

                <i class="bi bi-file-earmark-word-fill"></i>

                Word

            </a>

            <!-- Print -->

            <a
                href="print.php?id=<?= $lesson['id']; ?>"
                target="_blank"
                class="btn btn-success">

                <i class="bi bi-printer-fill"></i>

                طباعة

            </a>

            <!-- Favorite -->

            <?php if($lesson['is_favorite']){ ?>

                <a
                    href="favorite.php?id=<?= $lesson['id']; ?>&action=remove"
                    class="btn btn-outline-warning">

                    <i class="bi bi-star-fill"></i>

                    إزالة من المفضلة

                </a>

            <?php }else{ ?>

                <a
                    href="favorite.php?id=<?= $lesson['id']; ?>
                    class="btn btn-warning">

                    <i class="bi bi-star"></i>

                    إضافة للمفضلة

                </a>

            <?php } ?>

            <!-- نسخ -->

            <button
                class="btn btn-dark"
                onclick="copyLesson()">

                <i class="bi bi-clipboard"></i>

                نسخ التحضير

            </button>

            <!-- حذف -->

            <button

                class="btn btn-outline-danger"

                data-bs-toggle="modal"

                data-bs-target="#deleteModal">

                <i class="bi bi-trash-fill"></i>

                حذف

            </button>

        </div>

    </div>

</div>

<!-- ==========================================
Delete Modal
========================================== -->

<div
class="modal fade"

id="deleteModal"

tabindex="-1">

<div class="modal-dialog">

<div class="modal-content">

<div class="modal-header bg-danger text-white">

<h5 class="modal-title">

تأكيد حذف التحضير

</h5>

<button

type="button"

class="btn-close btn-close-white"

data-bs-dismiss="modal">

</button>

</div>

<div class="modal-body">

هل أنت متأكد من حذف التحضير

<br><br>

<strong>

<?= htmlspecialchars($lesson['lesson_title']); ?>

</strong>

؟

</div>

<div class="modal-footer">

<button

class="btn btn-secondary"

data-bs-dismiss="modal">

إلغاء

</button>

<a

href="delete.php?id=<?= $lesson['id']; ?>"

class="btn btn-danger">

حذف نهائياً

</a>

</div>

</div>

</div>

</div>

<!-- ==========================================
Version Information
========================================== -->

<div class="row">

    <!-- معلومات التحضير -->

    <div class="col-lg-6 mb-4">

        <div class="card shadow border-0 h-100">

            <div class="card-header bg-primary text-white">

                <i class="bi bi-info-circle-fill"></i>

                معلومات التحضير

            </div>

            <div class="card-body">

                <table class="table table-striped table-hover align-middle mb-0">

                    <tr>

                        <th width="40%">

                            رقم التحضير

                        </th>

                        <td>

                            #<?= $lesson['id']; ?>

                        </td>

                    </tr>

                    <tr>

                        <th>

                            الإصدار الحالي

                        </th>

                        <td>

                            <?= $lesson['version_no']; ?>

                        </td>

                    </tr>

                    <tr>

                        <th>

                            الحالة

                        </th>

                        <td>

                            <?php

                            switch($lesson['status']){

                                case 'published':

                                    echo '<span class="badge bg-success">منشور</span>';

                                    break;

                                case 'archived':

                                    echo '<span class="badge bg-secondary">مؤرشف</span>';

                                    break;

                                default:

                                    echo '<span class="badge bg-warning text-dark">مسودة</span>';

                            }

                            ?>

                        </td>

                    </tr>

                    <tr>

                        <th>

                            المفضلة

                        </th>

                        <td>

                            <?= $lesson['is_favorite'] ? '⭐ نعم' : 'لا'; ?>

                        </td>

                    </tr>

                    <tr>

                        <th>

                            نموذج الذكاء الاصطناعي

                        </th>

                        <td>

                            <?= htmlspecialchars($lesson['ai_model']); ?>

                        </td>

                    </tr>

                    <tr>

                        <th>

                            زمن إنشاء التحضير

                        </th>

                        <td>

                            <?= $lesson['generation_time']; ?>

                            ثانية

                        </td>

                    </tr>

                    <tr>

                        <th>

                            عدد Tokens

                        </th>

                        <td>

                            <?= number_format($lesson['tokens_used']); ?>

                        </td>

                    </tr>

                </table>

            </div>

        </div>

    </div>

    <!-- معلومات النظام -->

    <div class="col-lg-6 mb-4">

        <div class="card shadow border-0 h-100">

            <div class="card-header bg-success text-white">

                <i class="bi bi-clock-history"></i>

                معلومات النظام

            </div>

            <div class="card-body">

                <table class="table table-striped table-hover align-middle mb-0">

                    <tr>

                        <th width="40%">

                            المعلم

                        </th>

                        <td>

                            <?= htmlspecialchars($lesson['full_name']); ?>

                        </td>

                    </tr>

                    <tr>

                        <th>

                            تاريخ الإنشاء

                        </th>

                        <td>

                            <?= date('Y-m-d h:i A',strtotime($lesson['created_at'])); ?>

                        </td>

                    </tr>

                    <tr>

                        <th>

                            آخر تعديل

                        </th>

                        <td>

                            <?= date('Y-m-d h:i A',strtotime($lesson['updated_at'])); ?>

                        </td>

                    </tr>

                    <tr>

                        <th>

                            مرات تصدير PDF

                        </th>

                        <td>

                            <?= $lesson['exported_pdf']; ?>

                        </td>

                    </tr>

                    <tr>

                        <th>

                            مرات تصدير Word

                        </th>

                        <td>

                            <?= $lesson['exported_word']; ?>

                        </td>

                    </tr>

                    <tr>

                        <th>

                            مرات الطباعة

                        </th>

                        <td>

                            <?= $lesson['printed_count']; ?>

                        </td>

                    </tr>

                </table>

            </div>

        </div>

    </div>

</div>

<!-- ==========================================
Version Timeline
========================================== -->

<div class="card shadow border-0 mb-4">

    <div class="card-header bg-dark text-white">

        <i class="bi bi-diagram-3-fill"></i>

        سجل الإصدارات

    </div>

    <div class="card-body">

        <div class="timeline">

            <div class="mb-4">

                <h6 class="fw-bold">

                    الإصدار

                    <?= $lesson['version_no']; ?>

                </h6>

                <p class="mb-1">

                    تم إنشاء التحضير بواسطة الذكاء الاصطناعي.

                </p>

                <small class="text-muted">

                    <?= date(
                        'd/m/Y h:i A',
                        strtotime($lesson['created_at'])
                    ); ?>

                </small>

            </div>

        </div>

    </div>

</div>

<!-- ==========================================
JavaScript
========================================== -->

<script>

function copyLesson() {

    const lesson = document.querySelector('.lesson-plan-body');

    if (!lesson) {

        return;

    }

    navigator.clipboard.writeText(

        lesson.innerText

    ).then(function(){

        Swal.fire({

            icon:'success',

            title:'تم النسخ',

            text:'تم نسخ التحضير إلى الحافظة.',

            timer:1800,

            showConfirmButton:false

        });

    });

}

</script>

<?php if(isset($_SESSION['success'])): ?>

<script>

Swal.fire({

    icon:'success',

    title:'نجاح',

    text:'<?= $_SESSION['success']; ?>',

    timer:2000,

    showConfirmButton:false

});

</script>

<?php unset($_SESSION['success']); ?>

<?php endif; ?>


<?php if(isset($_SESSION['error'])): ?>

<script>

Swal.fire({

    icon:'error',

    title:'خطأ',

    html:'<?= $_SESSION['error']; ?>'

});

</script>

<?php unset($_SESSION['error']); ?>

<?php endif; ?>


<style>

.lesson-plan-body{

    font-size:16px;

    line-height:2.1;

    white-space:pre-wrap;

    direction:rtl;

    text-align:right;

}

.lesson-plan-body h1,
.lesson-plan-body h2,
.lesson-plan-body h3,
.lesson-plan-body h4{

    margin-top:25px;

    margin-bottom:15px;

    color:#0d6efd;

    font-weight:bold;

}

.lesson-plan-body ul{

    padding-right:25px;

}

.lesson-plan-body li{

    margin-bottom:8px;

}

.card{

    border-radius:15px;

}

.card-header{

    font-weight:bold;

    font-size:18px;

}

.table th{

    width:35%;

    background:#f8f9fa;

}

.btn{

    border-radius:10px;

}

.timeline{

    border-right:4px solid #0d6efd;

    padding-right:20px;

}

.timeline h6{

    color:#0d6efd;

    font-weight:bold;

}

@media print{

    .navbar,

    .sidebar-fixed,

    .teacher-sidebar,

    .btn,

    .card-header.bg-secondary,

    .modal{

        display:none !important;

    }

    .main-content{

        margin:0 !important;

        width:100% !important;

        padding:0 !important;

    }

    .card{

        border:none !important;

        box-shadow:none !important;

    }

    body{

        background:#fff;

    }

}

</style>

<?php

include '../../app/views/layouts/footer.php';

?>


