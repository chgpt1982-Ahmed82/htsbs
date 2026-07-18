<?php

session_start();

require_once '../../config/config.php';
require_once '../../app/models/Activity.php';

if(
    !isset($_SESSION['user_id']) ||
    $_SESSION['role_id'] != 2
){
    exit('Unauthorized Access');
}

$model = new Activity();

$activity = $model->getById($_GET['id']);

$courses = $model->getTeacherCourses(
    $_SESSION['user_id']
);

include '../../app/views/layouts/header.php';

?>

<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">
<h2 class="mb-4">

📝 تعديل النشاط

</h2>

<form
method="POST"
action="update.php?id=<?= $activity['id']; ?>">

<div class="card shadow border-0">

<div class="card-body">

<div class="mb-3">

<label class="form-label">

المقرر

</label>

<select
name="course_id"
class="form-control"
required>

<?php foreach($courses as $course): ?>

<option
value="<?= $course['id']; ?>"
<?= ($course['id'] == $activity['course_id']) ? 'selected' : ''; ?>>

<?= htmlspecialchars($course['course_name']); ?>

</option>

<?php endforeach; ?>

</select>

</div>

<div class="mb-3">

<label class="form-label">

عنوان النشاط

</label>

<input
type="text"
name="title"
value="<?= htmlspecialchars($activity['title']); ?>"
class="form-control"
required>

</div>

<div class="mb-3">

<label class="form-label">

تعليمات النشاط

</label>

<textarea
name="instructions"
class="form-control"
rows="5"><?= htmlspecialchars($activity['instructions']); ?></textarea>

</div>

<div class="mb-3">

<label class="form-label">

الدرجة العظمى

</label>

<input
type="number"
name="max_grade"
value="<?= $activity['max_grade']; ?>"
class="form-control">

</div>

<div class="mb-3">

<label class="form-label">

موعد التسليم

</label>

<input
type="datetime-local"
name="due_date"
value="<?= !empty($activity['due_date'])
? date('Y-m-d\TH:i', strtotime($activity['due_date']))
: ''; ?>"
class="form-control">

</div>

<div class="mt-4">

<button
type="submit"
class="btn btn-success">

<i class="bi bi-check-circle"></i>

حفظ التعديلات

</button>

<a
href="index.php"
class="btn btn-secondary">

<i class="bi bi-arrow-right"></i>

رجوع

</a>

</div>

</div>

</div>

</form>

</div>

</div>

</div>

<?php include '../../app/views/layouts/footer.php'; ?>

