<?php

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';

if(
    !isset($_SESSION['user_id']) ||
    $_SESSION['role_id'] != 2
){
    exit('Unauthorized Access');
}

$db = (new Database())->connect();

/*
=================================
Classes
=================================
*/

$classes = $db->query("
SELECT *
FROM classes
ORDER BY class_name
")->fetchAll(PDO::FETCH_ASSOC);

/*
=================================
Filters
=================================
*/

$studentName =
trim($_GET['student_name'] ?? '');

$studentNumber =
trim($_GET['student_number'] ?? '');

$classId =
(int)($_GET['class_id'] ?? 0);

/*
=================================
Query
=================================
*/

$sql = "
SELECT

s.id,

u.full_name,

s.student_number,

c.class_name,

SUM(
CASE
WHEN b.note_type='positive'
THEN 1
ELSE 0
END
) AS positive_count,

SUM(
CASE
WHEN b.note_type='negative'
THEN 1
ELSE 0
END
) AS negative_count,

SUM(
CASE
WHEN b.note_type='warning'
THEN 1
ELSE 0
END
) AS warning_count,

COUNT(b.id) AS total_notes

FROM students s

INNER JOIN users u
ON s.user_id = u.id

LEFT JOIN classes c
ON s.class_id = c.id

LEFT JOIN behavior_notes b
ON s.id = b.student_id

WHERE 1=1
";

$params = [];

if($studentName != '')
{
    $sql .= "
    AND u.full_name LIKE ?
    ";

    $params[] =
    "%{$studentName}%";
}

if($studentNumber != '')
{
    $sql .= "
    AND s.student_number LIKE ?
    ";

    $params[] =
    "%{$studentNumber}%";
}

if($classId > 0)
{
    $sql .= "
    AND s.class_id = ?
    ";

    $params[] =
    $classId;
}

$sql .= "
GROUP BY

s.id,
u.full_name,
s.student_number,
c.class_name

ORDER BY
u.full_name
";

$stmt = $db->prepare($sql);

$stmt->execute($params);

$results =
$stmt->fetchAll(PDO::FETCH_ASSOC);

/*
=================================
Totals
=================================
*/

$totalPositive = 0;
$totalNegative = 0;
$totalWarning  = 0;
$totalNotes    = 0;

foreach($results as $row)
{
    $totalPositive += $row['positive_count'];
    $totalNegative += $row['negative_count'];
    $totalWarning  += $row['warning_count'];
    $totalNotes    += $row['total_notes'];
}

include '../../app/views/layouts/header.php';

?>

<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">
<h2 class="mb-4">

📊 تقرير السلوك الطلابي

</h2>

<div class="card shadow mb-4">

<div class="card-body">

<form method="GET">

<div class="row">

<div class="col-md-4 mb-2">

<label class="form-label">

اسم الطالب

</label>

<input
type="text"
name="student_name"
value="<?= htmlspecialchars($studentName); ?>"
class="form-control">

</div>

<div class="col-md-3 mb-2">

<label class="form-label">

الرقم الأكاديمي

</label>

<input
type="text"
name="student_number"
value="<?= htmlspecialchars($studentNumber); ?>"
class="form-control">

</div>

<div class="col-md-3 mb-2">

<label class="form-label">

الصف

</label>

<select
name="class_id"
class="form-select">

<option value="">

جميع الصفوف

</option>

<?php foreach($classes as $class): ?>

<option
value="<?= $class['id']; ?>"
<?= $classId == $class['id'] ? 'selected' : ''; ?>>

<?= htmlspecialchars($class['class_name']); ?>

</option>

<?php endforeach; ?>

</select>

</div>

<div class="col-md-2 mb-2 d-flex align-items-end">

<button
class="btn btn-primary w-100">

بحث

</button>

</div>

</div>

</form>

</div>

</div>

<div class="row mb-4">

<div class="col-md-3">

<div class="card bg-success text-white">

<div class="card-body text-center">

<h5>إيجابية</h5>

<h2><?= $totalPositive; ?></h2>

</div>

</div>

</div>

<div class="col-md-3">

<div class="card bg-danger text-white">

<div class="card-body text-center">

<h5>سلبية</h5>

<h2><?= $totalNegative; ?></h2>

</div>

</div>

</div>

<div class="col-md-3">

<div class="card bg-warning text-dark">

<div class="card-body text-center">

<h5>تنبيهات</h5>

<h2><?= $totalWarning; ?></h2>

</div>

</div>

</div>

<div class="col-md-3">

<div class="card bg-primary text-white">

<div class="card-body text-center">

<h5>الإجمالي</h5>

<h2><?= $totalNotes; ?></h2>

</div>

</div>

</div>

</div>

<div class="card shadow">

<div class="card-header bg-dark text-white">

تقرير السلوك

</div>

<div class="card-body">

<div class="table-responsive">

<table class="table table-bordered table-hover">

<thead class="table-dark">

<tr>

<th>#</th>

<th>الطالب</th>

<th>الرقم الأكاديمي</th>

<th>الصف</th>

<th>إيجابية</th>

<th>سلبية</th>

<th>تنبيهات</th>

<th>الإجمالي</th>

<th>التفاصيل</th>

</tr>

</thead>

<tbody>

<?php if(empty($results)): ?>

<tr>

<td colspan="9" class="text-center">

لا توجد بيانات

</td>

</tr>

<?php endif; ?>

<?php foreach($results as $index => $row): ?>

<tr>

<td>

<?= $index + 1; ?>

</td>

<td>

<?= htmlspecialchars($row['full_name']); ?>

</td>

<td>

<?= htmlspecialchars($row['student_number']); ?>

</td>

<td>

<?= htmlspecialchars($row['class_name']); ?>

</td>

<td>

<span class="badge bg-success">

<?= $row['positive_count']; ?>

</span>

</td>

<td>

<span class="badge bg-danger">

<?= $row['negative_count']; ?>

</span>

</td>

<td>

<span class="badge bg-warning text-dark">

<?= $row['warning_count']; ?>

</span>

</td>

<td>

<span class="badge bg-primary">

<?= $row['total_notes']; ?>

</span>

</td>

<td>

<a
href="view.php?student_id=<?= $row['id']; ?>"
class="btn btn-info btn-sm">

عرض

</a>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

</div>

</div>

</div>

</div>

</div>

<?php include '../../app/views/layouts/footer.php'; ?>