<?php

session_start();

require_once '../config/config.php';
require_once '../config/database.php';

$db = (new Database())->connect();

$classId =
$_GET['class_id'];

$sql = "

SELECT

u.full_name,
u.email,
u.phone,

s.student_number,
s.academic_level

FROM students s

INNER JOIN users u
ON s.user_id=u.id

WHERE s.class_id=?

ORDER BY u.full_name

";

$stmt = $db->prepare($sql);

$stmt->execute([
$classId
]);

$students =
$stmt->fetchAll(PDO::FETCH_ASSOC);

include '../app/views/layouts/header.php';

?>


<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../app/views/layouts/teacher_sidebar.php'; ?>

<div class="col-md-10 p-4">
<h2>Class Students</h2>

<table class="table table-bordered">

<thead>

<tr>

<th>Name</th>
<th>Email</th>
<th>Phone</th>
<th>Student Number</th>
<th>Level</th>

</tr>

</thead>

<tbody>

<?php foreach($students as $student): ?>

<tr>

<td>
<?= htmlspecialchars($student['full_name']); ?>
</td>

<td>
<?= htmlspecialchars($student['email']); ?>
</td>

<td>
<?= htmlspecialchars($student['phone']); ?>
</td>

<td>
<?= htmlspecialchars($student['student_number']); ?>
</td>

<td>
<?= htmlspecialchars($student['academic_level']); ?>
</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

</div>

</div>

<?php include '../app/views/layouts/footer.php'; ?>
