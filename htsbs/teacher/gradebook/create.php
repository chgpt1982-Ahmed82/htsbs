<?php

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';

$db = (new Database())->connect();

$classId = $_GET['class_id'];
$courseId = $_GET['course_id'];

$stmt = $db->prepare(

"SELECT

s.id,
s.student_number,
u.full_name

FROM students s

INNER JOIN users u
ON s.user_id=u.id

WHERE s.class_id=?

ORDER BY u.full_name"

);

$stmt->execute([
$classId
]);

$students =
$stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../app/views/layouts/header.php';

?>
<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">   
<h2>Enter Grades</h2>

<form method="POST" action="store.php">

<input
type="hidden"
name="class_id"
value="<?= $classId; ?>">

<input
type="hidden"
name="course_id"
value="<?= $courseId; ?>">

<div class="row mb-3">

<div class="col-md-4">

<label>Assessment Type</label>

<select
name="assessment_type"
class="form-control"
required>

<option value="Quiz">Quiz</option>
<option value="Assignment">Assignment</option>
<option value="Activity">Activity</option>
<option value="Midterm">Midterm</option>
<option value="Final">Final</option>
<option value="Participation">Participation</option>

</select>

</div>

<div class="col-md-4">

<label>Title</label>

<input
type="text"
name="title"
class="form-control"
required>

</div>

<div class="col-md-4">

<label>Max Score</label>

<input
type="number"
name="max_score"
value="100"
class="form-control"
required>

</div>

</div>

<table class="table table-bordered">

<tr>

<th>Student</th>
<th>Number</th>
<th>Score</th>

</tr>

<?php foreach($students as $student): ?>

<tr>

<td>

<?= htmlspecialchars($student['full_name']); ?>

</td>

<td>

<?= htmlspecialchars($student['student_number']); ?>

</td>

<td>

<input
type="number"
step="0.01"
name="score[<?= $student['id']; ?>]"
class="form-control">

</td>

</tr>

<?php endforeach; ?>

</table>

<button
class="btn btn-success">

Save Grades

</button>

</form>

</div>

</div>

</div>

<?php include '../../app/views/layouts/footer.php'; ?>
