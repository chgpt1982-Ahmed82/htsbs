<?php

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../app/models/BehaviorNote.php';

if(
    !isset($_SESSION['user_id']) ||
    $_SESSION['role_id'] != 3
){
    exit('Unauthorized Access');
}

$db = (new Database())->connect();

$model = new BehaviorNote();

/*
=================================
Student
=================================
*/

$stmt = $db->prepare("
SELECT id
FROM students
WHERE user_id=?
");

$stmt->execute([
    $_SESSION['user_id']
]);

$studentId = $stmt->fetchColumn();

if(!$studentId){
    die('Student Not Found');
}

/*
=================================
Statistics
=================================
*/

$positive =
$model->countPositive(
    $studentId
);

$negative =
$model->countNegative(
    $studentId
);

$warnings =
$model->countWarnings(
    $studentId
);

$total =
$model->countTotal(
    $studentId
);

/*
=================================
Notes
=================================
*/

$notes =
$model->getStudentNotes(
    $studentId
);

include '../../app/views/layouts/header.php';

?>

<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/student_sidebar.php'; ?>
<div class="main-content">


<h2 class="mb-4">

<i class="bi bi-shield-check"></i>

السلوك والانضباط

</h2>

<!-- الإحصائيات -->

<div class="row mb-4">

<div class="col-md-3 mb-3">

<div class="card bg-success text-white">

<div class="card-body text-center">

<h6>

الملاحظات الإيجابية

</h6>

<h2>

<?= $positive; ?>

</h2>

</div>

</div>

</div>

<div class="col-md-3 mb-3">

<div class="card bg-danger text-white">

<div class="card-body text-center">

<h6>

الملاحظات السلبية

</h6>

<h2>

<?= $negative; ?>

</h2>

</div>

</div>

</div>

<div class="col-md-3 mb-3">

<div class="card bg-warning text-dark">

<div class="card-body text-center">

<h6>

التنبيهات

</h6>

<h2>

<?= $warnings; ?>

</h2>

</div>

</div>

</div>

<div class="col-md-3 mb-3">

<div class="card bg-primary text-white">

<div class="card-body text-center">

<h6>

إجمالي الملاحظات

</h6>

<h2>

<?= $total; ?>

</h2>

</div>

</div>

</div>

</div>

<!-- جدول الملاحظات -->

<div class="card shadow">

<div class="card-header bg-primary text-white">

<h5 class="mb-0">

سجل السلوك والانضباط

</h5>

</div>

<div class="card-body">

<?php if(empty($notes)): ?>

<div class="alert alert-info">

لا توجد ملاحظات سلوكية مسجلة

</div>

<?php else: ?>

<div class="table-responsive">

<table class="table table-bordered table-hover align-middle">

<thead class="table-light">

<tr>

<th>التاريخ</th>

<th>النوع</th>

<th>العنوان</th>

<th>التفاصيل</th>

<th>المعلم</th>

<th>الحالة</th>

</tr>

</thead>

<tbody>

<?php foreach($notes as $note): ?>

<tr>

<td>

<?= date(
'd/m/Y',
strtotime(
$note['note_date']
)
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

<?= htmlspecialchars(
$note['title']
); ?>

</td>

<td>

<?= nl2br(
htmlspecialchars(
$note['details']
)
); ?>

</td>

<td>

<?= htmlspecialchars(
$note['teacher_name']
); ?>

</td>

<td>

<?php if($note['is_read']): ?>

<span class="badge bg-success">

مقروءة

</span>

<?php else: ?>

<span class="badge bg-secondary">

غير مقروءة

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

</div>


</div>
<?php include '../../app/views/layouts/footer.php'; ?>

