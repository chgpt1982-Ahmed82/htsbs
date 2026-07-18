<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once '../config/config.php';
require_once '../app/models/StudentDashboard.php';

if(
    !isset($_SESSION['user_id']) ||
    $_SESSION['role_id'] != 3
){
    header("Location: ".BASE_URL."/login.php");
    exit;
}

$model = new StudentDashboard();

$studentInfo =
$model->getStudentInfo($_SESSION['user_id']);

$totalLessons =
$model->countLessons($_SESSION['user_id']);

$totalActivities =
$model->countActivities($_SESSION['user_id']);

$totalQuizzes =
$model->countQuizzes($_SESSION['user_id']);

$totalNotifications =
$model->countNotifications($_SESSION['user_id']);

$recentLessons =
$model->recentLessons($_SESSION['user_id']);

$notifications =
$model->recentNotifications($_SESSION['user_id']);

include '../app/views/layouts/header.php';

?>

<div class="container-fluid px-0">

<div class="row flex-lg-row-reverse g-0">

<?php include '../app/views/layouts/student_sidebar.php'; ?>
<div class="main-content">


<!-- Welcome Card -->

<div class="card shadow-lg border-0 mb-4">

<div class="card-body">

<div class="row align-items-center">

<div class="col-md-2 text-center">

<?php if(!empty($studentInfo['profile_image'])): ?>

<img
src="<?= BASE_URL ?>/uploads/profiles/<?= $studentInfo['profile_image']; ?>"
class="rounded-circle"
width="100">

<?php else: ?>

<i class="bi bi-person-circle text-primary"
style="font-size:80px;"></i>

<?php endif; ?>

</div>

<div class="col-md-10">

<h3 class="fw-bold">

مرحباً <?= htmlspecialchars($studentInfo['full_name']); ?>

</h3>

<p class="text-muted mb-1">

نتمنى لك يوماً دراسياً موفقاً

</p>

<div class="row">

<div class="col-md-4">

<strong>الرقم الأكاديمي:</strong>

<?= htmlspecialchars($studentInfo['student_number']); ?>

</div>

<div class="col-md-4">

<strong>الصف:</strong>

<?= htmlspecialchars($studentInfo['class_name']); ?>

</div>

<div class="col-md-4">

<strong>التاريخ:</strong>

<?= date('d/m/Y'); ?>

</div>

</div>

</div>

</div>

</div>

</div>

<!-- Statistics -->

<!-- Statistics -->

<div class="row g-3 mb-4">

<div class="col-md-3">

<a href="<?= BASE_URL ?>/student/lessons/index.php"
class="dashboard-link">

<div class="card border-0 shadow text-center h-100 dashboard-card">

<div class="card-body">

<i class="bi bi-journal-richtext text-primary fs-1"></i>

<h2><?= $totalLessons; ?></h2>

<p>الدروس</p>

</div>

</div>

</a>

</div>

<div class="col-md-3">

<a href="<?= BASE_URL ?>/student/activities/index.php"
class="dashboard-link">

<div class="card border-0 shadow text-center h-100 dashboard-card">

<div class="card-body">

<i class="bi bi-list-task text-success fs-1"></i>

<h2><?= $totalActivities; ?></h2>

<p>الأنشطة</p>

</div>

</div>

</a>

</div>

<div class="col-md-3">

<a href="<?= BASE_URL ?>/student/quizzes/index.php"
class="dashboard-link">

<div class="card border-0 shadow text-center h-100 dashboard-card">

<div class="card-body">

<i class="bi bi-patch-question-fill text-danger fs-1"></i>

<h2><?= $totalQuizzes; ?></h2>

<p>الاختبارات القصيرة</p>

</div>

</div>

</a>

</div>

<div class="col-md-3">

<a href="<?= BASE_URL ?>/notifications/index.php"
class="dashboard-link">

<div class="card border-0 shadow text-center h-100 dashboard-card">

<div class="card-body">

<i class="bi bi-bell-fill text-warning fs-1"></i>

<h2><?= $totalNotifications; ?></h2>

<p>الإشعارات</p>

</div>

</div>

</a>

</div>

</div>

<style>

.dashboard-link{
    text-decoration:none;
    color:inherit;
    display:block;
}

.dashboard-link:hover{
    color:inherit;
}

.dashboard-card{
    transition:all .3s ease;
    cursor:pointer;
}

.dashboard-card:hover{
    transform:translateY(-6px);
    box-shadow:0 10px 25px rgba(0,0,0,.15)!important;
}

</style>

<!-- Charts -->

<div class="row mb-4">

<div class="col-md-6">

<div class="card shadow border-0">

<div class="card-header bg-primary text-white">

إحصائيات الطالب

</div>

<div class="card-body">

<canvas id="studentChart"
style="height:350px !important;"></canvas>

</div>

</div>

</div>

<div class="col-md-6">

<div class="card shadow border-0">

<div class="card-header bg-success text-white">

التقدم الدراسي

</div>

<div class="card-body">

<canvas id="progressChart"
style="height:350px !important;"></canvas>

</div>

</div>

</div>

</div>


<!-- Recent Lessons -->

<div class="card shadow border-0 mb-4">

<div class="card-header bg-primary text-white">

أحدث الدروس

</div>

<div class="card-body">

<table class="table table-bordered">

<thead>

<tr>

<th>#</th>
<th>اسم الدرس</th>
<th>التاريخ</th>

</tr>

</thead>

<tbody>

<?php foreach($recentLessons as $index => $lesson): ?>

<tr>

<td><?= $index + 1; ?></td>

<td>

<?= htmlspecialchars(
$lesson['lesson_title']
); ?>

</td>

<td>

<?= date(
'd/m/Y',
strtotime(
$lesson['created_at']
)
); ?>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

</div>

<!-- Notifications -->

<div class="card shadow border-0">

<div class="card-header bg-warning">

آخر الإشعارات

</div>

<div class="card-body">

<?php if(empty($notifications)): ?>

<div class="alert alert-info">

لا توجد إشعارات

</div>

<?php else: ?>

<ul class="list-group">

<?php foreach($notifications as $notification): ?>

<li class="list-group-item">

<strong>

<?= htmlspecialchars(
$notification['title']
); ?>

</strong>

<br>

<?= htmlspecialchars(
$notification['message']
); ?>

<small class="text-muted d-block">

<?= date(
'd/m/Y H:i',
strtotime(
$notification['created_at']
)
); ?>

</small>

</li>

<?php endforeach; ?>

</ul>

<?php endif; ?>

</div>

</div>

</div>

</div>


</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
new Chart(
document.getElementById('studentChart'),
{
    type:'doughnut',

    data:{
        labels:[
            'الدروس',
            'الأنشطة',
            'الاختبارات'
        ],

        datasets:[{
            data:[
                <?= $totalLessons ?>,
                <?= $totalActivities ?>,
                <?= $totalQuizzes ?>
            ],

            backgroundColor:[
                '#2563EB',
                '#10B981',
                '#F59E0B'
            ]
        }]
    },

    options:{
        responsive:true,
        maintainAspectRatio:false
    }
});

new Chart(
document.getElementById('progressChart'),
{
    type:'radar',

    data:{
        labels:[
            'الدروس',
            'الأنشطة',
            'الاختبارات'
        ],

        datasets:[{
            label:'التقدم الدراسي',

            data:[
                <?= $totalLessons ?>,
                <?= $totalActivities ?>,
                <?= $totalQuizzes ?>
            ],

            backgroundColor:'rgba(37,99,235,0.2)',
            borderColor:'#2563EB',
            borderWidth:3,
            pointBackgroundColor:'#2563EB'
        }]
    },

    options:{
        responsive:true,
        maintainAspectRatio:false,

        scales:{
            r:{
                beginAtZero:true
            }
        }
    }
});

</script>

<?php include '../app/views/layouts/footer.php'; ?>