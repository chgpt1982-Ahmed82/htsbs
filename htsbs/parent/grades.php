<?php

session_start();
require_once '../config/config.php';
require_once '../config/database.php';

$db = (new Database())->connect();

$sql = "

SELECT

c.course_name,

g.assessment_type,

g.title,

g.score,

g.max_score

FROM gradebook g

INNER JOIN courses c
ON g.course_id=c.id

INNER JOIN parent_student ps
ON g.student_id=ps.student_id

INNER JOIN parents p
ON ps.parent_id=p.id

WHERE p.user_id=?

ORDER BY g.created_at DESC

";

$stmt = $db->prepare($sql);

$stmt->execute([
$_SESSION['user_id']
]);

$grades =
$stmt->fetchAll(PDO::FETCH_ASSOC);

include '../app/views/layouts/header.php';

?>

<div class="container-fluid">

<div class="row">

<?php include '../app/views/layouts/parent_sidebar.php'; ?>

<div class="col-md-10">
<h2>Grades</h2>

<table class="table table-bordered">

<tr>

<th>Course</th>
<th>Type</th>
<th>Title</th>
<th>Score</th>

</tr>

<?php foreach($grades as $row): ?>

<tr>

<td><?= $row['course_name']; ?></td>

<td><?= $row['assessment_type']; ?></td>

<td><?= $row['title']; ?></td>

<td>

<?= $row['score']; ?>

/

<?= $row['max_score']; ?>

</td>

</tr>

<?php endforeach; ?>

</table>

</div>
</div>

</div>

</div>
<?php include '../app/views/layouts/footer.php'; ?>
