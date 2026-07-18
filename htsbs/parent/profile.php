<?php

session_start();
require_once '../config/config.php';
require_once '../config/database.php';

$db = (new Database())->connect();

$sql = "

SELECT

u.full_name,

s.student_number,

s.academic_level,

s.gpa,

c.class_name

FROM parent_student ps

INNER JOIN parents p
ON ps.parent_id=p.id

INNER JOIN students s
ON ps.student_id=s.id

INNER JOIN users u
ON s.user_id=u.id

LEFT JOIN classes c
ON s.class_id=c.id

WHERE p.user_id=?

LIMIT 1

";

$stmt = $db->prepare($sql);

$stmt->execute([
$_SESSION['user_id']
]);

$student =
$stmt->fetch(PDO::FETCH_ASSOC);

include '../app/views/layouts/header.php';

?>

<div class="container-fluid">

<div class="row">

<?php include '../app/views/layouts/parent_sidebar.php'; ?>

<div class="col-md-10">

<h2>Student Profile</h2>

<div class="card">

<div class="card-body">

<p>

<strong>Name:</strong>

<?= $student['full_name']; ?>

</p>

<p>

<strong>Student Number:</strong>

<?= $student['student_number']; ?>

</p>

<p>

<strong>Class:</strong>

<?= $student['class_name']; ?>

</p>

<p>

<strong>Level:</strong>

<?= $student['academic_level']; ?>

</p>

<p>

<strong>GPA:</strong>

<?= $student['gpa']; ?>

</p>

</div>

</div>

</div>
</div>

</div>

</div>
<?php include '../app/views/layouts/footer.php'; ?>
