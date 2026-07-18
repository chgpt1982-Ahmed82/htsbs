<?php

session_start();

require_once '../../config/config.php';
require_once '../../app/models/Quiz.php';
require_once '../../app/models/Notification.php';

$model = new Quiz();

$notificationModel = new Notification();

$count = $notificationModel->unreadCount(
    $_SESSION['user_id']
);

$quizzes = $model->getAllByTeacher(
    $_SESSION['user_id']
);

include '../../app/views/layouts/header.php';

?>

<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">
<!-- Header Card -->

<div class="card border-0 shadow-sm mb-4">

<div class="card-body">

<div class="row align-items-center">

<div class="col-md-6">

<h2 class="fw-bold mb-0">

📝  بنك الاختبارات القصيرة

</h2>

<small class="text-muted">

إدارة الاختبارات وإعادة استخدامها وتعيينها للشعب

</small>

</div>

<div class="col-md-6 text-md-end mt-3 mt-md-0">

<a
href="create.php"
class="btn btn-success">

<i class="bi bi-plus-circle"></i>

إضافة اختبار

</a>

<a
href="assign.php"
class="btn btn-primary">

<i class="bi bi-diagram-3"></i>

تعيين الاختبارات

</a>

<a
href="assignments.php"
class="btn btn-info text-white">

<i class="bi bi-list-check"></i>

التعيينات

</a>

</div>

</div>

</div>

</div>

<!-- Quiz Table -->

<div class="card border-0 shadow">

<div class="card-body">

<div class="table-responsive">

<table class="table table-hover table-bordered align-middle">

<thead class="table-dark">

<tr>

<th width="60">#</th>

<th>عنوان الاختبار</th>

<th>المقرر</th>

<th>رمز المقرر</th>

<th>الدرجة</th>

<th>المدة</th>

<th>عدد مرات الاستخدام</th>

<th>الحالة</th>

<th width="350">الإجراءات</th>

</tr>

</thead>

<tbody>

<?php if(empty($quizzes)): ?>

<tr>

<td colspan="9" class="text-center">

لا توجد اختبارات

</td>

</tr>

<?php endif; ?>

<?php foreach($quizzes as $index => $quiz): ?>

<tr>

<td>

<?= $index + 1 ?>

</td>

<td>

<strong>

<?= htmlspecialchars(
$quiz['title']
); ?>

</strong>

</td>

<td>

<?= htmlspecialchars(
$quiz['course_name']
); ?>

</td>

<td>

<?= htmlspecialchars(
$quiz['course_code'] ?? '-'
); ?>

</td>

<td>

<span class="badge bg-success">

<?= $quiz['total_marks']; ?>

درجة

</span>

</td>

<td>

<span class="badge bg-info">

<?= $quiz['duration_minutes']; ?>

دقيقة

</span>

</td>

<td>

<span class="badge bg-primary">

<?= $quiz['usage_count'] ?? 0 ?>

</span>

</td>

<td>

<?php if($quiz['is_published']): ?>

<span class="badge bg-success">

منشور

</span>

<?php else: ?>

<span class="badge bg-secondary">

مسودة

</span>

<?php endif; ?>

</td>

<td>

<a
href="questions.php?id=<?= $quiz['id']; ?>"
class="btn btn-info btn-sm">

<i class="bi bi-question-circle"></i>

الأسئلة

</a>

<a
href="results.php?id=<?= $quiz['id']; ?>"
class="btn btn-success btn-sm">

<i class="bi bi-bar-chart"></i>

النتائج

</a>

<a
href="analytics.php?id=<?= $quiz['id']; ?>"
class="btn btn-dark btn-sm">

<i class="bi bi-graph-up"></i>

التحليلات

</a>

<a
href="assign.php?quiz_id=<?= $quiz['id']; ?>"
class="btn btn-primary btn-sm">

<i class="bi bi-diagram-3"></i>

تعيين

</a>

<a
href="edit.php?id=<?= $quiz['id']; ?>"
class="btn btn-warning btn-sm">

<i class="bi bi-pencil"></i>

تعديل

</a>

<a
href="delete.php?id=<?= $quiz['id']; ?>"
class="btn btn-danger btn-sm"
onclick="return confirm('هل تريد حذف الاختبار؟');">

<i class="bi bi-trash"></i>

حذف

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
