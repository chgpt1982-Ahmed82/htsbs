<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

/*
==================================================
Config
==================================================
*/

require_once '../../config/config.php';
require_once '../../config/database.php';

/*
==================================================
Models
==================================================
*/

require_once '../../app/models/LessonPlanner.php';

/*
==================================================
Helpers
==================================================
*/

require_once '../../app/helpers/LessonPlanRenderer.php';

/*
==================================================
Authentication
==================================================
*/

if (!isset($_SESSION['user_id'])) {

    header("Location: " . BASE_URL . "/login.php");

    exit;

}

if ($_SESSION['role_id'] != 2) {

    die("Access Denied");

}

/*
==================================================
Database
==================================================
*/

$db = (new Database())->connect();

$lessonPlanner = new LessonPlanner();

/*
==================================================
Lesson ID
==================================================
*/

$id = isset($_GET['id'])

    ?

    (int)$_GET['id']

    :

    0;

if ($id <= 0) {

    die("رقم التحضير غير صحيح.");

}

/*
==================================================
Load Lesson
==================================================
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

WHERE lp.id = ? AND lp.teacher_id = ?

LIMIT 1

");

$stmt->execute([$id, (int)$_SESSION['user_id']]);

$lesson = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$lesson) {

    require_once '../../core/Logger.php';

    Logger::log(
        'lesson_planner',
        'print_denied',
        "محاولة طباعة تحضير لا يملكه المعلم (lesson_id=$id)",
        null, null, 'danger'
    );

    die("التحضير غير موجود أو لا تملك صلاحية طباعته.");

}

/*
==================================================
Decode JSON
==================================================
*/

$lessonJson = [];

if (!empty($lesson['lesson_plan_json'])) {

    $lessonJson = json_decode(

        $lesson['lesson_plan_json'],

        true

    );

    if (!is_array($lessonJson)) {

        $lessonJson = [];

    }

}

/*
==================================================
Increase Print Counter
==================================================
*/

$stmt = $db->prepare("

UPDATE lesson_plans

SET

printed_count = printed_count + 1

WHERE id = ?

");

$stmt->execute([$id]);

$lesson['printed_count']++;

require_once '../../core/Logger.php';

Logger::log(
    'lesson_planner',
    'print_plan',
    "طباعة تحضير (id=$id) - المرة رقم ({$lesson['printed_count']})",
    'lesson_plan',
    $id,
    'info'
);
/*
==================================================
School Information
==================================================
*/

$schoolName = "مدرسة مدينة حمد الثانوية للبنين";

$ministry = "وزارة التربية والتعليم";

$academicYear = date('Y');

/*
==================================================
Date
==================================================
*/

$printDate = date("Y-m-d");

$printTime = date("H:i");

/*
==================================================
Page Title
==================================================
*/

$pageTitle ="طباعة التحضير - ".$lesson['lesson_title'];

?>


<!DOCTYPE html>

<html lang="ar" dir="rtl">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0">

<title>

<?= htmlspecialchars($pageTitle) ?>

</title>

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css"
rel="stylesheet">

<link
href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap"
rel="stylesheet">

<link
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
rel="stylesheet">

<style>

body{

    font-family:'Cairo',sans-serif;

    background:#f5f7fb;

    color:#222;

    margin:0;

    padding:25px;

}

/*==========================================
Header
==========================================*/

.print-header{

    background:#fff;

    border-radius:15px;

    padding:25px;

    box-shadow:0 3px 10px rgba(0,0,0,.08);

    margin-bottom:25px;

}

.logo{

    width:80px;

    height:80px;

    object-fit:contain;

}

.school-title{

    font-size:28px;

    font-weight:800;

    color:#0d6efd;

}

.sub-title{

    color:#666;

    font-size:15px;

}

.lesson-title{

    margin-top:15px;

    font-size:26px;

    font-weight:700;

}

.info-table{

    margin-top:25px;

}

.info-table td{

    padding:8px 12px;

}

.info-label{

    width:170px;

    font-weight:bold;

    color:#0d6efd;

}

/*==========================================
Buttons
==========================================*/

.no-print{

    margin-bottom:20px;

}

.btn{

    min-width:170px;

}

/*==========================================
Cards
==========================================*/

.card{

    border:none;

    border-radius:14px;

    overflow:hidden;

    margin-bottom:20px;

    box-shadow:0 2px 8px rgba(0,0,0,.08);

}

.card-header{

    font-weight:700;

    font-size:18px;

}

/*==========================================
Footer
==========================================*/

.footer{

    margin-top:35px;

    text-align:center;

    color:#666;

    font-size:14px;

}

/*==========================================
Print
==========================================*/

@page{

    size:A4;

    margin:15mm;

}

@media print{

    body{

        background:#fff;

        padding:0;

    }

    .no-print{

        display:none !important;

    }

    .card{

        page-break-inside:avoid;

        break-inside:avoid;

        box-shadow:none;

        border:1px solid #ddd;

    }

    .print-header{

        box-shadow:none;

        border:1px solid #ccc;

    }

    .footer{

        position:fixed;

        bottom:0;

        width:100%;

    }

}

</style>

</head>

<body>

<div class="container-fluid">

<div class="row">

<div class="col-12">

<!--=========================================
Buttons
==========================================-->

<div class="text-center no-print">

<button

class="btn btn-primary"

onclick="window.print()">

<i class="bi bi-printer-fill"></i>

طباعة

</button>

<a

href="view.php?id=<?= $lesson['id']; ?>"

class="btn btn-secondary">

<i class="bi bi-arrow-right-circle"></i>

رجوع

</a>

</div>

<!--=========================================
Header
==========================================-->

<div class="print-header">

<div class="row align-items-center">

<div class="col-2 text-center">

<img

src="../../assets/images/moe.png"

class="logo"

alt="وزارة التربية">

</div>

<div class="col-8 text-center">

<div class="school-title">

<?= htmlspecialchars($schoolName) ?>

</div>

<div class="sub-title">

<?= htmlspecialchars($ministry) ?>

</div>

<div class="lesson-title">

تحضير درس باستخدام الذكاء الاصطناعي

</div>

</div>

<div class="col-2 text-center">

<img

src="../../assets/images/school.png"

class="logo"

alt="School">

</div>

</div>

<hr>

<table class="table table-borderless info-table">

<tr>

<td class="info-label">

المعلم

</td>

<td>

<?= htmlspecialchars($lesson['full_name']) ?>

</td>

<td class="info-label">

المادة

</td>

<td>

<?= htmlspecialchars($lesson['course_name']) ?>

</td>

</tr>

<tr>

<td class="info-label">

الصف

</td>

<td>

<?= htmlspecialchars($lesson['class_name']) ?>

</td>

<td class="info-label">

عنوان الدرس

</td>

<td>

<?= htmlspecialchars($lesson['lesson_title']) ?>

</td>

</tr>

<tr>

<td class="info-label">

الوحدة

</td>

<td>

<?= htmlspecialchars($lesson['unit_name']) ?>

</td>

<td class="info-label">

تاريخ الطباعة

</td>

<td>

<?= $printDate ?>

&nbsp;

<?= $printTime ?>

</td>

</tr>

</table>

</div>

<!-- =========================================
Lesson Content
========================================= -->

<div class="lesson-content">

<?php

if (!empty($lessonJson)) {

    echo LessonPlanRenderer::render($lessonJson,['mode'=>'print']);

} else {

?>

<div class="alert alert-warning">

    <h5>

        لا يوجد تحضير محفوظ بصيغة JSON

    </h5>

    <pre style="white-space:pre-wrap">

<?= htmlspecialchars($lesson['lesson_plan']) ?>

    </pre>

</div>

<?php

}

?>

</div>

<!-- =========================================
Statistics
========================================= -->

<div class="card">

<div class="card-header bg-secondary text-white">

<i class="bi bi-bar-chart-fill"></i>

إحصائيات التحضير

</div>

<div class="card-body">

<div class="row text-center">

<div class="col-md-3">

<h4>

<?= (int)$lesson['version_no'] ?>

</h4>

<div>

رقم الإصدار

</div>

</div>

<div class="col-md-3">

<h4>

<?= (int)$lesson['tokens_used'] ?>

</h4>

<div>

Tokens

</div>

</div>

<div class="col-md-3">

<h4>

<?= (int)$lesson['generation_time'] ?>

</h4>

<div>

ثانية

</div>

</div>

<div class="col-md-3">

<h4>

<?= (int)$lesson['printed_count'] ?>

</h4>

<div>

مرات الطباعة

</div>

</div>

</div>

</div>

</div>


<!-- =========================================
Teacher Notes
========================================= -->

<?php if(!empty($lesson['notes'])): ?>

<div class="card">

    <div class="card-header bg-warning">

        <i class="bi bi-journal-text"></i>

        ملاحظات المعلم

    </div>

    <div class="card-body">

        <?= nl2br(htmlspecialchars($lesson['notes'])) ?>

    </div>

</div>

<?php endif; ?>


<!-- =========================================
AI Information
========================================= -->

<div class="card">

<div class="card-header bg-info text-white">

<i class="bi bi-cpu-fill"></i>

معلومات إنشاء التحضير

</div>

<div class="card-body">

<div class="row">

<div class="col-md-3">

<strong>النموذج</strong>

<br>

<?= htmlspecialchars($lesson['ai_model']) ?>

</div>

<div class="col-md-3">

<strong>الإصدار</strong>

<br>

<?= (int)$lesson['version_no'] ?>

</div>

<div class="col-md-3">

<strong>Tokens</strong>

<br>

<?= (int)$lesson['tokens_used'] ?>

</div>

<div class="col-md-3">

<strong>مدة الإنشاء</strong>

<br>

<?= (int)$lesson['generation_time'] ?>

 ثانية

</div>

</div>

</div>

</div>


<!-- =========================================
Signatures
========================================= -->

<div class="card">

<div class="card-header bg-dark text-white">

<i class="bi bi-pen-fill"></i>

الاعتماد

</div>

<div class="card-body">

<div class="row mt-5">

<div class="col-md-4 text-center">

____________________

<br><br>

المعلم

</div>

<div class="col-md-4 text-center">

____________________

<br><br>

رئيس القسم

</div>

<div class="col-md-4 text-center">

____________________

<br><br>

مدير المدرسة

</div>

</div>

</div>

</div>

<!-- =========================================
Footer
========================================= -->

<div class="footer">

<hr>

<div class="row">

<div class="col-4 text-start">

تم الإنشاء بواسطة

<strong>AI Lesson Planner</strong>

</div>

<div class="col-4 text-center">

<?= htmlspecialchars($schoolName) ?>

</div>

<div class="col-4 text-end">

<?= $printDate ?>

&nbsp;

<?= $printTime ?>

</div>

</div>

</div>

</div>

</div>

</div>

<!-- =========================================
Print Styles
========================================= -->

<style media="print">

html,
body{

    width:210mm;

    min-height:297mm;

    background:#fff !important;

    color:#000;

    font-size:13px;

}

.no-print{

    display:none !important;

}

.card{

    page-break-inside:avoid;

    break-inside:avoid;

    margin-bottom:18px;

    border:1px solid #999 !important;

    box-shadow:none !important;

}

.card-header{

    background:#e9ecef !important;

    color:#000 !important;

    -webkit-print-color-adjust:exact;

    print-color-adjust:exact;

}

.table{

    margin-bottom:0;

}

.print-header{

    page-break-after:avoid;

    border:1px solid #999;

}

.logo{

    width:70px;

    height:70px;

}

.footer{

    position:fixed;

    left:0;

    right:0;

    bottom:0;

    border-top:1px solid #999;

    background:#fff;

    padding-top:8px;

    font-size:11px;

}

h1,
h2,
h3,
h4,
h5{

    page-break-after:avoid;

}

pre{

    white-space:pre-wrap;

    word-break:break-word;

}

</style>

<script>

window.onafterprint=function(){

    console.log("Lesson Printed");

};

</script>



