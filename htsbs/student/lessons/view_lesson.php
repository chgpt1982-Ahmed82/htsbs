<?php

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';

if(
    !isset($_SESSION['user_id']) ||
    $_SESSION['role_id'] != 3
){
    exit('Unauthorized Access');
}

if(!isset($_GET['id']))
{
    die('Lesson Not Found');
}

$lessonId = (int)$_GET['id'];

$db = (new Database())->connect();

/*
====================================
Student
====================================
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
    die('Student Not Found');
}

/*
====================================
Lesson
====================================
*/

$stmt = $db->prepare("
SELECT

    l.*,

    c.course_name

FROM lessons l

INNER JOIN courses c
    ON l.course_id = c.id

WHERE l.id = ?
");

$stmt->execute([
    $lessonId
]);

$lesson = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$lesson)
{
    die('Lesson Not Found');
}

include '../../app/views/layouts/header.php';

?>

<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/student_sidebar.php'; ?>
<div class="main-content">

<div class="d-flex justify-content-between align-items-center mb-4">

    <h2>

        📚 <?= htmlspecialchars($lesson['lesson_title']); ?>

    </h2>

    <a
    href="index.php"
    class="btn btn-secondary">

        <i class="bi bi-arrow-right"></i>

        رجوع

    </a>

</div>

<div class="card shadow border-0">

<div class="card-header bg-primary text-white">

    <h4 class="mb-0">

        <?= htmlspecialchars($lesson['lesson_title']); ?>

    </h4>

</div>

<div class="card-body">

    <div class="row mb-3">

        <div class="col-md-6">

            <strong>المقرر:</strong>

            <?= htmlspecialchars($lesson['course_name']); ?>

        </div>

        <div class="col-md-6">

            <strong>نوع الدرس:</strong>

            <?= htmlspecialchars($lesson['lesson_type']); ?>

        </div>

    </div>

    <hr>

    <h5 class="text-primary">

        وصف الدرس

    </h5>

    <p class="mt-3">

        <?= nl2br(
        htmlspecialchars(
        $lesson['lesson_description']
        )
        ); ?>

    </p>

    <?php if(!empty($lesson['file_path'])): ?>

    <hr>

    <h5 class="text-success">

        الملفات المرفقة

    </h5>

    <a
    href="<?= BASE_URL . '/' . $lesson['file_path']; ?>"
    target="_blank"
    class="btn btn-success">

        <i class="bi bi-download"></i>

        تحميل الملف

    </a>

    <?php endif; ?>

    <?php if(!empty($lesson['video_link'])): ?>

    <?php

    $videoUrl = $lesson['video_link'];

    preg_match(
        '/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\n?#]+)/',
        $videoUrl,
        $matches
    );

    $videoId = $matches[1] ?? '';

    ?>

    <?php if($videoId): ?>

    <hr>

    <h5 class="text-danger">

        <i class="bi bi-youtube"></i>

        فيديو الشرح

    </h5>

    <div class="ratio ratio-16x9 mt-3">

        <iframe
        src="https://www.youtube.com/embed/<?= $videoId; ?>"
        title="YouTube video"
        allowfullscreen>
        </iframe>

    </div>

    <?php endif; ?>

    <?php endif; ?>

</div>

<div class="card-footer text-muted">

    <i class="bi bi-calendar-event"></i>

    تاريخ الإضافة:

    <?= date(
        'd/m/Y H:i',
        strtotime(
        $lesson['created_at']
        )
    ); ?>

</div>

</div>

</div>

</div>


</div>
<?php include '../../app/views/layouts/footer.php'; ?>

