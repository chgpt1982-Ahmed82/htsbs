<?php

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';

$db = (new Database())->connect();

/*
الحصول على الطالب الحالي
*/

$stmt = $db->prepare("
SELECT id
FROM students
WHERE user_id = ?
");

$stmt->execute([
    $_SESSION['user_id']
]);

$student = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$student){
    die('Student Not Found');
}

$studentId = $student['id'];

/*
إحصائيات الحضور
*/

$sql = "
SELECT

SUM(CASE WHEN status='Present' THEN 1 ELSE 0 END) AS present_count,

SUM(CASE WHEN status='Absent' THEN 1 ELSE 0 END) AS absent_count,

SUM(CASE WHEN status='Late' THEN 1 ELSE 0 END) AS late_count,

SUM(CASE WHEN status='Excused' THEN 1 ELSE 0 END) AS excused_count,

COUNT(*) AS total_days

FROM attendance

WHERE student_id = ?
";

$stmt = $db->prepare($sql);

$stmt->execute([
    $studentId
]);

$stats = $stmt->fetch(PDO::FETCH_ASSOC);

/*
سجل الحضور
*/

$sql = "
SELECT

a.attendance_date,
a.status,
a.notes,
c.course_name,
cl.class_name

FROM attendance a

LEFT JOIN courses c
ON a.course_id = c.id

LEFT JOIN classes cl
ON a.class_id = cl.id

WHERE a.student_id = ?

ORDER BY a.attendance_date DESC
";

$stmt = $db->prepare($sql);

$stmt->execute([
    $studentId
]);

$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

$attendancePercentage = 0;

if($stats['total_days'] > 0){
    $attendancePercentage =
    round(
        ($stats['present_count'] / $stats['total_days']) * 100,
        2
    );
}

include '../../app/views/layouts/header.php';
?>

<div class="container-fluid">
<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/student_sidebar.php'; ?>
<div class="main-content">


<h2 class="mb-4">

<i class="bi bi-person-check-fill text-success"></i>

الحضور والغياب

</h2>

<div class="row mb-4">

<div class="col-md-3">
<div class="card text-center">
<div class="card-body">
<h5 class="text-success">حاضر</h5>
<h2><?= $stats['present_count'] ?? 0; ?></h2>
</div>
</div>
</div>

<div class="col-md-3">
<div class="card text-center">
<div class="card-body">
<h5 class="text-danger">غائب</h5>
<h2><?= $stats['absent_count'] ?? 0; ?></h2>
</div>
</div>
</div>

<div class="col-md-2">
<div class="card text-center">
<div class="card-body">
<h5 class="text-warning">متأخر</h5>
<h2><?= $stats['late_count'] ?? 0; ?></h2>
</div>
</div>
</div>

<div class="col-md-2">
<div class="card text-center">
<div class="card-body">
<h5 class="text-info">بعذر</h5>
<h2><?= $stats['excused_count'] ?? 0; ?></h2>
</div>
</div>
</div>

<div class="col-md-2">
<div class="card text-center">
<div class="card-body">
<h5 class="text-primary">النسبة</h5>
<h2><?= $attendancePercentage; ?>%</h2>
</div>
</div>
</div>

</div>

<div class="card">

<div class="card-header bg-primary text-white">

سجل الحضور

</div>

<div class="card-body">

<?php if(empty($records)): ?>

<div class="alert alert-info">

لا توجد سجلات حضور.

</div>

<?php else: ?>

<div class="table-responsive">

<table class="table table-bordered table-striped">

<thead>

<tr>

<th>التاريخ</th>
<th>المقرر</th>
<th>الشعبة</th>
<th>الحالة</th>
<th>ملاحظات</th>

</tr>

</thead>

<tbody>

<?php foreach($records as $row): ?>

<tr>

<td>
<?= $row['attendance_date']; ?>
</td>

<td>
<?= htmlspecialchars($row['course_name'] ?? '-'); ?>
</td>

<td>
<?= htmlspecialchars($row['class_name'] ?? '-'); ?>
</td>

<td>

<?php
switch($row['status']){

case 'Present':
echo '<span class="badge bg-success">حاضر</span>';
break;

case 'Absent':
echo '<span class="badge bg-danger">غائب</span>';
break;

case 'Late':
echo '<span class="badge bg-warning">متأخر</span>';
break;

case 'Excused':
echo '<span class="badge bg-info">بعذر</span>';
break;
}
?>

</td>

<td>
<?= htmlspecialchars($row['notes'] ?? ''); ?>
</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

<?php endif; ?>

</div>

</div>

</div>


</div>
</div>
<?php include '../../app/views/layouts/footer.php'; ?>