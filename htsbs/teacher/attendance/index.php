<?php

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';

$db = (new Database())->connect();

$sql = "

SELECT DISTINCT

c.id,
c.class_name

FROM course_assignments ca

INNER JOIN teachers t
ON ca.teacher_id=t.id

INNER JOIN classes c
ON ca.class_id=c.id

WHERE t.user_id=?

ORDER BY c.class_name

";

$stmt = $db->prepare($sql);

$stmt->execute([
$_SESSION['user_id']
]);

$classes =
$stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../app/views/layouts/header.php';

?>

<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">
   
<h2 dir="ltr">Attendance</h2>

<table class="table table-bordered">

<tr>

<th>الشعبة</th>
<th width="300">الإجراءات</th>

</tr>

<?php foreach($classes as $class): ?>

<tr>

<td>

<?= htmlspecialchars($class['class_name']); ?>

</td>

<td>

<a
href="mark.php?class_id=<?= $class['id']; ?>"
class="btn btn-success btn-sm">

📝 أخذ الحضور

</a>

<a
href="report.php?class_id=<?= $class['id']; ?>"
class="btn btn-primary btn-sm">

📊 تقرير الحضور

</a>

</td>

</tr>

<?php endforeach; ?>

</table>

</div>

</div>

</div>

<?php include '../../app/views/layouts/footer.php'; ?>
