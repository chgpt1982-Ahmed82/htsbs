<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../app/models/CourseAssignment.php';
require_once '../app/models/Lesson.php';
require_once '../app/models/Activity.php';




if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/login.php");
    exit;
}
if ($_SESSION['role_id'] != 2) {
    die('Access Denied');
}



$lessonModel = new Lesson();
$model = new CourseAssignment();
$activityModel = new Activity();

$totalCourses = $model->countTeacherCourses($_SESSION['user_id']);
$totalClasses = $model->countTeacherClasses($_SESSION['user_id']);
$totalLessons = $model->countTeacherLessons($_SESSION['user_id']);
$totalQuizzes = $model->countTeacherQuizzes($_SESSION['user_id']);
$totalStudents = $model->countTeacherStudents($_SESSION['user_id']);
$totalAssignments =$model->countTeacherAssignments($_SESSION['user_id']);
$totalActivities = $model->countTeacherActivities($_SESSION['user_id']);

$latestLessons = $model->latestLessons($_SESSION['user_id'],5);
$latestActivities = $model->latestActivities($_SESSION['user_id'],5);
$latestAssignments =$model->latestAssignments($_SESSION['user_id'],5);


$monthlyLessons = $lessonModel->monthlyLessons($_SESSION['user_id']);

$totalActivities = $activityModel->countTeacherActivities($_SESSION['user_id']);
//$totalQuizzes = $quizModel->countTeacherQuizzes($_SESSION['teacher_id']);





include '../app/views/layouts/header.php'; ?>
<style>
    .dashboard-welcome {
        background: linear-gradient(135deg, #2563EB, #7C3AED);
        color: #fff;
        border-radius: 20px;
    }

    .dashboard-card {
        border: none;
        border-radius: 16px;
        transition: .3s;
    }

    .dashboard-card:hover {
        transform: translateY(-5px);
    }

    .dashboard-icon {
        font-size: 40px;
    }
</style>
<div class="container-fluid"> 
    <?php include '../app/views/layouts/teacher_sidebar.php'; ?> 
    <div class="main-content"> 
    
    <!-- Welcome Card -->
        <div class="card dashboard-welcome shadow-lg border-0 mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h3 class="fw-bold"> 👨‍🏫 مرحباً أستاذ <?= htmlspecialchars($_SESSION['name']); ?> </h3>
                        <p class="mb-1"> نتمنى لك يوماً تعليمياً مميزاً </p> <small> <?= date('d/m/Y'); ?> </small>
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0"> <a href="<?= BASE_URL ?>/teacher/lessons/create.php"
                            class="btn btn-light m-1"> إضافة درس </a> <a
                            href="<?= BASE_URL ?>/teacher/activities/create.php" class="btn btn-warning m-1"> إضافة نشاط
                        </a> <a href="<?= BASE_URL ?>/teacher/quizzes/create.php" class="btn btn-danger m-1"> إضافة
                            اختبار قصير </a> </div>
                </div>
            </div>
        </div> <!-- Statistics -->
        <div class="row g-3 mb-4">
            <div class="col-md-4 col-lg-2">
                <div class="card dashboard-card shadow">
                    <div class="card-body text-center"> <i class="bi bi-book-fill text-primary dashboard-icon"></i>
                        <h3><?= $totalCourses ?></h3>
                        <p>المقررات</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-lg-2">
                <div class="card dashboard-card shadow">
                    <div class="card-body text-center"> <i class="bi bi-people-fill text-success dashboard-icon"></i>
                        <h3><?= $totalClasses ?></h3>
                        <p>الصفوف</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-lg-2">
                <div class="card dashboard-card shadow">
                    <div class="card-body text-center"> <i class="bi bi-mortarboard-fill text-info dashboard-icon"></i>
                        <h3><?= $totalStudents ?></h3>
                        <p>الطلاب</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-lg-2">
                <div class="card dashboard-card shadow">
                    <div class="card-body text-center"> <i
                            class="bi bi-journal-richtext text-warning dashboard-icon"></i>
                        <h3><?= $totalLessons ?></h3>
                        <p>الدروس</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-lg-2">
                <div class="card dashboard-card shadow">
                    <div class="card-body text-center"> <i class="bi bi-list-task text-success dashboard-icon"></i>
                        <h3><?= $totalActivities ?></h3>
                        <p>الأنشطة</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-lg-2">
                <div class="card dashboard-card shadow">
                    <div class="card-body text-center"> <i
                            class="bi bi-patch-question-fill text-danger dashboard-icon"></i>
                        <h3><?= $totalQuizzes ?></h3>
                        <p>الاختبارات </p>
                    </div>
                </div>
            </div>
        </div>
        
        
        
        <div class="row g-3 mb-4">
            <div class="col-md-4"> <a href="<?= BASE_URL ?>/teacher/lessons/index.php" class="text-decoration-none">
                    <div class="card shadow border-0 h-100 dashboard-table-card">
                        <div class="card-body text-center" > <i class="bi bi-book-fill text-primary"
                                style="font-size:50px"></i>
                            <h4 class="mt-3"> <?= $totalLessons ?> </h4>
                            <h5> بنك الدروس </h5>
                        </div>
                    </div>
                </a> </div>
                
                
            <div class="col-md-4"> <a href="<?= BASE_URL ?>/teacher/activities/index.php" class="text-decoration-none">
                    <div class="card shadow border-0 h-100 dashboard-table-card">
                        <div class="card-body text-center" > <i class="bi bi-list-task text-success"
                                style="font-size:50px"></i>
                            <h4 class="mt-3"> <?= $totalActivities ?> </h4>
                            <h5> بنك الأنشطة </h5>
                        </div>
                    </div>
                </a> </div>
            <div class="col-md-4"> <a href="<?= BASE_URL ?>/teacher/quizzes/index.php" class="text-decoration-none">
                    <div class="card shadow border-0 h-100">
                        <div class="card-body text-center"> <i class="bi bi-patch-question-fill text-danger"
                                style="font-size:50px"></i>
                            <h4 class="mt-3"> <?= $totalQuizzes ?> </h4>
                            <h5> بنك الاختبارات  </h5>
                        </div>
                    </div>
                </a> </div>
        </div>
        
        
        <div class="row g-3 mb-4">
    <div class="col-md-4">
    <a href="<?= BASE_URL ?>/teacher/assignments/index.php"
       class="text-decoration-none">

        <div class="card dashboard-stat-card shadow border-0 h-100">

            <div class="card-body text-center">

                <i class="bi bi-journal-check text-info"
                   style="font-size:50px"></i>

                <h4 class="mt-3">
                    <?= $totalAssignments ?>
                </h4>

                <h5>
                    بنك الواجبات
                </h5>

            </div>

        </div>

    </a>

</div>



<div class="col-md-4">
    <a href="<?= BASE_URL ?>/teacher/assignments/index.php"
       class="text-decoration-none">

        <div class="card dashboard-stat-card shadow border-0 h-100">

            <div class="card-body text-center">

                <i class="bi bi-journal-check text-info"
                   style="font-size:50px"></i>

                <h4 class="mt-3">
                    <?= $totalAssignments ?>
                </h4>

                <h5>
                    بنك الواجبات
                </h5>

            </div>

        </div>

    </a>

</div>

<div class="col-md-4">
    <a href="<?= BASE_URL ?>/teacher/assignments/index.php"
       class="text-decoration-none">

        <div class="card dashboard-stat-card shadow border-0 h-100">

            <div class="card-body text-center">

                <i class="bi bi-journal-check text-info"
                   style="font-size:50px"></i>

                <h4 class="mt-3">
                    <?= $totalAssignments ?>
                </h4>

                <h5>
                    بنك الواجبات
                </h5>

            </div>

        </div>

    </a>

</div>






</div>
        <!-- Charts -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card shadow border-0">
                    <div class="card-header"> الدروس الشهرية </div>
                    <div class="card-body">
          <canvas id="lessonsChart" style="height:350px !important;"></canvas> 
                         </div>
                </div>
            </div>
           
            
            <div class="col-md-6">
                <div class="card shadow border-0">
                    <div class="card-header">  الأنشطة والاختبارات </div>
                    <div class="card-body">
          <canvas id="teacherChart" style="height:350px !important;"></canvas> 
                         </div>
                </div>
            </div>
            
            
 
        </div>
        
        <!-- Latest Lessons -->
        
        <div class="row mt-4 ">
            <div class="col-md-6 ">
        <div class="card shadow border-0 ">
            <div class="card-header bg-primary text-white">
                
                 <i class="bi bi-clock-history"></i> أحدث الدروس المضافة
            </div>
            
            
            <div class="card-body"> <?php if (empty($latestLessons)): ?>
                    <div class="alert alert-info"> لا توجد دروس مضافة </div> <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>عنوان الدرس</th>
                                    <th>تاريخ الإضافة</th>
                                </tr>
                            </thead>
                            <tbody> <?php foreach ($latestLessons as $index => $lesson): ?>
                                    <tr>
                                        <td><?= $index + 1; ?></td>
                                        <td> <?= htmlspecialchars($lesson['lesson_title']); ?> </td>
                                        <td> <?= date('d/m/Y H:i', strtotime($lesson['created_at'])); ?> </td>
                                    </tr> <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div> <?php endif; ?>
            </div>
                </div>
             </div>
             <div class="col-md-6 ">
         <div class="card shadow border-0">

        <div class="card-header bg-success text-white">

        <i class="bi bi-list-task"></i>

        أحدث الأنشطة المضافة

    </div>

    <div class="card-body">

        <?php if(empty($latestActivities)): ?>

            <div class="alert alert-info">

                لا توجد أنشطة مضافة

            </div>

        <?php else: ?>

            <div class="table-responsive">

                <table class="table table-hover table-bordered">

                    <thead class="table-light">

                        <tr>

                            <th width="80">#</th>

                            <th>عنوان النشاط</th>

                            <th width="220">تاريخ الإضافة</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach($latestActivities as $index => $activity): ?>

                        <tr>

                            <td>

                                <?= $index + 1 ?>

                            </td>

                            <td>

                                <?= htmlspecialchars($activity['title']) ?>

                            </td>

                            <td>

                                <?= date(
                                    'd/m/Y H:i',
                                    strtotime($activity['created_at'])
                                ) ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>
 </div>
    </div>


<div class="card shadow border-0">

    <div class="card-header bg-info text-white">

        <i class="bi bi-journal-check"></i>

        أحدث الواجبات المضافة

    </div>

    <div class="card-body">

        <?php if(empty($latestAssignments)): ?>

            <div class="alert alert-info">

                لا توجد واجبات مضافة

            </div>

        <?php else: ?>

            <div class="table-responsive">

                <table class="table table-bordered">

                    <thead>

                        <tr>

                            <th>#</th>
                            <th>عنوان الواجب</th>
                            <th>تاريخ الإضافة</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach($latestAssignments as $index => $assignment): ?>

                        <tr>

                            <td><?= $index + 1 ?></td>

                            <td>
                                <?= htmlspecialchars($assignment['title']) ?>
                            </td>

                            <td>
                                <?= date(
                                    'd/m/Y H:i',
                                    strtotime($assignment['created_at'])
                                ) ?>
                            </td>

                        </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </div>

</div>


</div>


</div>
     </div>   
        
    </div>  
   <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>

const lessonLabels = [

'يناير',
'فبراير',
'مارس',
'أبريل',
'مايو',
'يونيو',
'يوليو',
'أغسطس',
'سبتمبر',
'أكتوبر',
'نوفمبر',
'ديسمبر'

];

const lessonData = <?= json_encode(array_values($monthlyLessons)); ?>;

console.log(lessonData);

new Chart(

document.getElementById('lessonsChart'),

{

type:'bar',

data:{

labels:lessonLabels,

datasets:[{label:'عدد الدروس',data:lessonData,backgroundColor:'#2563eb',borderColor:'#1d4ed8',borderWidth:1,borderRadius:8}]},

options:{

responsive:true,

maintainAspectRatio:false,

plugins:{

legend:{

display:true

}

},

scales:{

y:{

beginAtZero:true,

ticks:{

precision:0,

stepSize:1

}

}

}

}

}

);

new Chart(

document.getElementById('teacherChart'),

{

type:'pie',

data:{

labels:[

'الأنشطة',

'الاختبارات'

],

datasets:[{

data:[

<?= $totalActivities ?>,

<?= $totalQuizzes ?>

],

backgroundColor:[

'#0d6efd',

'#198754'

],

hoverBackgroundColor:[

'#0b5ed7',

'#157347'

],

borderColor:'#ffffff',

borderWidth:3

}]

},

options:{

responsive:true,

maintainAspectRatio:false,

plugins:{

legend:{

position:'bottom',

labels:{

font:{

size:14

}

}

},

tooltip:{

callbacks:{

label:function(context){

return context.label + ' : ' + context.raw;

}

}

}

}

}

}

);





</script>
    <?php include '../app/views/layouts/footer.php'; ?>