<?php

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';

require_once '../../app/models/Quiz.php';
require_once '../../app/models/Notification.php';

if(!isset($_GET['id']))
{
    die('Quiz ID Missing');
}

$db =
(new Database())->connect();

$quizModel =
new Quiz();

$notificationModel =
new Notification();

$count =
$notificationModel->unreadCount(
$_SESSION['user_id']
);

$quizId =
(int)$_GET['id'];

$quiz =
$quizModel->getById(
$quizId
);

if(!$quiz)
{
    die('Quiz Not Found');
}

$stmt = $db->prepare(

"SELECT

    qr.*,
    u.full_name

 FROM quiz_results qr

 INNER JOIN students s
 ON qr.student_id=s.id

 INNER JOIN users u
 ON s.user_id=u.id

 WHERE qr.quiz_id=?

 ORDER BY qr.score DESC"

);

$stmt->execute([
$quizId
]);

$results =
$stmt->fetchAll(PDO::FETCH_ASSOC);

$totalStudents =
count($results);

$highestScore = 0;
$lowestScore = 999999;
$totalScore = 0;
$passedStudents = 0;

foreach($results as $result)
{
    $score =
    $result['score'];

    $totalScore +=
    $score;

    if($score > $highestScore)
    {
        $highestScore =
        $score;
    }

    if($score < $lowestScore)
    {
        $lowestScore =
        $score;
    }

    if(
    $score >=
    ($quiz['total_marks'] * 0.5)
    )
    {
        $passedStudents++;
    }
}

$averageScore =
$totalStudents > 0
?
round(
$totalScore / $totalStudents,
2
)
:
0;

$passRate =
$totalStudents > 0
?
round(
($passedStudents / $totalStudents) * 100,
2
)
:
0;

include '../../app/views/layouts/header.php';

?>

<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">
   

<div class="d-flex justify-content-between mb-4">

<div>

<h2>

📈 Quiz Analytics

</h2>

<h5 class="text-muted">

<?= htmlspecialchars(
$quiz['title']
); ?>

</h5>

</div>

<a
href="results.php?id=<?= $quizId; ?>"
class="btn btn-secondary">

Back To Results

</a>

</div>

<div class="row mb-4">

<div class="col-md-3">

<div class="card text-center">

<div class="card-body">

<h2>

<?= $totalStudents; ?>

</h2>

<p>

Participants

</p>

</div>

</div>

</div>

<div class="col-md-3">

<div class="card text-center">

<div class="card-body">

<h2>

<?= $averageScore; ?>

</h2>

<p>

Average Score

</p>

</div>

</div>

</div>

<div class="col-md-3">

<div class="card text-center">

<div class="card-body">

<h2>

<?= $highestScore; ?>

</h2>

<p>

Highest Score

</p>

</div>

</div>

</div>

<div class="col-md-3">

<div class="card text-center">

<div class="card-body">

<h2>

<?= $passRate; ?>%

</h2>

<p>

Pass Rate

</p>

</div>

</div>

</div>

</div>

<div class="card mb-4">

<div class="card-header">

🏆 Top Students

</div>

<div class="card-body">

<table class="table table-bordered">

<thead>

<tr>

<th>Rank</th>

<th>Student</th>

<th>Score</th>

</tr>

</thead>

<tbody>

<?php

$rank = 1;

foreach(array_slice($results,0,10) as $student):

?>

<tr>

<td>

<?= $rank++; ?>

</td>

<td>

<?= htmlspecialchars(
$student['full_name']
); ?>

</td>

<td>

<?= $student['score']; ?>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

</div>

<div class="card">

<div class="card-header">

⚠ Students Needing Support

</div>

<div class="card-body">

<table class="table table-bordered">

<thead>

<tr>

<th>Student</th>

<th>Score</th>

</tr>

</thead>

<tbody>

<?php

foreach($results as $student):

if(
$student['score']
>=
($quiz['total_marks'] * 0.5)
)
{
    continue;
}

?>

<tr>

<td>

<?= htmlspecialchars(
$student['full_name']
); ?>

</td>

<td>

<?= $student['score']; ?>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

</div>

</div>

</div>

</div>

<?php include '../../app/views/layouts/footer.php'; ?>
