<?php

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';

$db = (new Database())->connect();

$sql = "

SELECT

ca.id,
c.course_name,
cl.class_name,
ca.course_id,
ca.class_id

FROM course_assignments ca

INNER JOIN teachers t
ON ca.teacher_id=t.id

INNER JOIN courses c
ON ca.course_id=c.id

INNER JOIN classes cl
ON ca.class_id=cl.id

WHERE t.user_id=?

ORDER BY c.course_name

";

$stmt = $db->prepare($sql);

$stmt->execute([
$_SESSION['user_id']
]);

$assignments =
$stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../app/views/layouts/header.php';

?>

<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">   
<h2>Gradebook</h2>

<table class="table table-bordered">

<tr>

<th>Course</th>
<th>Class</th>
<th>Actions</th>

</tr>

<?php foreach($assignments as $item): ?>

<tr>

<td>
<?= htmlspecialchars($item['course_name']); ?>
</td>

<td>
<?= htmlspecialchars($item['class_name']); ?>
</td>

<td>

<a
href="create.php?course_id=<?= $item['course_id']; ?>&class_id=<?= $item['class_id']; ?>"
class="btn btn-success btn-sm">

Enter Grades

</a>

<a
href="report.php?course_id=<?= $item['course_id']; ?>&class_id=<?= $item['class_id']; ?>"
class="btn btn-primary btn-sm">

View Report

</a>

</td>

</tr>

<?php endforeach; ?>

</table>

</div>

</div>

</div>

<?php include '../../app/views/layouts/footer.php'; ?>