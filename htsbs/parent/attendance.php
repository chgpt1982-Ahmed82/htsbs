<?php

session_start();
require_once '../config/config.php';
require_once '../config/database.php';

$db = (new Database())->connect();

$sql = "

SELECT

a.attendance_date,
a.status

FROM attendance a

INNER JOIN parent_student ps
ON a.student_id=ps.student_id

INNER JOIN parents p
ON ps.parent_id=p.id

WHERE p.user_id=?

ORDER BY a.attendance_date DESC

";

$stmt = $db->prepare($sql);

$stmt->execute([
$_SESSION['user_id']
]);

$attendance =
$stmt->fetchAll(PDO::FETCH_ASSOC);

include '../app/views/layouts/header.php';

?>

<div class="container-fluid">

<div class="row">

<?php include '../app/views/layouts/parent_sidebar.php'; ?>

<div class="col-md-10">

<h2>Attendance</h2>

<table class="table table-bordered">

<tr>

<th>Date</th>
<th>Status</th>

</tr>

<?php foreach($attendance as $row): ?>

<tr>

<td>

<?= $row['attendance_date']; ?>

</td>

<td>

<?php

switch($row['status'])
{
    case 'Present':
        echo '<span class="badge bg-success">حاضر</span>';
        break;

    case 'Absent':
        echo '<span class="badge bg-danger">غائب</span>';
        break;

    case 'Late':
        echo '<span class="badge bg-warning">متأخر</span>';
        break;

    default:
        echo '<span class="badge bg-primary">بعذر</span>';
}

?>

</td>

</tr>

<?php endforeach; ?>

</table>

</div>
</div>

</div>

</div>
<?php include '../app/views/layouts/footer.php'; ?>
