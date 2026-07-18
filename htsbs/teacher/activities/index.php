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

$activities = $model->getAllByTeacher(
    $_SESSION['user_id']
);

include '../../app/views/layouts/header.php';

?>

<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">
<div class="card border-0 shadow-sm mb-4">

    <div class="card-body">

        <div class="row align-items-center">

            <div class="col-md-6">

                <h2 class="fw-bold mb-0">

                    📝 بنك الأنشطة

                </h2>

                <small class="text-muted">

                    إنشاء الأنشطة وإعادة استخدامها وتعيينها للشعب

                </small>

            </div>

            <div class="col-md-6 text-md-end">

                <a
                href="create.php"
                class="btn btn-success">

                    <i class="bi bi-plus-circle"></i>

                    إضافة نشاط

                </a>

                <a
                href="assign.php"
                class="btn btn-primary">

                    <i class="bi bi-diagram-3"></i>

                    تعيين الأنشطة

                </a>

            </div>

        </div>

    </div>

</div>

<div class="card border-0 shadow">

<div class="card-header bg-primary text-white">

    <h5 class="mb-0">

        <i class="bi bi-list-task"></i>

        قائمة الأنشطة

    </h5>

</div>

<div class="card-body">

<div class="table-responsive">

<table class="table table-bordered table-hover align-middle">

<thead class="table-dark">

<tr>

<th>#</th>

<th>اسم النشاط</th>

<th>المقرر</th>

<th>رمز المقرر</th>

<th>الدرجة</th>

<th>موعد التسليم</th>

<th>عدد مرات الاستخدام</th>

<th>عدد الحلول</th>

<th>الإجراءات</th>

</tr>

</thead>

<tbody>

<?php if(empty($activities)): ?>

<tr>

<td colspan="9" class="text-center">

لا توجد أنشطة

</td>

</tr>

<?php endif; ?>

<?php foreach($activities as $index => $activity): ?>

<tr>

<td>

<?= $index + 1 ?>

</td>

<td>

<?= htmlspecialchars($activity['title']) ?>

</td>

<td>

<?= htmlspecialchars($activity['course_name']) ?>

</td>

<td>

<span class="badge bg-secondary">

<?= htmlspecialchars($activity['course_code']) ?>

</span>

</td>

<td>

<span class="badge bg-success">

<?= $activity['max_grade'] ?>

</span>

</td>

<td>

<?= !empty($activity['due_date'])
? date('d/m/Y', strtotime($activity['due_date']))
: '-'; ?>

</td>

<td>

<span class="badge bg-info">

<?= $activity['usage_count'] ?>

</span>

</td>

<td>

<span class="badge bg-warning text-dark">

<?= $activity['submissions_count'] ?>

</span>

</td>

<td>

<a
href="assign.php?activity_id=<?= $activity['id']; ?>"
class="btn btn-primary btn-sm">

<i class="bi bi-diagram-3"></i>

تعيين

</a>

<a
href="submissions.php?activity_id=<?= $activity['id']; ?>"
class="btn btn-success btn-sm">

<i class="bi bi-upload"></i>

الحلول

</a>

<a
href="edit.php?id=<?= $activity['id']; ?>"
class="btn btn-warning btn-sm">

<i class="bi bi-pencil"></i>

تعديل

</a>

<a
href="delete.php?id=<?= $activity['id']; ?>"
class="btn btn-danger btn-sm"
onclick="return confirm('هل تريد حذف النشاط؟')">

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