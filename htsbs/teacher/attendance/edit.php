<?php

error_reporting(E_ALL);
ini_set('display_errors',1);

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';

if(!isset($_GET['attendance_id']))
{
    die('Attendance ID Missing');
}

$db = (new Database())->connect();

$attendanceId = $_GET['attendance_id'];

$stmt = $db->prepare(

"SELECT

a.*,

u.full_name,

s.student_number

FROM attendance a

INNER JOIN students s
ON a.student_id=s.id

INNER JOIN users u
ON s.user_id=u.id

WHERE a.id=?"

);

$stmt->execute([
$attendanceId
]);

$attendance =
$stmt->fetch(PDO::FETCH_ASSOC);

if(!$attendance)
{
    die('Attendance Record Not Found');
}

include '../../app/views/layouts/header.php';

?>
<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">    
<h2>تحديث الحضور</h2>

<form
method="POST"
action="update.php">

<input
type="hidden"
name="attendance_id"
value="<?= $attendance['id']; ?>">

<div class="card">

<div class="card-body">

<div class="mb-3">

<label class="form-label">

اسم الطالب
</label>

<input
type="text"
class="form-control"
value="<?= htmlspecialchars($attendance['full_name']); ?>"
readonly>

</div>

<div class="mb-3">

<label class="form-label">

الرقم الأكاديمي
</label>

<input
type="text"
class="form-control"
value="<?= htmlspecialchars($attendance['student_number']); ?>"
readonly>

</div>

<div class="mb-3">

<label class="form-label">

تاريخ الحضور
</label>

<input
type="date"
class="form-control"
style="direction: rtl; text-align:right;"
value="<?= $attendance['attendance_date']; ?>"
readonly>

</div>

<div class="mb-3">

<label class="form-label">

الحالة

</label>

<select
name="status"
class="form-select attendance-status">

<option
value="Present"
<?= ($attendance['status']=='Present') ? 'selected' : ''; ?>
style="background-color:#d4edda;color:#155724;">

🟢 حاضر

</option>

<option
value="Absent"
<?= ($attendance['status']=='Absent') ? 'selected' : ''; ?>
style="background-color:#f8d7da;color:#721c24;">

🔴 غائب

</option>

<option
value="Late"
<?= ($attendance['status']=='Late') ? 'selected' : ''; ?>
style="background-color:#fff3cd;color:#856404;">

🟡 متأخر

</option>

<option
value="Excused"
<?= ($attendance['status']=='Excused') ? 'selected' : ''; ?>
style="background-color:#cce5ff;color:#004085;">

🔵 بعذر

</option>

</select>


</div>

<div class="mb-3">

<label class="form-label">

ملاحظات

</label>

<textarea
name="notes"
rows="4"
class="form-control"><?= htmlspecialchars($attendance['notes'] ?? ''); ?></textarea>

</div>

<button
type="submit"
class="btn btn-success">

تحديث الحضور
</button>

<a
href="javascript:history.back();"
class="btn btn-secondary">

رجوع

</a>

</div>

</div>

</form>

</div>

</div>

</div>
<script>

function updateAttendanceColor(select)
{
    switch(select.value)
    {
        case 'Present':
            select.style.backgroundColor='#d4edda';
            select.style.color='#155724';
            break;

        case 'Absent':
            select.style.backgroundColor='#f8d7da';
            select.style.color='#721c24';
            break;

        case 'Late':
            select.style.backgroundColor='#fff3cd';
            select.style.color='#856404';
            break;

        case 'Excused':
            select.style.backgroundColor='#cce5ff';
            select.style.color='#004085';
            break;
    }
}

document.addEventListener(
'DOMContentLoaded',
function(){

    document
    .querySelectorAll('.attendance-status')
    .forEach(function(select){

        updateAttendanceColor(select);

        select.addEventListener(
        'change',
        function(){
            updateAttendanceColor(this);
        });

    });

});

</script>

<?php include '../../app/views/layouts/footer.php'; ?>
