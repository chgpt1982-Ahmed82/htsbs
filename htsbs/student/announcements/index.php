<?php

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';

require_once '../../app/models/Announcement.php';
require_once '../../app/models/Notification.php';

$db = (new Database())->connect();

$announcementModel = new Announcement();
$notificationModel = new Notification();

$count =
$notificationModel->unreadCount(
    $_SESSION['user_id']
);

/*
|--------------------------------------------------------------------------
| جلب بيانات الطالب
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
SELECT
    id,
    class_id
FROM students
WHERE user_id=?
");

$stmt->execute([
    $_SESSION['user_id']
]);

$student = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$student)
{
    die('الطالب غير موجود');
}

/*
|--------------------------------------------------------------------------
| جلب إعلانات الصف
|--------------------------------------------------------------------------
*/

$announcements =
$announcementModel->getStudentAnnouncements(
    $student['class_id']
);

include '../../app/views/layouts/header.php';

?>

<div class="container-fluid">

    <div class="row flex-lg-row-reverse">
    
        <?php include '../../app/views/layouts/student_sidebar.php'; ?>
<div class="main-content">
       

            <div class="d-flex justify-content-between align-items-center mb-4">

                <h2 class="mb-0">
                    📢 الإعلانات
                </h2>

                <span class="badge bg-primary fs-6">
                    <?= count($announcements); ?>
                    إعلان
                </span>

            </div>

            <?php if(empty($announcements)): ?>

                <div class="alert alert-info text-center">

                    لا توجد إعلانات حالياً

                </div>

            <?php else: ?>

                <?php foreach($announcements as $announcement): ?>

                    <div class="card mb-4 shadow-sm">

                        <div class="card-header bg-primary text-white">

                            <div class="d-flex justify-content-between align-items-center">

                                <strong>

                                    <?= htmlspecialchars(
                                        $announcement['title']
                                    ); ?>

                                </strong>

                                <small>

                                    <?= date(
                                        'd/m/Y',
                                        strtotime(
                                            $announcement['created_at']
                                        )
                                    ); ?>

                                </small>

                            </div>

                        </div>

                        <div class="card-body">

                            <p class="mb-0">

                                <?= nl2br(
                                    htmlspecialchars(
                                        $announcement['message']
                                    )
                                ); ?>

                            </p>

                        </div>

                        <div class="card-footer text-muted">

                            تاريخ النشر:

                            <?= date(
                                'd/m/Y H:i',
                                strtotime(
                                    $announcement['created_at']
                                )
                            ); ?>

                        </div>

                    </div>

                <?php endforeach; ?>

            <?php endif; ?>

        </div>

    </div>

</div>

<?php include '../../app/views/layouts/footer.php'; ?>