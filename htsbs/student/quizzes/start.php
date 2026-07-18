<?php

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';

require_once '../../app/models/Quiz.php';
require_once '../../app/models/QuizQuestion.php';

if(!isset($_GET['id']))
{
    die('Quiz ID Missing');
}

$quizId =
(int)$_GET['id'];

$quizModel =
new Quiz();

$questionModel =
new QuizQuestion();

$quiz =
$quizModel->getById(
$quizId
);

if(!$quiz)
{
    die('Quiz Not Found');
}

$questions =
$questionModel->getQuestions(
$quizId
);

if(empty($questions))
{
    die('No Questions Found');
}

$db =
(new Database())->connect();

$stmt = $db->prepare(

"SELECT

    id

 FROM students

 WHERE user_id=?"

);

$stmt->execute([
$_SESSION['user_id']
]);

$student =
$stmt->fetch(PDO::FETCH_ASSOC);

if(!$student)
{
    die('Student Not Found');
}

$now =
date('Y-m-d H:i:s');

if(
$now < $quiz['start_date']
||
$now > $quiz['end_date']
)
{
    die(
    'Quiz Not Available'
    );
}

include '../../app/views/layouts/header.php';

?>

<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/student_sidebar.php'; ?>
<div class="main-content">



<h2>

📝

<?= htmlspecialchars(
$quiz['title']
); ?>

</h2>

<div class="alert alert-info">

Duration:

<?= $quiz['duration_minutes']; ?>

Minutes

</div>

<form
method="POST"
action="submit.php">

<input
type="hidden"
name="quiz_id"
value="<?= $quiz['id']; ?>">

<?php

$counter = 1;

foreach($questions as $question):

?>

<div class="card mb-4">

<div class="card-header">

Question

<?= $counter++; ?>

</div>

<div class="card-body">

<p>

<strong>

<?= htmlspecialchars(
$question['question_text']
); ?>

</strong>

</p>

<div class="form-check">

<input
class="form-check-input"
type="radio"
name="answers[<?= $question['id']; ?>]"
value="A"
required>

<label
class="form-check-label">

<?= htmlspecialchars(
$question['option_a']
); ?>

</label>

</div>

<div class="form-check">

<input
class="form-check-input"
type="radio"
name="answers[<?= $question['id']; ?>]"
value="B">

<label
class="form-check-label">

<?= htmlspecialchars(
$question['option_b']
); ?>

</label>

</div>

<div class="form-check">

<input
class="form-check-input"
type="radio"
name="answers[<?= $question['id']; ?>]"
value="C">

<label
class="form-check-label">

<?= htmlspecialchars(
$question['option_c']
); ?>

</label>

</div>

<div class="form-check">

<input
class="form-check-input"
type="radio"
name="answers[<?= $question['id']; ?>]"
value="D">

<label
class="form-check-label">

<?= htmlspecialchars(
$question['option_d']
); ?>

</label>

</div>

</div>

</div>

<?php endforeach; ?>

<button
type="submit"
class="btn btn-success">

Submit Quiz

</button>

</form>

</div>

</div>


</div>
<?php include '../../app/views/layouts/footer.php'; ?>
