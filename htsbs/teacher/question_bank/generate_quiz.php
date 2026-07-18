<?php

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';

require_once '../../app/models/QuestionBank.php';
require_once '../../app/models/Quiz.php';

$questionBank = new QuestionBank();
$quizModel = new Quiz();

$db = (new Database())->connect();

/*
عرض المقررات
*/

$courses =
$questionBank->getTeacherCourses(
$_SESSION['user_id']
);

$selectedCourse =
$_GET['course_id']
??
'';

/*
عرض الصفوف المسندة
*/

$classes = [];

if($selectedCourse)
{
    $classes =
    $questionBank->getTeacherClasses(
        $_SESSION['user_id'],
        $selectedCourse
    );
}

/*
إنشاء الاختبار
*/

if($_SERVER['REQUEST_METHOD'] == 'POST')
{
    $teacherId =
    $quizModel->getTeacherId(
    $_SESSION['user_id']
    );

    $quizData = [

        'teacher_id' =>
        $teacherId,

        'course_id' =>
        $_POST['course_id'],

        'class_id' =>
        $_POST['class_id'],

        'title' =>
        $_POST['title'],

        'duration_minutes' =>
        $_POST['duration_minutes'],

        'total_marks' =>
        $_POST['total_marks'],

        'start_date' =>
        $_POST['start_date'],

        'end_date' =>
        $_POST['end_date'],

        'attempts_allowed' => 1,

        'is_published' => 1

    ];

    $quizId =
    $quizModel->create(
    $quizData
    );

    /*
    ربط الاختبار بالصف
    */

    $stmt = $db->prepare(

    "INSERT INTO quiz_classes
    (
        quiz_id,
        class_id
    )
    VALUES
    (
        ?,?
    )"

    );

    $stmt->execute([

        $quizId,
        $_POST['class_id']

    ]);

    /*
    عدد الأسئلة
    */

    $questionCount =
    (int)$_POST['question_count'];

    /*
    أسئلة عشوائية
    */

    $questions =
    $questionBank->getRandomQuestions(
        $_POST['course_id'],
        $questionCount
    );

    foreach($questions as $question)
    {
        $quizModel->addQuestion([

            'quiz_id' =>
            $quizId,

            'question_text' =>
            $question['question_text'],

            'question_type' =>
            'multiple_choice',

            'marks' =>
            $question['marks'],

            'option_a' =>
            $question['option_a'],

            'option_b' =>
            $question['option_b'],

            'option_c' =>
            $question['option_c'],

            'option_d' =>
            $question['option_d'],

            'correct_answer' =>
            $question['correct_answer']

        ]);
    }

    header(
    'Location: ../quizzes/questions.php?id=' .
    $quizId
    );

    exit;
}

include '../../app/views/layouts/header.php';

?>
<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">
   

<h2>

⚡ Generate Quiz From Question Bank

</h2>

<div class="card">

<div class="card-body">

<form method="GET">

<div class="mb-3">

<label>

Course

</label>

<select
name="course_id"
class="form-control"
onchange="this.form.submit()">

<option value="">

Select Course

</option>

<?php foreach($courses as $course): ?>

<option
value="<?= $course['id']; ?>"

<?= $selectedCourse == $course['id']
? 'selected'
: ''; ?>>

<?= htmlspecialchars(
$course['course_name']
); ?>

</option>

<?php endforeach; ?>

</select>

</div>

</form>

<hr>

<?php if($selectedCourse): ?>

<form method="POST">

<input
type="hidden"
name="course_id"
value="<?= $selectedCourse; ?>">

<div class="mb-3">

<label>

Quiz Title

</label>

<input
type="text"
name="title"
class="form-control"
required>

</div>

<div class="mb-3">

<label>

Class

</label>

<select
name="class_id"
class="form-control"
required>

<option value="">

Select Class

</option>

<?php foreach($classes as $class): ?>

<option
value="<?= $class['id']; ?>">

<?= htmlspecialchars(
$class['class_name']
); ?>

</option>

<?php endforeach; ?>

</select>

</div>

<div class="mb-3">

<label>

Number Of Questions

</label>

<input
type="number"
name="question_count"
value="10"
min="1"
class="form-control"
required>

</div>

<div class="mb-3">

<label>

Duration (Minutes)

</label>

<input
type="number"
name="duration_minutes"
value="30"
class="form-control"
required>

</div>

<div class="mb-3">

<label>

Total Marks

</label>

<input
type="number"
name="total_marks"
value="100"
class="form-control"
required>

</div>

<div class="row">

<div class="col-md-6">

<div class="mb-3">

<label>

Start Date

</label>

<input
type="datetime-local"
name="start_date"
class="form-control"
required>

</div>

</div>

<div class="col-md-6">

<div class="mb-3">

<label>

End Date

</label>

<input
type="datetime-local"
name="end_date"
class="form-control"
required>

</div>

</div>

</div>

<button
type="submit"
class="btn btn-success">

Generate Quiz

</button>

<a
href="index.php"
class="btn btn-secondary">

Back

</a>

</form>

<?php endif; ?>

</div>

</div>

</div>

</div>

</div>

<?php include '../../app/views/layouts/footer.php'; ?>
