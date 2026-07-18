<?php

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../app/models/BehaviorNote.php';

if(
    !isset($_SESSION['user_id']) ||
    $_SESSION['role_id'] != 2
){
    exit('Unauthorized Access');
}

$db = (new Database())->connect();

$model = new BehaviorNote();

/*
=================================
Teacher
=================================
*/

$stmt = $db->prepare("
SELECT id
FROM teachers
WHERE user_id=?
");

$stmt->execute([
    $_SESSION['user_id']
]);

$teacherId = $stmt->fetchColumn();

if(!$teacherId){
    die('Teacher Not Found');
}

/*
=================================
Notes
=================================
*/

$notes =
$model->getTeacherNotes(
    $teacherId
);

include '../../app/views/layouts/header.php';

?>

<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">
    
    <div class="d-flex justify-content-between align-items-center mb-4">

<h2>
    <i class="bi bi-journal-text"></i>
    الملاحظات السلوكية
</h2>

<div class="d-flex justify-content-between mb-4">

  
    <div>

        <a href="create.php"
           class="btn btn-success">

            إضافة ملاحظة

        </a>

        <a href="report.php"
           class="btn btn-info">

            تقرير السلوك

        </a>

    </div>

</div>
</div>

<?php if(isset($_SESSION['success'])): ?>

<div class="alert alert-success">

<?= $_SESSION['success']; ?>

</div>

<?php unset($_SESSION['success']); ?>

<?php endif; ?>

<div class="card shadow">

<div class="card-body">

<?php if(empty($notes)): ?>

<div class="alert alert-info">

لا توجد ملاحظات سلوكية

</div>

<?php else: ?>

<div class="table-responsive">

<table class="table table-bordered table-hover align-middle">

<thead class="table-dark">

<tr>

<th>#</th>

<th>الطالب</th>

<th>الرقم الأكاديمي</th>
<th>الصف</th>
<th>النوع</th>

<th>العنوان</th>

<th>التاريخ</th>

<th>الحالة</th>

<th width="180">الإجراءات</th>

</tr>

</thead>

<tbody>

<?php
$counter=1;
foreach($notes as $note): ?>

<tr>
<td>
<?= $counter++; ?>
</td>

<td>

<?= htmlspecialchars(
$note['full_name']
); ?>

</td>

<td>

<?= htmlspecialchars(
$note['student_number']
); ?>

</td>
<td>

<?= htmlspecialchars(
$note['class_name']
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

<td class="text-nowrap">

<?= date(
'd/m/Y',
strtotime($note['created_at'])
); ?>

<br>

<small class="text-muted">

<?= date(
'h:i A',
strtotime($note['created_at'])
); ?>

</small>

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

<td>
<!-- 
   <a
   href="view.php?id=<?= $note['id']; ?>"
   class="btn btn-info btn-sm">

   <i class="bi bi-eye"></i>

 عرض     </a>
-->
<a
href="edit.php?id=<?= $note['id']; ?>"
class="btn btn-warning btn-sm">

<i class="bi bi-pencil"></i> 
<!-- تعديل -->
</a>

<a
href="delete.php?id=<?= $note['id']; ?>"
class="btn btn-danger btn-sm"
onclick="return confirm('هل أنت متأكد من الحذف؟')">

<i class="bi bi-trash"></i>



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