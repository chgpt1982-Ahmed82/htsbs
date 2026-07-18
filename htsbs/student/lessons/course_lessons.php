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

if(!isset($_GET['course_id']))
{
    die('Course Not Found');
}

$courseId = (int)$_GET['course_id'];

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

$classId = $student['class_id'];

/*
====================================
Course Info
====================================
*/

$stmt = $db->prepare("
SELECT
    course_name,
    course_code
FROM courses
WHERE id=?
");

$stmt->execute([
    $courseId
]);

$course = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$course)
{
    die('Course Not Found');
}

/*
====================================
Lessons
====================================
*/

$stmt = $db->prepare("
SELECT

    l.id,
    l.lesson_title,
    l.lesson_description,
    l.lesson_type,
    l.file_path,
    l.video_link,
    l.created_at

FROM lesson_assignments la

INNER JOIN lessons l
    ON la.lesson_id = l.id

WHERE la.class_id = ?
AND l.course_id = ?

ORDER BY l.created_at DESC
");

$stmt->execute([
    $classId,
    $courseId
]);

$lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../app/views/layouts/header.php';

?>

<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/student_sidebar.php'; ?>
<div class="main-content">


<div class="d-flex justify-content-between align-items-center mb-4">

    <div>

        <h2 class="fw-bold">

            📚 <?= htmlspecialchars($course['course_name']); ?>

        </h2>

        <small class="text-muted">

            <?= htmlspecialchars($course['course_code']); ?>

        </small>

    </div>

    <a
    href="index.php"
    class="btn btn-secondary">

        <i class="bi bi-arrow-right"></i>

        رجوع

    </a>

</div>

<?php if(empty($lessons)): ?>

<div class="alert alert-info">

    لا توجد دروس لهذا المقرر حالياً

</div>

<?php else: ?>

<div class="row">

<?php foreach($lessons as $lesson): ?>

<div class="col-lg-6 mb-4">

<div class="card shadow border-0 h-100">

<div class="card-header bg-primary text-white">

    <h5 class="mb-0">

        <?= htmlspecialchars($lesson['lesson_title']); ?>

    </h5>

</div>

<div class="card-body">

    <p>

        <?= nl2br(htmlspecialchars($lesson['lesson_description'])); ?>

    </p>

    <hr>

    <p>

        <strong>نوع الدرس:</strong>

        <?= htmlspecialchars($lesson['lesson_type']); ?>

    </p>

    <?php if(!empty($lesson['file_path'])): ?>

    <a
    href="<?= BASE_URL . '/' . $lesson['file_path']; ?>"
    target="_blank"
    class="btn btn-success btn-sm mb-2">

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

    <div class="mt-3">

        <h6>

            <i class="bi bi-youtube text-danger"></i>

            شرح الفيديو

        </h6>

        <div class="ratio ratio-16x9">

            <iframe
            src="https://www.youtube.com/embed/<?= $videoId; ?>"
            allowfullscreen>
            </iframe>

        </div>

    </div>

    <?php endif; ?>

    <?php endif; ?>

</div>

<div class="card-footer text-muted">

    <i class="bi bi-calendar"></i>

    <?= date(
        'd/m/Y H:i',
        strtotime($lesson['created_at'])
    ); ?>

</div>

</div>

</div>

<?php endforeach; ?>

</div>

<?php endif; ?>

</div>

</div>


</div>
<?php include '../../app/views/layouts/footer.php'; ?>

