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
SELECT id
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

/*
====================================
Results
====================================
*/

$stmt = $db->prepare("
SELECT

    a.title,

    c.course_name,

    s.grade,

    s.feedback,

    s.submitted_at

FROM activity_submissions s

INNER JOIN activities a
ON s.activity_id = a.id

INNER JOIN courses c
ON a.course_id = c.id

WHERE s.student_id = ?

ORDER BY s.submitted_at DESC
");

$stmt->execute([
    $studentId
]);

$results =
$stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../app/views/layouts/header.php';

?>

<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/student_sidebar.php'; ?>
<div class="main-content">


<div class="card shadow">

<div class="card-header bg-success text-white">

<h4 class="mb-0">

<i class="bi bi-clipboard-check-fill"></i>

نتائج الأنشطة

</h4>

</div>

<div class="card-body">

<?php if(empty($results)): ?>

<div class="alert alert-info">

لا توجد نتائج أنشطة حتى الآن

</div>

<?php else: ?>

<div class="table-responsive">

<table class="table table-bordered table-hover align-middle">

<thead class="table-success">

<tr>

<th>النشاط</th>

<th>المقرر</th>

<th>الدرجة</th>

<th>الحالة</th>

<th>ملاحظات المعلم</th>

<th>تاريخ التسليم</th>

</tr>

</thead>

<tbody>

<?php foreach($results as $result): ?>

<tr>

<td>

<?= htmlspecialchars(
$result['title']
); ?>

</td>

<td>

<?= htmlspecialchars(
$result['course_name']
); ?>

</td>

<td>

<?php

if($result['grade'] !== null)
{
    echo $result['grade'];
}
else
{
    echo '-';
}

?>

</td>

<td>

<?php if($result['grade'] !== null): ?>

<span class="badge bg-success">

تم التصحيح

</span>

<?php else: ?>

<span class="badge bg-warning text-dark">

بانتظار التصحيح

</span>

<?php endif; ?>

</td>

<td>

<?= !empty($result['feedback'])
? nl2br(
htmlspecialchars(
$result['feedback']
)
)
: '-'; ?>

</td>

<td>

<?= date(
'd/m/Y H:i',
strtotime(
$result['submitted_at']
)
); ?>

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