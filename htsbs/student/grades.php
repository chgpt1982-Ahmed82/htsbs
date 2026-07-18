<?php

session_start();

require_once '../config/config.php';
require_once '../config/database.php';

if(!isset($_SESSION['user_id']))
{
    header(
        "Location: " .
        BASE_URL .
        "/login.php"
    );

    exit;
}

$db = (new Database())->connect();

/*
Get Student ID
*/

$stmt = $db->prepare(
"SELECT id
 FROM students
 WHERE user_id=?"
);

$stmt->execute([
$_SESSION['user_id']
]);

$student =
$stmt->fetch(PDO::FETCH_ASSOC);

$studentId =
$student['id'];

/*
Get Grades
*/

$sql = "

SELECT

g.*,

c.course_name

FROM gradebook g

INNER JOIN courses c
ON g.course_id=c.id

WHERE g.student_id=?

ORDER BY
c.course_name,
g.created_at DESC

";

$stmt = $db->prepare($sql);

$stmt->execute([
$studentId
]);

$grades =
$stmt->fetchAll(PDO::FETCH_ASSOC);


$totalCourses = 0;
$passedCourses = 0;
$failedCourses = 0;

$courseGrades = [];

foreach($grades as $grade)
{
    $percentage =
    ($grade['score'] / $grade['max_score']) * 100;

    $courseGrades[$grade['course_name']][] =
    $percentage;
}

foreach($courseGrades as $course => $scores)
{
    $avg =
    array_sum($scores) /
    count($scores);

    $totalCourses++;

    if($avg >= 50)
    {
        $passedCourses++;
    }
    else
    {
        $failedCourses++;
    }
}

$overallGPA = 0;

if($totalCourses > 0)
{
    foreach($courseGrades as $scores)
    {
        $overallGPA +=
        array_sum($scores) /
        count($scores);
    }

    $overallGPA =
    $overallGPA /
    $totalCourses;
}



include '../app/views/layouts/header.php';

?>

<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../app/views/layouts/student_sidebar.php';?>
<div class="main-content">



<h2>My Grades</h2>
<div class="row mb-4">

    <div class="col-md-4">

        <div class="card text-center border-success">

            <div class="card-body">

                <h5 class="text-success">

                    Overall GPA

                </h5>

                <h2>

                    <?= number_format($overallGPA,2); ?>%

                </h2>

            </div>

        </div>

    </div>

    <div class="col-md-4">

        <div class="card text-center border-primary">

            <div class="card-body">

                <h5 class="text-primary">

                    Courses Passed

                </h5>

                <h2>

                    <?= $passedCourses; ?>

                </h2>

            </div>

        </div>

    </div>

    <div class="col-md-4">

        <div class="card text-center border-danger">

            <div class="card-body">

                <h5 class="text-danger">

                    Courses Failed

                </h5>

                <h2>

                    <?= $failedCourses; ?>

                </h2>

            </div>

        </div>

    </div>

</div>
<table class="table table-bordered table-striped">

<thead>

<tr>

<th>Course</th>
<th>Assessment Type</th>
<th>Title</th>
<th>Score</th>
<th>Max Score</th>
<th>Percentage</th>
<th>Date</th>

</tr>

</thead>

<tbody>

<?php foreach($grades as $grade): ?>

<?php

$percentage =
($grade['score'] /
$grade['max_score']) * 100;

?>

<tr>

<td>

<?= htmlspecialchars(
$grade['course_name']
); ?>

</td>

<td>

<?= htmlspecialchars(
$grade['assessment_type']
); ?>

</td>

<td>

<?= htmlspecialchars(
$grade['title']
); ?>

</td>

<td>

<?= $grade['score']; ?>

</td>

<td>

<?= $grade['max_score']; ?>

</td>

<td>

<?= number_format(
$percentage,
2
); ?> %

</td>

<td>

<?= $grade['created_at']; ?>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

</div>


</div>

<?php include '../app/views/layouts/footer.php'; ?>
