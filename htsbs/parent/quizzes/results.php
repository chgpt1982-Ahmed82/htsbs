<?php

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';

require_once '../../app/models/Notification.php';

$db =
(new Database())->connect();

$notificationModel =
new Notification();

$count =
$notificationModel->unreadCount(
$_SESSION['user_id']
);

/*
الحصول على أبناء ولي الأمر
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

?>

<div class="container-fluid">

<div class="row">

<?php include '../../app/views/layouts/parent_sidebar.php'; ?>

<div class="col-md-10 p-4">

<h2>

📊 Children Quiz Results

</h2>

<?php if(empty($children)): ?>

<div class="alert alert-warning">

No Students Linked To This Parent

</div>

<?php else: ?>

<?php foreach($children as $child): ?>

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

<?php

$resultStmt = $db->prepare(

"SELECT

    qr.*,
    q.title

 FROM quiz_results qr

 INNER JOIN quizzes q
 ON qr.quiz_id=q.id

 WHERE qr.student_id=?

 ORDER BY qr.completed_at DESC"

);

$resultStmt->execute([
$child['id']
]);

$results =
$resultStmt->fetchAll(PDO::FETCH_ASSOC);

?>

<?php if(empty($results)): ?>

<div class="alert alert-info">

No Quiz Results Available

</div>

<?php else: ?>

<table class="table table-bordered table-striped">

<thead class="table-dark">

<tr>

<th>#</th>

<th>Quiz</th>

<th>Score</th>

<th>Attempt</th>

<th>Completed At</th>

</tr>

</thead>

<tbody>

<?php

$counter = 1;
$totalScore = 0;
$highestScore = 0;

foreach($results as $result):

$totalScore +=
$result['score'];

if(
$result['score']
>
$highestScore
)
{
    $highestScore =
    $result['score'];
}

?>

<tr>

<td>

<?= $counter++; ?>

</td>

<td>

<?= htmlspecialchars(
$result['title']
); ?>

</td>

<td>

<?= $result['score']; ?>

</td>

<td>

<?= $result['attempt_number']; ?>

</td>

<td>

<?= $result['completed_at']; ?>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

<?php

$average =
count($results) > 0
?
round(
$totalScore /
count($results),
2
)
:
0;

?>

<div class="row">

<div class="col-md-4">

<div class="card text-center">

<div class="card-body">

<h3>

<?= count($results); ?>

</h3>

<p>

Completed Quizzes

</p>

</div>

</div>

</div>

<div class="col-md-4">

<div class="card text-center">

<div class="card-body">

<h3>

<?= $average; ?>

</h3>

<p>

Average Score

</p>

</div>

</div>

</div>

<div class="col-md-4">

<div class="card text-center">

<div class="card-body">

<h3>

<?= $highestScore; ?>

</h3>

<p>

Highest Score

</p>

</div>

</div>

</div>

</div>

<?php endif; ?>

</div>

</div>

<?php endforeach; ?>

<?php endif; ?>

</div>

</div>

</div>

<?php include '../../app/views/layouts/footer.php'; ?>
