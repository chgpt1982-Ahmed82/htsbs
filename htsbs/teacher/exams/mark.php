<?php

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';

require_once '../../app/models/Exam.php';
require_once '../../app/models/Notification.php';

$db = (new Database())->connect();

$examModel =
new Exam();

$notificationModel =
new Notification();

$count =
$notificationModel->unreadCount(
$_SESSION['user_id']
);

$examId =
$_GET['id'] ?? 0;

$exam =
$examModel->find(
$examId
);

if(!$exam)
{
    die('Exam Not Found');
}

/*
جلب الصفوف المرتبطة بالامتحان
*/

$classStmt = $db->prepare(

"SELECT class_id
 FROM exam_classes
 WHERE exam_id=?"

);

$classStmt->execute([
$examId
]);

$classIds =
$classStmt->fetchAll(
PDO::FETCH_COLUMN
);

if(empty($classIds))
{
    die('No Classes Found');
}

/*
جلب الطلاب
*/

$placeholders =
implode(
',',
array_fill(
0,
count($classIds),
'?'
)
);

$sql = "

SELECT

s.id,
u.full_name,
s.student_number

FROM students s

INNER JOIN users u
ON s.user_id=u.id

WHERE s.class_id IN
($placeholders)

ORDER BY u.full_name

";

$stmt =
$db->prepare($sql);

$stmt->execute(
$classIds
);

$students =
$stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../app/views/layouts/header.php';

?>
<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">   



<h2>

Enter Exam Marks

</h2>

<div class="card mb-4">

<div class="card-body">

<h4>

<?= htmlspecialchars(
$exam['exam_name']
); ?>

</h4>

<p>

Type:

<?= $exam['exam_type']; ?>

</p>

<p>

Date:

<?= $exam['exam_date']; ?>

</p>

<p>

Max Marks:

<?= $exam['max_marks']; ?>

</p>

</div>

</div>

<form
method="POST"
action="save_marks.php">

<input
type="hidden"
name="exam_id"
value="<?= $examId; ?>">

<table class="table table-bordered">

<thead>

<tr>

<th>Student Number</th>
<th>Student Name</th>
<th>Marks</th>
<th>Remarks</th>

</tr>

</thead>

<tbody>

<?php foreach($students as $student): ?>

<tr>

<td>

<?= htmlspecialchars(
$student['student_number']
); ?>

</td>

<td>

<?= htmlspecialchars(
$student['full_name']
); ?>

</td>

<td>

<input
type="number"
step="0.01"
max="<?= $exam['max_marks']; ?>"
min="0"
name="marks[<?= $student['id']; ?>]"
class="form-control">

</td>

<td>

<input
type="text"
name="remarks[<?= $student['id']; ?>]"
class="form-control">

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

<button
type="submit"
class="btn btn-success">

Save Marks

</button>

<a
href="index.php"
class="btn btn-secondary">

Back

</a>

</form>

</div>

<?php include '../../app/views/layouts/footer.php'; ?>
