<?php

session_start();

require_once '../config/config.php';
require_once '../config/database.php';

$db = (new Database())->connect();

$sql = "

SELECT DISTINCT

c.id,
c.class_name,

COUNT(s.id) AS total_students

FROM course_assignments ca

INNER JOIN teachers t
ON ca.teacher_id=t.id

INNER JOIN classes c
ON ca.class_id=c.id

LEFT JOIN students s
ON s.class_id=c.id

WHERE t.user_id=?

GROUP BY c.id

ORDER BY c.class_name

";

$stmt = $db->prepare($sql);

$stmt->execute([
$_SESSION['user_id']
]);

$classes =
$stmt->fetchAll(PDO::FETCH_ASSOC);

include '../app/views/layouts/header.php';

?>


<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">
<h2>My Classes</h2>

<table class="table table-bordered">

<thead>

<tr>

<th>Class</th>
<th>Students</th>
<th>Action</th>

</tr>

</thead>

<tbody>

<?php foreach($classes as $class): ?>

<tr>

<td>
<?= htmlspecialchars($class['class_name']); ?>
</td>

<td>
<?= $class['total_students']; ?>
</td>

<td>

<a
href="students.php?class_id=<?= $class['id']; ?>"
class="btn btn-primary btn-sm">

View Students

</a>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

</div>

</div>

<?php include '../app/views/layouts/footer.php'; ?>
