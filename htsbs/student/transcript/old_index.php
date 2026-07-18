<?php

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';

require_once '../../app/models/Transcript.php';
require_once '../../app/models/Notification.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/login.php");
    exit;
}

$db = (new Database())->connect();

$transcriptModel = new Transcript();
$notificationModel = new Notification();

$count = $notificationModel->unreadCount($_SESSION['user_id']);

/*
=================================
جلب بيانات الطالب
=================================
*/
    $stmt = $db->prepare("
    SELECT
        s.id,
        s.student_number,
        u.full_name,
        c.class_name
    FROM students s
    INNER JOIN users u
        ON u.id = s.user_id
    LEFT JOIN classes c
        ON c.id = s.class_id
    WHERE s.user_id = ?
");


$stmt->execute([$_SESSION['user_id']]);

//$student = $stmt->fetch(PDO::FETCH_ASSOC);
$st = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {

    echo '<pre>';
    echo 'SESSION USER ID = ' . $_SESSION['user_id'];
    echo '</pre>';

    die('الطالب غير موجود');
}
/*
=================================
السجل الأكاديمي
=================================
*/

$transcript = $transcriptModel->getTranscript($student['id']);

/*
=================================
قيم افتراضية
=================================
*/

$attendance =$transcript['attendance'] ?? 0;

$assignments =$transcript['assignments'] ?? 0;

$exams =$transcript['exams'] ?? 0;

$finalGrade =$transcript['final_grade'] ?? 0;

$gpa =$transcript['gpa'] ?? 0;

$status =($finalGrade >= 50)? 'ناجح': 'راسب';
//echo '<pre>';
//print_r($student);
//echo '</pre>';
include '../../app/views/layouts/header.php';

?>

<div class="container-fluid">

    <?php include '../../app/views/layouts/student_sidebar.php'; ?>

    <div class="main-content">

        <div class="card shadow border-0 mb-4">
        <i class="bi bi-person-circle fs-1 text-primary"></i>
    
<div class="card mb-4">

    <div class="card-header bg-primary text-white">
        معلومات الطالب
    </div>

    <div class="card-body text-end">

        <div class="row">

            <div class="col-md-4 mb-3">
                <strong>اسم الطالب:</strong><br>
                <?= htmlspecialchars($st['full_name']); ?>
            </div>

            <div class="col-md-4 mb-3">
                <strong>الرقم الأكاديمي:</strong><br>
                <?= htmlspecialchars($st['student_number']); ?>
            </div>

            <div class="col-md-4 mb-3">
                <strong>الصف:</strong><br>
                <?= htmlspecialchars($st['class_name']); ?>
            </div>

        </div>

    </div>

</div>



        <!-- الإحصائيات -->

        <div class="row mb-4">

            <div class="col-md-4">

                <div class="card dashboard-stat-card shadow">

                    <div class="card-body">

                        <i class="bi bi-calendar-check text-primary"
                           style="font-size:45px"></i>

                        <h2 class="mt-3">

                            <?= $attendance ?>%

                        </h2>

                        <h5>

                            معدل الحضور

                        </h5>

                    </div>

                </div>

            </div>

            <div class="col-md-4">

                <div class="card dashboard-stat-card shadow">

                    <div class="card-body">

                        <i class="bi bi-journal-check text-success"
                           style="font-size:45px"></i>

                        <h2 class="mt-3">

                            <?= $assignments ?>

                        </h2>

                        <h5>

                            معدل الواجبات

                        </h5>

                    </div>

                </div>

            </div>

            <div class="col-md-4">

                <div class="card dashboard-stat-card shadow">

                    <div class="card-body">

                        <i class="bi bi-patch-check text-danger"
                           style="font-size:45px"></i>

                        <h2 class="mt-3">

                            <?= $exams ?>

                        </h2>

                        <h5>

                            معدل الاختبارات

                        </h5>

                    </div>

                </div>

            </div>

        </div>

        <!-- النتيجة النهائية -->

        <div class="card shadow border-0 mb-4">

            <div class="card-header bg-success text-white">

                <i class="bi bi-award-fill"></i>

                النتيجة النهائية

            </div>

            <div class="card-body">

                <div class="row text-center">

                    <div class="col-md-6">

                        <h5>

                            الدرجة النهائية

                        </h5>

                        <h2 class="text-primary">

                            <?= $finalGrade ?>

                        </h2>

                    </div>

                    <div class="col-md-6">

                        <h5>

                            GPA

                        </h5>

                        <h2 class="text-success">

                            <?= $gpa ?>

                        </h2>

                    </div>

                </div>

            </div>

        </div>

        <!-- حالة الطالب -->

        <div class="alert <?= $finalGrade >= 50 ? 'alert-success' : 'alert-danger'; ?>">

            <strong>

                حالة الطالب:

            </strong>

            <?= $status; ?>

        </div>

    </div>

</div>

<?php include '../../app/views/layouts/footer.php'; ?>