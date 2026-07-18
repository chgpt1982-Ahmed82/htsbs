<?php

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';

require_once '../../app/models/Assignment.php';
require_once '../../app/models/Notification.php';

$db = (new Database())->connect();

$assignmentModel =
new Assignment();

$notificationModel =
new Notification();

$count =
$notificationModel->unreadCount(
$_SESSION['user_id']
);

/*
جلب الطالب الحالي
*/

$stmt = $db->prepare(

"SELECT id
 FROM students
 WHERE user_id=?"

);

$stmt->execute([
$_SESSION['user_id']
]);

$student =
$stmt->fetch(PDO::FETCH_ASSOC);

if(!$student)
{
    die('Student Not Found');
}

/*
جلب جميع تسليمات الطالب
*/

$submissions =
$assignmentModel->getStudentSubmissions(
$student['id']
);
include '../../app/views/layouts/header.php';

?>
<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/student_sidebar.php'; ?>


<div class="main-content">
<div class="d-flex justify-content-between mb-3">

<h2>

My Submissions

</h2>

<a
href="index.php"
class="btn btn-primary">

Assignments

</a>

</div>

<?php if(empty($submissions)): ?>

<div class="alert alert-info">

No submissions found.

</div>

<?php else: ?>

<table class="table table-bordered table-striped">

<thead>

<tr>

<th>Assignment</th>
<th>Submitted At</th>
<th>File</th>
<th>Score</th>
<th>Feedback</th>
<th>Status</th>

</tr>

</thead>

<tbody>

<?php foreach($submissions as $submission): ?>

<tr>

<td>

<?= htmlspecialchars(
$submission['title']
); ?>

</td>

<td>

<?= $submission['submitted_at']; ?>

</td>

<td>

<?php if(!empty($submission['file_path'])): ?>

<a
href="<?= BASE_URL . '/' . $submission['file_path']; ?>"
target="_blank"
class="btn btn-info btn-sm">

Download

</a>

<?php else: ?>

<span class="text-muted">

No File

</span>

<?php endif; ?>

</td>

<td>

<?php if($submission['score'] !== null): ?>

<span class="badge bg-success">

<?= $submission['score']; ?>

</span>

<?php else: ?>

<span class="badge bg-warning">

Pending

</span>

<?php endif; ?>

</td>

<td>

<?= htmlspecialchars(
$submission['feedback'] ?? ''
); ?>

</td>

<td>

<?php if($submission['score'] !== null): ?>

<span class="badge bg-success">

Graded

</span>

<?php else: ?>

<span class="badge bg-secondary">

Waiting

</span>

<?php endif; ?>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

<?php endif; ?>


</div>
<?php include '../../app/views/layouts/footer.php'; ?>
