<?php

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../app/models/Quiz.php';
require_once '../../app/models/Notification.php';

if(
    !isset($_SESSION['user_id']) ||
    $_SESSION['role_id'] != 3
){
    exit('Unauthorized Access');
}

$quizModel = new Quiz();

$notificationModel = new Notification();

$count = $notificationModel->unreadCount(
    $_SESSION['user_id']
);

$db = (new Database())->connect();

/*
=================================
Student Info
=================================
*/

$stmt = $db->prepare("

SELECT

    id,
    class_id

FROM students

WHERE user_id = ?

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
=================================
Available Quizzes
=================================
*/

$stmt = $db->prepare("

SELECT

    q.id,

    q.title,

    q.duration_minutes,

    q.total_marks,

    q.start_date,

    q.end_date,

    q.attempts_allowed,

    q.is_published,

    c.course_name,

    c.course_code,

    qa.assigned_at

FROM quiz_assignments qa

INNER JOIN quizzes q
    ON qa.quiz_id = q.id

INNER JOIN courses c
    ON q.course_id = c.id

WHERE qa.class_id = ?

AND q.is_published = 1

ORDER BY q.start_date DESC

");

$stmt->execute([
    $classId
]);

$quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../app/views/layouts/header.php';

?>

<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/student_sidebar.php'; ?>
<div class="main-content">


<div class="d-flex justify-content-between align-items-center mb-4">

<h2>

📝 اختباراتي

</h2>

<span class="badge bg-primary">

<?= count($quizzes); ?>

اختبار

</span>

</div>

<?php if(empty($quizzes)): ?>

<div class="alert alert-info">

لا توجد اختبارات متاحة حالياً

</div>

<?php else: ?>

<div class="card border-0 shadow">

<div class="card-header bg-primary text-white">

<h5 class="mb-0">

📋 قائمة الاختبارات

</h5>

</div>

<div class="card-body">

<div class="table-responsive">

<table class="table table-hover table-bordered align-middle">

<thead class="table-dark">

<tr>

<th width="60">#</th>

<th>المقرر</th>

<th>رمز المقرر</th>

<th>عنوان الاختبار</th>

<th>الدرجة</th>

<th>المدة</th>

<th>بداية الاختبار</th>

<th>نهاية الاختبار</th>

<th width="150">الإجراء</th>

</tr>

</thead>

<tbody>

<?php foreach($quizzes as $index => $quiz): ?>

<tr>

<td>

<?= $index + 1 ?>

</td>

<td>

<?= htmlspecialchars(
$quiz['course_name']
); ?>

</td>

<td>

<?= htmlspecialchars(
$quiz['course_code']
); ?>

</td>

<td>

<strong>

<?= htmlspecialchars(
$quiz['title']
); ?>

</strong>

</td>

<td>

<span class="badge bg-success">

<?= $quiz['total_marks']; ?>

درجة

</span>

</td>

<td>

<span class="badge bg-info">

<?= $quiz['duration_minutes']; ?>

دقيقة

</span>

</td>

<td>

<?= date(
'd/m/Y H:i',
strtotime(
$quiz['start_date']
)
); ?>

</td>

<td>

<?= date(
'd/m/Y H:i',
strtotime(
$quiz['end_date']
)
); ?>

</td>

<td>

<?php

$now = date('Y-m-d H:i:s');

if(
    $now >= $quiz['start_date']
    &&
    $now <= $quiz['end_date']
):

?>

<a
href="start.php?id=<?= $quiz['id']; ?>"
class="btn btn-success btn-sm">

<i class="bi bi-play-fill"></i>

بدء الاختبار

</a>

<?php elseif($now < $quiz['start_date']): ?>

<span class="badge bg-warning text-dark">

لم يبدأ بعد

</span>

<?php else: ?>

<span class="badge bg-secondary">

انتهى

</span>

<?php endif; ?>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

</div>

</div>

<?php endif; ?>

</div>

</div>


</div>
<?php include '../../app/views/layouts/footer.php'; ?>
