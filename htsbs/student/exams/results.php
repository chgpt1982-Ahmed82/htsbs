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

    er.marks,
    er.remarks,
    er.created_at,

    e.exam_name,
    e.exam_type,
    e.max_marks,
    e.exam_date,

    c.course_name

FROM exam_results er

INNER JOIN exams e
ON er.exam_id = e.id

INNER JOIN courses c
ON e.course_id = c.id

WHERE er.student_id=?

ORDER BY e.exam_date DESC
");

$stmt->execute([
    $studentId
]);

$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
====================================
Average
====================================
*/

$totalMarks = 0;
$totalMax   = 0;

foreach($results as $row)
{
    $totalMarks += $row['marks'];
    $totalMax   += $row['max_marks'];
}

$average =
$totalMax > 0
? round(($totalMarks / $totalMax) * 100,2)
: 0;

include '../../app/views/layouts/header.php';

?>

<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/student_sidebar.php'; ?>
<div class="main-content">


<div class="d-flex justify-content-between align-items-center mb-4">

<h2>

<i class="bi bi-clipboard-data-fill"></i>

نتائج الاختبارات

</h2>

<span class="badge bg-success">

المعدل

<?= $average; ?>%

</span>

</div>

<?php if(empty($results)): ?>

<div class="alert alert-info">

لا توجد نتائج اختبارات حالياً

</div>

<?php else: ?>

<div class="table-responsive">

<table class="table table-bordered table-hover align-middle">

<thead class="table-dark">

<tr>

<th>الاختبار</th>

<th>المقرر</th>

<th>النوع</th>

<th>الدرجة</th>

<th>الدرجة النهائية</th>

<th>النسبة</th>

<th>الملاحظات</th>

</tr>

</thead>

<tbody>

<?php foreach($results as $result): ?>

<?php

$percentage =
$result['max_marks'] > 0
? round(
($result['marks'] / $result['max_marks']) * 100,
2
)
: 0;

?>

<tr>

<td>

<?= htmlspecialchars(
$result['exam_name']
); ?>

</td>

<td>

<?= htmlspecialchars(
$result['course_name']
); ?>

</td>

<td>

<?= htmlspecialchars(
$result['exam_type']
); ?>

</td>

<td>

<span class="badge bg-primary">

<?= $result['marks']; ?>

</span>

</td>

<td>

<?= $result['max_marks']; ?>

</td>

<td>

<?php if($percentage >= 50): ?>

<span class="badge bg-success">

<?= $percentage; ?>%

</span>

<?php else: ?>

<span class="badge bg-danger">

<?= $percentage; ?>%

</span>

<?php endif; ?>

</td>

<td>

<?= htmlspecialchars(
$result['remarks'] ?? '-'
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
<?php include '../../app/views/layouts/footer.php'; ?>