<?php

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../app/models/Notification.php';

if(
    !isset($_SESSION['user_id']) ||
    $_SESSION['role_id'] != 3
){
    exit('Unauthorized Access');
}

$db = (new Database())->connect();

$notificationModel =
new Notification();

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

$student =
$stmt->fetch(PDO::FETCH_ASSOC);

if(!$student)
{
    die('Student Not Found');
}

$classId = $student['class_id'];

/*
====================================
Assignments
====================================
*/

$stmt = $db->prepare("
SELECT

    a.id,
    a.title,
    a.description,
    a.due_date,
    a.file_path,
    a.created_at,

    c.course_name

FROM assignment_assignments aa

INNER JOIN assignments a
ON aa.assignment_id = a.id

INNER JOIN courses c
ON a.course_id = c.id

WHERE aa.class_id = ?

ORDER BY a.created_at DESC
");

$stmt->execute([
    $classId
]);

$assignments =
$stmt->fetchAll(PDO::FETCH_ASSOC);

$count =
$notificationModel->unreadCount(
$_SESSION['user_id']
);

include '../../app/views/layouts/header.php';

?>

<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/student_sidebar.php'; ?>
<div class="main-content">


<div class="d-flex justify-content-between align-items-center mb-4">

<h2>

<i class="bi bi-file-earmark-text"></i>

واجباتي

</h2>

<span class="badge bg-primary">

<?= count($assignments); ?>

واجب

</span>

</div>

<?php if(empty($assignments)): ?>

<div class="alert alert-warning">

لا توجد واجبات متاحة حالياً

</div>

<?php else: ?>

<div class="table-responsive">

<table class="table table-bordered table-hover align-middle">

<thead class="table-primary">

<tr>

<th>عنوان الواجب</th>

<th>المقرر</th>

<th>تاريخ التسليم</th>

<th>الملف</th>

<th>الإجراء</th>

</tr>

</thead>

<tbody>

<?php foreach($assignments as $assignment): ?>

<tr>

<td>

<?= htmlspecialchars(
$assignment['title']
); ?>

</td>

<td>

<?= htmlspecialchars(
$assignment['course_name']
); ?>

</td>

<td>

<?= !empty($assignment['due_date'])
? date(
'd/m/Y',
strtotime($assignment['due_date'])
)
: '-'; ?>

</td>

<td>

<?php if(!empty($assignment['file_path'])): ?>

<a
href="<?= BASE_URL . '/' . $assignment['file_path']; ?>"
target="_blank"
class="btn btn-info btn-sm">

<i class="bi bi-download"></i>

تحميل

</a>

<?php else: ?>

<span class="text-muted">
لا يوجد ملف
</span>

<?php endif; ?>

</td>

<td>

<a
href="submit.php?id=<?= $assignment['id']; ?>"
class="btn btn-success btn-sm">

<i class="bi bi-upload"></i>

رفع الحل

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
<?php include '../../app/views/layouts/footer.php'; ?>
