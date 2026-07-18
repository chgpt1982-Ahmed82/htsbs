<?php

session_start();

require_once '../../config/config.php';
require_once '../../app/models/Lesson.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    exit('Unauthorized Access');
}

$model = new Lesson();

$lessons = $model->getAllByTeacher(
    $_SESSION['user_id']
);

include '../../app/views/layouts/header.php';

?>

<div class="container-fluid">

    <div class="row flex-lg-row-reverse">

        <?php include '../../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">
            <!-- Header -->

            <div class="card border-0 shadow-sm mb-4">

                <div class="card-body">

                    <div class="row align-items-center">

                        <div class="col-md-6">

                            <h2 class="fw-bold mb-0">

                                📚 بنك الدروس

                            </h2>

                            <small class="text-muted">

                                إدارة جميع الدروس وإعادة استخدامها وتعيينها للشعب

                            </small>

                        </div>

                        <div class="col-md-6 text-md-end mt-3 mt-md-0">

                            <a
                            href="create.php"
                            class="btn btn-success">

                                <i class="bi bi-plus-circle"></i>

                                إضافة درس

                            </a>

                            <a
                            href="assign.php"
                            class="btn btn-primary">

                                <i class="bi bi-diagram-3"></i>

                                تعيين الدروس

                            </a>

                        </div>

                    </div>

                </div>

            </div>

            <!-- Lessons Table -->

            <div class="card border-0 shadow">

                <div class="card-header bg-primary text-white">

                    <h5 class="mb-0">

                        <i class="bi bi-journal-richtext"></i>

                        قائمة الدروس

                    </h5>

                </div>

                <div class="card-body">

                    <div class="table-responsive">

                        <table class="table table-bordered table-hover align-middle">

                            <thead class="table-dark">

                                <tr>

                                    <th width="60">#</th>

                                    <th>عنوان الدرس</th>

                                    <th>المقرر</th>

                                    <th>رمز المقرر</th>

                                    <th>نوع الدرس</th>

                                    <th>تاريخ الإنشاء</th>

                                    <th>عدد مرات الاستخدام</th>

                                    <th width="260">الإجراءات</th>

                                </tr>

                            </thead>

                            <tbody>

                            <?php if(empty($lessons)): ?>

                                <tr>

                                    <td colspan="8" class="text-center text-muted">

                                        لا توجد دروس مضافة

                                    </td>

                                </tr>

                            <?php else: ?>

                                <?php foreach($lessons as $index => $lesson): ?>

                                <tr>

                                    <td>

                                        <?= $index + 1 ?>

                                    </td>

                                    <td>

                                        <strong>

                                            <?= htmlspecialchars($lesson['lesson_title']); ?>

                                        </strong>

                                    </td>

                                    <td>

                                        <?= htmlspecialchars($lesson['course_name']); ?>

                                    </td>

                                    <td>

                                        <span class="badge bg-secondary">

                                            <?= htmlspecialchars($lesson['course_code']); ?>

                                        </span>

                                    </td>

                                    <td>

                                        <span class="badge bg-info">

                                            <?= htmlspecialchars($lesson['lesson_type']); ?>

                                        </span>

                                    </td>

                                    <td>

                                        <?= date(
                                            'd/m/Y',
                                            strtotime($lesson['created_at'])
                                        ); ?>

                                    </td>

                                    <td>

                                        <span class="badge bg-success">

                                            <?= $lesson['usage_count'] ?? 0 ?>

                                        </span>

                                    </td>

                                    <td>

                                        <a
                                        href="assign.php?lesson_id=<?= $lesson['id']; ?>"
                                        class="btn btn-primary btn-sm">

                                            <i class="bi bi-diagram-3"></i>

                                            تعيين

                                        </a>

                                        <a
                                        href="edit.php?id=<?= $lesson['id']; ?>"
                                        class="btn btn-warning btn-sm">

                                            <i class="bi bi-pencil"></i>

                                            تعديل

                                        </a>

                                        <a
                                        href="delete.php?id=<?= $lesson['id']; ?>"
                                        class="btn btn-danger btn-sm"
                                        onclick="return confirm('هل تريد حذف هذا الدرس؟');">

                                            <i class="bi bi-trash"></i>

                                            حذف

                                        </a>

                                    </td>

                                </tr>

                                <?php endforeach; ?>

                            <?php endif; ?>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

<?php include '../../app/views/layouts/footer.php'; ?>

