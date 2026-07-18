<?php

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../app/models/QuizAssignment.php';

if(
    !isset($_SESSION['user_id']) ||
    $_SESSION['role_id'] != 2
){
    exit('Unauthorized Access');
}

$db = (new Database())->connect();

$stmt = $db->prepare("
SELECT id
FROM teachers
WHERE user_id=?
");

$stmt->execute([
    $_SESSION['user_id']
]);

$teacherId = $stmt->fetchColumn();

if(!$teacherId){
    die('Teacher Not Found');
}

$model = new QuizAssignment();

$quizzes = $model->getTeacherQuizzes(
    $teacherId
);

$classes = $model->getTeacherClasses(
    $teacherId
);

if($_SERVER['REQUEST_METHOD']=='POST')
{
    $quizId = (int)$_POST['quiz_id'];
    $classId = (int)$_POST['class_id'];

    if(
        !$model->assignmentExists(
            $quizId,
            $classId
        )
    ){
        $model->assignQuiz(
            $quizId,
            $classId,
            $teacherId,
            $_POST['academic_year'],
            $_POST['semester']
        );
    }

    $_SESSION['success'] =
    'تم تعيين الاختبار بنجاح';

    header(
        'Location: assignments.php'
    );

    exit;
}

include '../../app/views/layouts/header.php';
?>

<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">

<div class="card shadow">

<div class="card-header bg-primary text-white">

<h4>

<i class="bi bi-diagram-3"></i>

تعيين اختبار لشعبة

</h4>

</div>

<div class="card-body">

<form method="POST">

<div class="row">

<div class="col-md-6 mb-3">

<label class="form-label">

الاختبار

</label>

<select
name="quiz_id"
class="form-select"
required>

<option value="">

اختر الاختبار

</option>

<?php foreach($quizzes as $quiz): ?>

<option
value="<?= $quiz['id']; ?>">

<?= htmlspecialchars($quiz['title']); ?>

-

<?= htmlspecialchars($quiz['course_name']); ?>

</option>

<?php endforeach; ?>

</select>

</div>

<div class="col-md-6 mb-3">

<label class="form-label">

الشعبة

</label>

<select
name="class_id"
class="form-select"
required>

<option value="">

اختر الشعبة

</option>

<?php foreach($classes as $class): ?>

<option
value="<?= $class['id']; ?>">

<?= htmlspecialchars($class['class_name']); ?>

</option>

<?php endforeach; ?>

</select>

</div>

</div>

<div class="row">

<div class="col-md-6 mb-3">

<label class="form-label">

السنة الدراسية

</label>

<input
type="text"
name="academic_year"
class="form-control"
value="2025-2026">

</div>

<div class="col-md-6 mb-3">

<label class="form-label">

الفصل الدراسي

</label>

<select
name="semester"
class="form-select">

<option value="الأول">
الفصل الأول
</option>

<option value="الثاني">
الفصل الثاني
</option>

</select>

</div>

</div>

<button
type="submit"
class="btn btn-success">

<i class="bi bi-check-circle"></i>

تعيين الاختبار

</button>

<a
href="assignments.php"
class="btn btn-secondary">

عرض التعيينات

</a>

</form>

</div>

</div>

</div>

</div>

</div>

<?php include '../../app/views/layouts/footer.php'; ?>