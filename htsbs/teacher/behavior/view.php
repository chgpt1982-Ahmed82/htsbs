<?php

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';

if(
    !isset($_SESSION['user_id']) ||
    $_SESSION['role_id'] != 2
){
    exit('Unauthorized Access');
}

if(!isset($_GET['student_id']))
{
    die('Student Not Found');
}

$db = (new Database())->connect();

$studentId = (int)$_GET['student_id'];

/*
=================================
Student Info
=================================
*/

$stmt = $db->prepare("
SELECT

    s.id,
    s.student_number,

    u.full_name,

    c.class_name

FROM students s

INNER JOIN users u
ON s.user_id = u.id

LEFT JOIN classes c
ON s.class_id = c.id

WHERE s.id = ?
");

$stmt->execute([
    $studentId
]);

$student = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$student)
{
    die('Student Not Found');
}

/*
=================================
Statistics
=================================
*/

$stmt = $db->prepare("
SELECT

COUNT(*) AS total_notes,

SUM(
CASE
WHEN note_type='positive'
THEN 1
ELSE 0
END
) AS positive_count,

SUM(
CASE
WHEN note_type='negative'
THEN 1
ELSE 0
END
) AS negative_count,

SUM(
CASE
WHEN note_type='warning'
THEN 1
ELSE 0
END
) AS warning_count

FROM behavior_notes

WHERE student_id=?
");

$stmt->execute([
    $studentId
]);

$stats = $stmt->fetch(PDO::FETCH_ASSOC);

/*
=================================
Notes
=================================
*/

$stmt = $db->prepare("
SELECT

b.*,

u.full_name AS teacher_name

FROM behavior_notes b

INNER JOIN teachers t
ON b.teacher_id = t.id

INNER JOIN users u
ON t.user_id = u.id

WHERE b.student_id=?

ORDER BY b.note_date DESC
");

$stmt->execute([
    $studentId
]);

$notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../app/views/layouts/header.php';

?>

<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">
<div class="d-flex justify-content-between mb-4">

<h2>

📋 سجل السلوك الطلابي

</h2>

<a
href="report.php"
class="btn btn-secondary">

رجوع

</a>

</div>

<div class="card shadow mb-4">

<div class="card-header bg-primary text-white">

بيانات الطالب

</div>

<div class="card-body">

<div class="row">

<div class="col-md-4">

<strong>اسم الطالب</strong>

<br>

<?= htmlspecialchars($student['full_name']); ?>

</div>

<div class="col-md-4">

<strong>الرقم الأكاديمي</strong>

<br>

<?= htmlspecialchars($student['student_number']); ?>

</div>

<div class="col-md-4">

<strong>الصف</strong>

<br>

<?= htmlspecialchars($student['class_name']); ?>

</div>

</div>

</div>

</div>

<div class="row mb-4">

<div class="col-md-3">

<div class="card bg-success text-white">

<div class="card-body text-center">

<h6>

إيجابية

</h6>

<h2>

<?= $stats['positive_count'] ?? 0; ?>

</h2>

</div>

</div>

</div>

<div class="col-md-3">

<div class="card bg-danger text-white">

<div class="card-body text-center">

<h6>

سلبية

</h6>

<h2>

<?= $stats['negative_count'] ?? 0; ?>

</h2>

</div>

</div>

</div>

<div class="col-md-3">

<div class="card bg-warning text-dark">

<div class="card-body text-center">

<h6>

تنبيهات

</h6>

<h2>

<?= $stats['warning_count'] ?? 0; ?>

</h2>

</div>

</div>

</div>

<div class="col-md-3">

<div class="card bg-primary text-white">

<div class="card-body text-center">

<h6>

الإجمالي

</h6>

<h2>

<?= $stats['total_notes'] ?? 0; ?>

</h2>

</div>

</div>

</div>

</div>

<div class="card shadow">

<div class="card-header bg-dark text-white">

جميع الملاحظات السلوكية

</div>

<div class="card-body">

<div class="table-responsive">

<table class="table table-bordered table-hover">

<thead class="table-dark">

<tr>

<th>#</th>

<th>التاريخ</th>

<th>النوع</th>

<th>العنوان</th>

<th>التفاصيل</th>

<th>المعلم</th>

</tr>

</thead>

<tbody>

<?php if(empty($notes)): ?>

<tr>

<td colspan="6" class="text-center">

لا توجد ملاحظات

</td>

</tr>

<?php endif; ?>

<?php foreach($notes as $index => $note): ?>

<tr>

<td>

<?= $index + 1; ?>

</td>

<td>

<?= date(
'd/m/Y',
strtotime($note['note_date'])
); ?>

</td>

<td>

<?php if($note['note_type']=='positive'): ?>

<span class="badge bg-success">

إيجابية

</span>

<?php elseif($note['note_type']=='negative'): ?>

<span class="badge bg-danger">

سلبية

</span>

<?php else: ?>

<span class="badge bg-warning text-dark">

تنبيه

</span>

<?php endif; ?>

</td>

<td>

<?= htmlspecialchars($note['title']); ?>

</td>

<td>

<?= nl2br(
htmlspecialchars(
$note['details']
)
); ?>

</td>

<td>

<?= htmlspecialchars($note['teacher_name']); ?>

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

</div>

<?php include '../../app/views/layouts/footer.php'; ?>