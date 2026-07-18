<?php

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';

$db = (new Database())->connect();

$classId = $_GET['class_id'];

$sql = "

SELECT

MAX(a.id) AS attendance_id,

u.full_name,

s.student_number,

SUM(
CASE
WHEN a.status='Present'
THEN 1
ELSE 0
END
) AS present_count,

SUM(
CASE
WHEN a.status='Absent'
THEN 1
ELSE 0
END
) AS absent_count,

SUM(
CASE
WHEN a.status='Late'
THEN 1
ELSE 0
END
) AS late_count,
SUM(
CASE
WHEN a.status='Excused'
THEN 1
ELSE 0
END
) AS excused_count,
COUNT(a.id) AS total_days

FROM attendance a

INNER JOIN students s
ON a.student_id=s.id

INNER JOIN users u
ON s.user_id=u.id

WHERE a.class_id=?

GROUP BY s.id

ORDER BY u.full_name

";

$stmt = $db->prepare($sql);

$stmt->execute([
$classId
]);

$results =
$stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../app/views/layouts/header.php';

?>
<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">   
   
<h2 dir="rtl">تقرير الحضور </h2>

<table class="table table-bordered table-striped">

<thead>

<tr>

<th>Student</th>
<th>Number</th>
<th>Present</th>
<th>Absent</th>
<th>Late</th>
<th>Excused</th>
<th>Attendance %</th>
<th>Actions</th>

</tr>

</thead>

<tbody>

<?php foreach($results as $row): ?>

<tr>

<td>
<?= htmlspecialchars($row['full_name']); ?>
</td>

<td>
<?= htmlspecialchars($row['student_number']); ?>
</td>

<td>
<?= $row['present_count']; ?>
</td>

<td>
<?= $row['absent_count']; ?>
</td>

<td>
<?= $row['late_count']; ?>
</td>
<td><?= $row['excused_count']; ?></td>
<?php
$attendancePercent =
($row['total_days'] > 0)
?
round(
($row['present_count'] / $row['total_days']) * 100,
2
)
: 0;
?>
<td><?= $attendancePercent; ?>%</td>
<td>

<a
href="edit.php?attendance_id=<?= $row['attendance_id']; ?>"
class="btn btn-warning btn-sm">

Edit

</a>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

</div>

</div>

<?php include '../../app/views/layouts/footer.php'; ?>
