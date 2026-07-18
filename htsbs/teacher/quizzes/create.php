<?php

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../app/models/Quiz.php';

$model = new Quiz();

$courses = $model->getTeacherCourses(
    $_SESSION['user_id']
);

include '../../app/views/layouts/header.php';

?>

<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">
<div class="card shadow border-0">

<div class="card-header bg-primary text-white">

<h3 class="mb-0">

📝 إنشاء اختبار جديد (بنك الاختبارات)

</h3>

</div>

<div class="card-body">

<form action="store.php" method="POST">

<div class="mb-3">

<label class="form-label">

المقرر الدراسي

</label>

<select
name="course_id"
class="form-select"
required>

<option value="">

اختر المقرر

</option>

<?php foreach($courses as $course): ?>

<option value="<?= $course['id']; ?>">

<?= htmlspecialchars($course['course_name']); ?>

<?php if(!empty($course['course_code'])): ?>

(<?= htmlspecialchars($course['course_code']); ?>)

<?php endif; ?>

</option>

<?php endforeach; ?>

</select>

</div>

<div class="mb-3">

<label class="form-label">

عنوان الاختبار

</label>

<input
type="text"
name="title"
class="form-control"
required>

</div>

<div class="row">

<div class="col-md-4">

<div class="mb-3">

<label class="form-label">

مدة الاختبار (دقيقة)

</label>

<input
type="number"
name="duration_minutes"
value="30"
min="1"
class="form-control"
required>

</div>

</div>

<div class="col-md-4">

<div class="mb-3">

<label class="form-label">

الدرجة الكلية

</label>

<input
type="number"
name="total_marks"
value="100"
min="1"
class="form-control"
required>

</div>

</div>

<div class="col-md-4">

<div class="mb-3">

<label class="form-label">

عدد المحاولات

</label>

<input
type="number"
name="attempts_allowed"
value="1"
min="1"
class="form-control"
required>

</div>

</div>

</div>

<div class="row">

<div class="col-md-6">

<div class="mb-3">

<label class="form-label">

تاريخ بداية الاختبار

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

<label class="form-label">

تاريخ نهاية الاختبار

</label>

<input
type="datetime-local"
name="end_date"
class="form-control"
required>

</div>

</div>

</div>

<div class="form-check mb-4">

<input
class="form-check-input"
type="checkbox"
name="is_published"
value="1"
checked>

<label class="form-check-label">

نشر الاختبار مباشرة

</label>

</div>

<button
type="submit"
class="btn btn-success">

💾 حفظ الاختبار

</button>

<a
href="index.php"
class="btn btn-secondary">

رجوع

</a>

</form>

</div>

</div>

</div>

</div>

</div>

<?php include '../../app/views/layouts/footer.php'; ?>