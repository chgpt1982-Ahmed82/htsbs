<?php

session_start();
require_once '../config/config.php';
require_once '../config/database.php';

$db = (new Database())->connect();

$sql = "

SELECT

q.title,

qa.score,

qa.total_questions,

qa.submitted_at

FROM quiz_attempts qa

INNER JOIN quizzes q
ON qa.quiz_id=q.id

INNER JOIN parent_student ps
ON qa.student_id=ps.student_id

INNER JOIN parents p
ON ps.parent_id=p.id

WHERE p.user_id=?

ORDER BY qa.submitted_at DESC

";

$stmt = $db->prepare($sql);

$stmt->execute([
$_SESSION['user_id']
]);

$results =
$stmt->fetchAll(PDO::FETCH_ASSOC);

include '../app/views/layouts/header.php';

?>

<div class="container-fluid">

<div class="row">

<?php include '../app/views/layouts/parent_sidebar.php'; ?>

<div class="col-md-10">

<h2>Quiz Results</h2>

<table class="table table-bordered">

<tr>

<th>Quiz</th>
<th>Score %</th>
<th>Questions</th>
<th>Date</th>

</tr>

<?php foreach($results as $row): ?>

<tr>

<td><?= $row['title']; ?></td>

<td><?= $row['score']; ?> %</td>

<td><?= $row['total_questions']; ?></td>

<td><?= $row['submitted_at']; ?></td>

</tr>

<?php endforeach; ?>

</table>

</div>
</div>

</div>

</div>
<?php include '../app/views/layouts/footer.php'; ?>
