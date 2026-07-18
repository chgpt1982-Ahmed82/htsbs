<?php

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';

$db = (new Database())->connect();

include '../../app/views/layouts/header.php';

/*
جلب جميع الواجبات مع الإحصائيات
*/

$stmt = $db->query(

"SELECT

a.id,
a.title,
a.due_date,

COUNT(DISTINCT s.id) AS submitted_students,

COUNT(DISTINCT st.id) AS total_students,

ROUND(AVG(s.score),2) AS average_score

FROM assignments a

LEFT JOIN assignment_submissions s
ON a.id = s.assignment_id

LEFT JOIN students st
ON st.class_id = a.class_id

GROUP BY a.id

ORDER BY a.created_at DESC"

);

$assignments =
$stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">   
<h2 class="mb-4">

Assignments Reports

</h2>

<table class="table table-bordered table-striped">

<thead>

<tr>

<th>Assignment</th>

<th>Due Date</th>

<th>Total Students</th>

<th>Submitted</th>

<th>Missing</th>

<th>Average Score</th>

<th>Status</th>

</tr>

</thead>

<tbody>

<?php foreach($assignments as $assignment): ?>

<?php

$missing =
$assignment['total_students']
-
$assignment['submitted_students'];

?>

<tr>

<td>

<?= htmlspecialchars(
$assignment['title']
); ?>

</td>

<td>

<?= $assignment['due_date']; ?>

</td>

<td>

<?= $assignment['total_students']; ?>

</td>

<td>

<span class="badge bg-success">

<?= $assignment['submitted_students']; ?>

</span>

</td>

<td>

<span class="badge bg-danger">

<?= $missing; ?>

</span>

</td>

<td>

<?= $assignment['average_score']
?: 'N/A'; ?>

</td>

<td>

<?php if($missing == 0): ?>

<span class="badge bg-success">

Completed

</span>

<?php else: ?>

<span class="badge bg-warning">

Pending

</span>

<?php endif; ?>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

<?php include '../../app/views/layouts/footer.php'; ?>
