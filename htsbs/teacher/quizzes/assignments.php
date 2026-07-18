<?php

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../app/models/QuizAssignment.php';

if(
    !isset($_SESSION['user_id']) ||
    $_SESSION['role_id'] != 2
){
    exit('Unauthorized Access');
}

$db = (new Database())->connect();

$stmt = $db->prepare("
SELECT id
FROM teachers
WHERE user_id=?
");

$stmt->execute([
    $_SESSION['user_id']
]);

$teacherId = $stmt->fetchColumn();

$model = new QuizAssignment();

$assignments =
$model->getAssignments(
    $teacherId
);

include '../../app/views/layouts/header.php';

?>

<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">
<div class="card shadow">

<div class="card-header bg-dark text-white d-flex justify-content-between">

<h4 class="mb-0">

📋 الاختبارات المعينة

</h4>

<a
href="assign.php"
class="btn btn-success btn-sm">

<i class="bi bi-plus-circle"></i>

تعيين جديد

</a>

</div>

<div class="card-body">

<div class="table-responsive">

<table
class="table table-bordered table-hover align-middle">

<thead class="table-dark">

<tr>

<th>#</th>

<th>الاختبار</th>

<th>المقرر</th>

<th>رمز المقرر</th>

<th>الشعبة</th>

<th>السنة</th>

<th>الفصل</th>

<th>تاريخ التعيين</th>

</tr>

</thead>

<tbody>

<?php if(empty($assignments)): ?>

<tr>

<td
colspan="8"
class="text-center">

لا توجد تعيينات

</td>

</tr>

<?php endif; ?>

<?php foreach($assignments as $i=>$row): ?>

<tr>

<td>

<?= $i + 1; ?>

</td>

<td>

<?= htmlspecialchars(
$row['title']
); ?>

</td>

<td>

<?= htmlspecialchars(
$row['course_name']
); ?>

</td>

<td>

<?= htmlspecialchars(
$row['course_code']
); ?>

</td>

<td>

<?= htmlspecialchars(
$row['class_name']
); ?>

</td>

<td>

<?= htmlspecialchars(
$row['academic_year']
); ?>

</td>

<td>

<?= htmlspecialchars(
$row['semester']
); ?>

</td>

<td>

<?= date(
'd/m/Y',
strtotime(
$row['assigned_at']
)
); ?>

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