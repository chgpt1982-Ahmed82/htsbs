<?php

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';

$db = (new Database())->connect();

$classId = $_GET['class_id'];

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
   
<h2>تسجيل الحضور</h2>

<form
method="POST"
action="save.php">

<input
type="hidden"
name="class_id"
value="<?= $classId; ?>">

<div class="mb-3">

<a
href="report.php?class_id=<?= $classId; ?>"
class="btn btn-primary">

📊 عرض تقرير الحضور

</a>

</div>



<table class="table table-bordered">

<tr>

<th>الطالب</th>
<th>الرقم الأكاديمي</th>
<th>الحالة</th>

</tr>

<?php foreach($students as $student): ?>

<?php

$attendanceStmt = $db->prepare(
"SELECT status
 FROM attendance
 WHERE student_id=?
 AND attendance_date=?"
);

$attendanceStmt->execute([
    $student['id'],
    date('Y-m-d')
]);

$currentAttendance =
$attendanceStmt->fetch(PDO::FETCH_ASSOC);

$currentStatus =
$currentAttendance['status'] ?? '';

?>

<tr>

<td>

<?= htmlspecialchars($student['full_name']); ?>

</td>

<td>

<?= htmlspecialchars($student['student_number']); ?>

</td>

<td>

<select
name="status[<?= $student['id']; ?>]"
class="form-select">

<option
value=""
<?= ($currentStatus=='') ? 'selected' : ''; ?>>

-- اختر الحالة --

</option>

<option
value="Present"
<?= ($currentStatus=='Present') ? 'selected' : ''; ?>>

🟢 حاضر

</option>

<option
value="Absent"
<?= ($currentStatus=='Absent') ? 'selected' : ''; ?>>

🔴 غائب

</option>

<option
value="Late"
<?= ($currentStatus=='Late') ? 'selected' : ''; ?>>

🟡 متأخر

</option>

<option
value="Excused"
<?= ($currentStatus=='Excused') ? 'selected' : ''; ?>>

🔵 بعذر

</option>

</select>

</td>

</tr>

<?php endforeach; ?>

</table>

<button
type="submit"
class="btn btn-success">

حفظ الحضور

</button>

</form>

</div>

</div>

</div>


</script>

<?php include '../../app/views/layouts/footer.php'; ?>
