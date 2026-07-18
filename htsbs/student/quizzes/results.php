<?php

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';

require_once '../../app/models/Quiz.php';
require_once '../../app/models/Notification.php';

$quizModel =
new Quiz();

$notificationModel =
new Notification();

$count =
$notificationModel->unreadCount(
$_SESSION['user_id']
);

$db =
(new Database())->connect();

/*
الحصول على الطالب
*/

$stmt = $db->prepare(

"SELECT

    id

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

$results =
$quizModel->getStudentResults(
$student['id']
);

include '../../app/views/layouts/header.php';

?>

<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/student_sidebar.php'; ?>
<div class="main-content">

<div class="d-flex justify-content-between align-items-center mb-4">

<h2>

📊 My Quiz Results

</h2>

<span class="badge bg-primary">

<?= count($results); ?>

Result(s)

</span>

</div>

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

foreach($results as $result):

$totalScore +=
$result['score'];

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

<div class="row mt-4">

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

<?php

$highest = 0;

foreach($results as $r)
{
    if(
    $r['score']
    >
    $highest
    )
    {
        $highest =
        $r['score'];
    }
}

echo $highest;

?>

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


</div>
<?php include '../../app/views/layouts/footer.php'; ?>
