<?php

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';

require_once '../../app/models/Transcript.php';
require_once '../../app/models/Notification.php';

$db = (new Database())->connect();

$transcriptModel =
new Transcript();

$notificationModel =
new Notification();

$count =
$notificationModel->unreadCount(
$_SESSION['user_id']
);

/*
جلب أبناء ولي الأمر
*/

$stmt = $db->prepare(

"SELECT

s.id,
s.student_number,
u.full_name

FROM parent_student ps

INNER JOIN students s
ON ps.student_id=s.id

INNER JOIN users u
ON s.user_id=u.id

INNER JOIN parents p
ON ps.parent_id=p.id

WHERE p.user_id=?"

);

$stmt->execute([
$_SESSION['user_id']
]);

$children =
$stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../app/views/layouts/header.php';
include '../../app/views/layouts/parent_sidebar.php';

?>

<div class="col-md-10 p-4">

<h2 class="mb-4">

Academic Transcript

</h2>

<?php if(empty($children)): ?>

<div class="alert alert-warning">

No Students Linked To This Parent

</div>

<?php else: ?>

<?php foreach($children as $child): ?>

<?php

$transcript =
$transcriptModel->getTranscript(
$child['id']
);

$status =
(
$transcript['final_grade']
>= 50
)
?
'Pass'
:
'Fail';

?>

<div class="card mb-4">

<div class="card-header bg-primary text-white">

<strong>

<?= htmlspecialchars(
$child['full_name']
); ?>

</strong>

*

<?= htmlspecialchars(
$child['student_number']
); ?>

</div>

<div class="card-body">

<div class="row">

<div class="col-md-4">

<div class="card text-center mb-3">

<div class="card-body">

<h3>

<?= $transcript['attendance']; ?>%

</h3>

<p>

Attendance Average

</p>

</div>

</div>

</div>

<div class="col-md-4">

<div class="card text-center mb-3">

<div class="card-body">

<h3>

<?= $transcript['assignments']; ?>

</h3>

<p>

Assignments Average

</p>

</div>

</div>

</div>

<div class="col-md-4">

<div class="card text-center mb-3">

<div class="card-body">

<h3>

<?= $transcript['exams']; ?>

</h3>

<p>

Exams Average

</p>

</div>

</div>

</div>

</div>

<div class="row">

<div class="col-md-6">

<div class="card border-success">

<div class="card-header bg-success text-white">

Final Grade

</div>

<div class="card-body text-center">

<h2>

<?= $transcript['final_grade']; ?>

</h2>

</div>

</div>

</div>

<div class="col-md-6">

<div class="card border-info">

<div class="card-header bg-info text-white">

GPA

</div>

<div class="card-body text-center">

<h2>

<?= $transcript['gpa']; ?>

</h2>

</div>

</div>

</div>

</div>

<div class="alert alert-secondary mt-3">

<strong>Status:</strong>

<?= $status; ?>

</div>

</div>

</div>

<?php endforeach; ?>

<?php endif; ?>

</div>

<?php include '../../app/views/layouts/footer.php'; ?>
