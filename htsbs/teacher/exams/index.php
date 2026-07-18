<?php

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';

if(
    !isset($_SESSION['user_id'])
    || $_SESSION['role_id'] != 2
){
    exit('Unauthorized Access');
}

$db = (new Database())->connect();

/*
====================================
Teacher
====================================
*/

$stmt = $db->prepare("
SELECT id
FROM teachers
WHERE user_id=?
");

$stmt->execute([
    $_SESSION['user_id']
]);

$teacher = $stmt->fetch(PDO::FETCH_ASSOC);

$teacherId = $teacher['id'] ?? 0;

/*
====================================
Exams
====================================
*/

$stmt = $db->prepare("
SELECT

    e.*,

    c.course_name

FROM exams e

INNER JOIN courses c
ON e.course_id = c.id

WHERE e.teacher_id=?

ORDER BY e.exam_date DESC
");

$stmt->execute([
    $teacherId
]);

$exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../app/views/layouts/header.php';

?>

<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">
<div class="d-flex justify-content-between align-items-center mb-4">

<h2>

<i class="bi bi-pencil-square"></i>

إدارة الاختبارات

</h2>

<a
href="create.php"
class="btn btn-primary">

<i class="bi bi-plus-circle"></i>

إضافة اختبار

</a>

</div>

<?php if(isset($_SESSION['success'])): ?>

<div class="alert alert-success">

<?= $_SESSION['success']; ?>

</div>

<?php unset($_SESSION['success']); ?>

<?php endif; ?>

<div class="card shadow">

<div class="card-body">

<?php if(empty($exams)): ?>

<div class="alert alert-info">

لا توجد اختبارات حالياً

</div>

<?php else: ?>

<div class="table-responsive">

<table class="table table-bordered table-hover align-middle">

<thead class="table-dark">

<tr>

<th>#</th>

<th>اسم الاختبار</th>

<th>المقرر</th>

<th>النوع</th>

<th>التاريخ</th>

<th>الدرجة</th>

<th width="280">الإجراءات</th>

</tr>

</thead>

<tbody>

<?php foreach($exams as $exam): ?>

<tr>

<td>

<?= $exam['id']; ?>

</td>

<td>

<?= htmlspecialchars(
$exam['exam_name']
); ?>

</td>

<td>

<?= htmlspecialchars(
$exam['course_name']
); ?>

</td>

<td>

<?= htmlspecialchars(
$exam['exam_type']
); ?>

</td>

<td>

<?= htmlspecialchars(
$exam['exam_date']
); ?>

</td>

<td>

<?= $exam['max_marks']; ?>

</td>

<td>

<a
href="assign.php?id=<?= $exam['id']; ?>"
class="btn btn-success btn-sm">

<i class="bi bi-diagram-3"></i>

تعيين

</a>

<a
href="edit.php?id=<?= $exam['id']; ?>"
class="btn btn-warning btn-sm">

<i class="bi bi-pencil"></i>

تعديل

</a>

<a
href="results.php?id=<?= $exam['id']; ?>"
class="btn btn-info btn-sm">

<i class="bi bi-bar-chart"></i>

النتائج

</a>

<a
href="delete.php?id=<?= $exam['id']; ?>"
class="btn btn-danger btn-sm"
onclick="return confirm('هل أنت متأكد؟')">

<i class="bi bi-trash"></i>

حذف

</a>

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