<?php

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';

if(
    !isset($_SESSION['user_id'])
    || $_SESSION['role_id'] != 3
){
    exit('Unauthorized Access');
}

$db = (new Database())->connect();

$activityId = (int)($_GET['id'] ?? 0);

if($activityId <= 0)
{
    die('Invalid Activity');
}

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
Activity
====================================
*/

$stmt = $db->prepare("
SELECT

    a.*,
    c.course_name

FROM activities a

INNER JOIN courses c
ON a.course_id = c.id

WHERE a.id=?
");

$stmt->execute([
    $activityId
]);

$activity = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$activity)
{
    die('Activity Not Found');
}

/*
====================================
 Previous Submission
====================================
*/

$stmt = $db->prepare("
SELECT *
FROM activity_submissions
WHERE activity_id=?
AND student_id=?
");

$stmt->execute([
    $activityId,
    $student['id']
]);

$submission = $stmt->fetch(PDO::FETCH_ASSOC);

include '../../app/views/layouts/header.php';

?>

<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/student_sidebar.php'; ?>
<div class="main-content">


<div class="card shadow">

<div class="card-header bg-primary text-white">

<h4 class="mb-0">

<i class="bi bi-list-task"></i>

<?= htmlspecialchars($activity['title']); ?>

</h4>

</div>

<div class="card-body">

<div class="mb-3">

<strong>المقرر:</strong>

<?= htmlspecialchars($activity['course_name']); ?>

</div>

<div class="mb-3">

<strong>الدرجة القصوى:</strong>

<?= $activity['max_grade']; ?>

</div>

<div class="mb-3">

<strong>موعد التسليم:</strong>

<?= !empty($activity['due_date'])
? date(
'd/m/Y h:i A',
strtotime($activity['due_date'])
)
: 'غير محدد'; ?>

</div>

<hr>

<h5>

تعليمات النشاط

</h5>

<div class="alert alert-light">

<?= nl2br(
htmlspecialchars(
$activity['instructions']
)
); ?>

</div>

<?php if($submission): ?>

<div class="alert alert-success">

تم تسليم النشاط مسبقاً بتاريخ

<?= $submission['submitted_at']; ?>

</div>

<?php if(!empty($submission['file_path'])): ?>

<a
href="<?= BASE_URL . '/' . $submission['file_path']; ?>"
target="_blank"
class="btn btn-info mb-3">

تحميل الملف المرفوع

</a>

<?php endif; ?>

<?php if($submission['grade'] !== null): ?>

<div class="alert alert-primary">

<strong>الدرجة:</strong>

<?= $submission['grade']; ?>

<br>

<strong>ملاحظات المعلم:</strong>

<?= nl2br(
htmlspecialchars(
$submission['feedback']
)
); ?>

</div>

<?php endif; ?>

<?php else: ?>

<form
action="submit.php"
method="POST"
enctype="multipart/form-data">

<input
type="hidden"
name="activity_id"
value="<?= $activity['id']; ?>">

<div class="mb-3">

<label class="form-label">

نص الحل

</label>

<textarea
name="submission_text"
class="form-control"
rows="5"></textarea>

</div>

<div class="mb-3">

<label class="form-label">

رفع ملف

(PDF / DOC / DOCX)

</label>

<input
type="file"
name="solution_file"
class="form-control">

</div>

<button
type="submit"
class="btn btn-success">

<i class="bi bi-upload"></i>

تسليم النشاط

</button>

<a
href="index.php"
class="btn btn-secondary">

رجوع

</a>

</form>

<?php endif; ?>

</div>

</div>

</div>

</div>

</div>
<?php include '../../app/views/layouts/footer.php'; ?>