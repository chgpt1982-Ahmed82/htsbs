<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

//echo "<pre>";

//echo "SESSION:\n";
//print_r($_SESSION);

//echo "</pre>";

//exit;

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../app/models/Student.php';

$db = (new Database())->connect();

$studentModel = new Student();

$studentId =
$studentModel->getStudentId(
$_SESSION['user_id']
);


$sql = "

SELECT

qa.*,

q.title

FROM quiz_attempts qa

INNER JOIN quizzes q
ON qa.quiz_id=q.id

WHERE qa.student_id=?

ORDER BY qa.id DESC

";

$stmt = $db->prepare($sql);

$stmt->execute([
$studentId
]);



$results =$stmt->fetchAll(PDO::FETCH_ASSOC);

//echo "<pre>";
////print_r($results);
//echo "</pre>";
//exit;



include '../app/views/layouts/header.php';

?>
<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../app/views/layouts/student_sidebar.php'; ?> 
<div class="main-content">



<h2>

My Results

</h2>

<table class="table table-bordered">

<tr>

<th>Quiz</th>

<th>Score %</th>

<th>Questions</th>

<th>Date</th>

</tr>

<?php foreach($results as $result): ?>

<tr>

<td>

<?= htmlspecialchars($result['title']); ?>

</td>

<td>

<?= $result['score']; ?> %

</td>

<td>

<?= $result['total_questions']; ?>

</td>

<td>

<?= $result['submitted_at']; ?>

</td>

</tr>

<?php endforeach; ?>

</table>

</div>


</div>
</div>
<?php include '../app/views/layouts/footer.php'; ?>
