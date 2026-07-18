<?php

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';

if(
    !isset($_SESSION['user_id'])
    || $_SESSION['role_id'] != 3
){
    exit('Unauthorized Access');
}

$db = (new Database())->connect();

/*
====================================
Student
====================================
*/

$stmt = $db->prepare("
SELECT
    id,
    class_id
FROM students
WHERE user_id=?
");

$stmt->execute([
    $_SESSION['user_id']
]);

$student = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$student)
{
    die('Student Not Found');
}

$studentId = $student['id'];
$classId   = $student['class_id'];

/*
====================================
Assigned Exams
====================================
*/

$stmt = $db->prepare("
SELECT

    e.id,
    e.exam_name,
    e.exam_type,
    e.exam_date,
    e.max_marks,

    c.course_name,

    (
        SELECT marks
        FROM exam_results er
        WHERE er.exam_id = e.id
        AND er.student_id = ?
        LIMIT 1
    ) AS student_mark

FROM exam_assignments ea

INNER JOIN exams e
ON ea.exam_id = e.id

INNER JOIN courses c
ON e.course_id = c.id

WHERE ea.class_id = ?

ORDER BY e.exam_date DESC
");

$stmt->execute([
    $studentId,
    $classId
]);

$exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../app/views/layouts/header.php';

?>

<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/student_sidebar.php'; ?>
<div class="main-content">


<div class="d-flex justify-content-between align-items-center mb-4">

<h2>

<i class="bi bi-pencil-square"></i>

الاختبارات

</h2>

<span class="badge bg-primary">

<?= count($exams); ?>

اختبار

</span>

</div>

<?php if(empty($exams)): ?>

<div class="alert alert-info">

لا توجد اختبارات متاحة حالياً

</div>

<?php else: ?>

<div class="table-responsive">

<table class="table table-bordered table-hover align-middle">

<thead class="table-primary">

<tr>

<th>الاختبار</th>

<th>المقرر</th>

<th>النوع</th>

<th>التاريخ</th>

<th>الدرجة النهائية</th>

<th>درجتي</th>

</tr>

</thead>

<tbody>

<?php foreach($exams as $exam): ?>

<tr>

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

<?= date(
'd/m/Y',
strtotime($exam['exam_date'])
); ?>

</td>

<td>

<?= $exam['max_marks']; ?>

</td>

<td>

<?php if($exam['student_mark'] !== null): ?>

<span class="badge bg-success">

<?= $exam['student_mark']; ?>

</span>

<?php else: ?>

<span class="badge bg-warning text-dark">

بانتظار التصحيح

</span>

<?php endif; ?>

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
<?php include '../../app/views/layouts/footer.php'; ?>