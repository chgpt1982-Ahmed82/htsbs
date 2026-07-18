<?php

session_start();
require_once '../config/config.php';

require_once '../config/database.php';

$db = (new Database())->connect();

$userId = $_SESSION['user_id'];

$stmt = $db->prepare(
"
SELECT
COUNT(*) total_students
FROM quiz_attempts qa
INNER JOIN quizzes q
ON qa.quiz_id=q.id
INNER JOIN teachers t
ON q.teacher_id=t.id
WHERE t.user_id=?
"
);

$stmt->execute([$userId]);

$totalStudents =
$stmt->fetch(PDO::FETCH_ASSOC)['total_students'];

$stmt = $db->prepare(
"
SELECT
ROUND(AVG(score),2) avg_score
FROM quiz_attempts qa
INNER JOIN quizzes q
ON qa.quiz_id=q.id
INNER JOIN teachers t
ON q.teacher_id=t.id
WHERE t.user_id=?
"
);

$stmt->execute([$userId]);

$avgScore =
$stmt->fetch(PDO::FETCH_ASSOC)['avg_score'];

$stmt = $db->prepare(
"
SELECT MAX(score) max_score
FROM quiz_attempts qa
INNER JOIN quizzes q
ON qa.quiz_id=q.id
INNER JOIN teachers t
ON q.teacher_id=t.id
WHERE t.user_id=?
"
);

$stmt->execute([$userId]);

$maxScore =
$stmt->fetch(PDO::FETCH_ASSOC)['max_score'];

$stmt = $db->prepare(
"
SELECT MIN(score) min_score
FROM quiz_attempts qa
INNER JOIN quizzes q
ON qa.quiz_id=q.id
INNER JOIN teachers t
ON q.teacher_id=t.id
WHERE t.user_id=?
"
);

$stmt->execute([$userId]);

$minScore =
$stmt->fetch(PDO::FETCH_ASSOC)['min_score'];

include '../app/views/layouts/header.php';
?>
<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">
    
    
    
    
<h2>Teacher Reports</h2>

 <!-- div class="col-lg-10 p-4" dir="rtl"> -->

<h2 class="mb-4 text-end">
التقارير والإحصائيات
</h2>

<div class="row g-3">

<div class="col-md-3">
<div class="card border-0 shadow-sm text-center">
<div class="card-body">

<h6 class="text-muted">
إجمالي المحاولات
</h6>

<h2 class="text-primary">
<?= $totalStudents ?? 0; ?>
</h2>

</div>
</div>
</div>

<div class="col-md-3">
<div class="card border-0 shadow-sm text-center">
<div class="card-body">

<h6 class="text-muted">
متوسط الدرجات
</h6>

<h2 class="text-success">
<?= $avgScore ?? 0; ?>%
</h2>

</div>
</div>
</div>

<div class="col-md-3">
<div class="card border-0 shadow-sm text-center">
<div class="card-body">

<h6 class="text-muted">
أعلى درجة
</h6>

<h2 class="text-info">
<?= $maxScore ?? 0; ?>%
</h2>

</div>
</div>
</div>

<div class="col-md-3">
<div class="card border-0 shadow-sm text-center">
<div class="card-body">

<h6 class="text-muted">
أقل درجة
</h6>

<h2 class="text-danger">
<?= $minScore ?? 0; ?>%
</h2>

</div>
</div>
</div>

</div>

<hr class="my-4">

<div class="card shadow-sm">

<div class="card-header bg-primary text-white">

ملخص أداء الاختبارات

</div>

<div class="card-body">

<p>
يعرض هذا التقرير إحصائيات جميع الاختبارات التي أنشأها المعلم.
</p>

<ul>

<li>
إجمالي المحاولات:
<strong><?= $totalStudents ?? 0; ?></strong>
</li>

<li>
متوسط الدرجات:
<strong><?= $avgScore ?? 0; ?>%</strong>
</li>

<li>
أعلى درجة:
<strong><?= $maxScore ?? 0; ?>%</strong>
</li>

<li>
أقل درجة:
<strong><?= $minScore ?? 0; ?>%</strong>
</li>

</ul>

</div>

</div>

</div>



<?php include '../app/views/layouts/footer.php'; ?>
