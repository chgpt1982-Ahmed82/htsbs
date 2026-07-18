<?php

session_start();

require_once '../../config/config.php';
require_once '../../app/models/Assignment.php';
require_once '../../app/models/Notification.php';

$assignmentModel =
new Assignment();

$notificationModel =
new Notification();

$count =
$notificationModel->unreadCount(
$_SESSION['user_id']
);

$assignmentId =
$_GET['id'] ?? 0;

$assignment =
$assignmentModel->find(
$assignmentId
);

if(!$assignment)
{
    die('Assignment Not Found');
}

$submissions =
$assignmentModel->getSubmissions(
$assignmentId
);

include '../../app/views/layouts/header.php';

?>
<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">   

<div class="d-flex justify-content-between mb-3">

<h2>

Assignment Submissions

</h2>

<a
href="index.php"
class="btn btn-secondary">

Back

</a>

</div>

<div class="card mb-4">

<div class="card-body">

<h4>

<?= htmlspecialchars(
$assignment['title']
); ?>

</h4>

<p>

<?= htmlspecialchars(
$assignment['description']
); ?>

</p>

<p>

<strong>Due Date:</strong>

<?= $assignment['due_date']; ?>

</p>

</div>

</div>

<table class="table table-bordered table-striped">

<thead>

<tr>

<th>Student</th>
<th>Submitted At</th>
<th>File</th>
<th>Score</th>
<th>Feedback</th>
<th>Action</th>

</tr>

</thead>

<tbody>

<?php foreach($submissions as $submission): ?>

<tr>

<td>

<?= htmlspecialchars(
$submission['full_name']
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

<?= $submission['score'] ?? '-'; ?>

</td>

<td>

<?= htmlspecialchars(
$submission['feedback'] ?? ''
); ?>

</td>

<td>

<a
href="grade.php?id=<?= $submission['id']; ?>"
class="btn btn-success btn-sm">

Grade

</a>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

<?php include '../../app/views/layouts/footer.php'; ?>
