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

$classId = $student['class_id'];

/*
====================================
Activities
====================================
*/

$stmt = $db->prepare("
SELECT

    a.id,
    a.title,
    a.instructions,
    a.max_grade,
    a.due_date,
    a.created_at,
    c.course_name

FROM activity_assignments aa

INNER JOIN activities a
ON aa.activity_id = a.id

INNER JOIN courses c
ON a.course_id = c.id

WHERE aa.class_id = ?

ORDER BY a.created_at DESC
");

$stmt->execute([
    $classId
]);

$activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../app/views/layouts/header.php';

?>

<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/student_sidebar.php'; ?>
<div class="main-content">


<div class="d-flex justify-content-between align-items-center mb-4">

<h2>
<i class="bi bi-list-task"></i>
أنشطتي
</h2>

<span class="badge bg-primary">
<?= count($activities); ?>
نشاط
</span>

</div>

<?php if(empty($activities)): ?>

<div class="alert alert-info">
لا توجد أنشطة متاحة حالياً
</div>

<?php else: ?>

<div class="table-responsive">

<table class="table table-bordered table-hover align-middle">

<thead class="table-primary">

<tr>
<th>النشاط</th>
<th>المقرر</th>
<th>الدرجة</th>
<th>تاريخ التسليم</th>
<th>التفاصيل</th>
</tr>

</thead>

<tbody>

<?php foreach($activities as $activity): ?>

<tr>

<td>
<strong>
<?= htmlspecialchars($activity['title']); ?>
</strong>
</td>

<td>
<?= htmlspecialchars($activity['course_name']); ?>
</td>

<td>
<?= $activity['max_grade']; ?>
</td>

<td>
<?= !empty($activity['due_date'])
? date('d/m/Y', strtotime($activity['due_date']))
: '-'; ?>
</td>

<td>

<button
class="btn btn-info btn-sm"
data-bs-toggle="collapse"
data-bs-target="#activity<?= $activity['id']; ?>">
عرض
</button>

</td>

</tr>

<tr>

<td colspan="5" class="p-0">

<div
id="activity<?= $activity['id']; ?>"
class="collapse">

<div class="p-3 bg-light">

<?= nl2br(htmlspecialchars($activity['instructions'])); ?>

</div>

</div>

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