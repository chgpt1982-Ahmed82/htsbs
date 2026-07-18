<?php

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    exit('Unauthorized Access');
}

$db = (new Database())->connect();

/*
|---------------------------------------------------
| جلب المعلم الحالي
|---------------------------------------------------
*/

$stmt = $db->prepare("
SELECT id
FROM teachers
WHERE user_id=?
");

$stmt->execute([
    $_SESSION['user_id']
]);

$teacher = $stmt->fetch(PDO::FETCH_ASSOC);
$teacherId = $teacher['id'] ?? 0;

/*
|---------------------------------------------------
| جلب واجبات المعلم
|---------------------------------------------------
*/

$stmt = $db->prepare("
SELECT

    a.id,
    a.title,
    a.due_date,
    a.file_path,
    a.created_at,
    c.course_name,
    c.course_code,

    (
        SELECT COUNT(*)
        FROM assignment_assignments aa
        WHERE aa.assignment_id = a.id
    ) AS assigned_classes_count,

    (
        SELECT COUNT(*)
        FROM assignment_submissions s
        WHERE s.assignment_id = a.id
    ) AS submissions_count

FROM assignments a

INNER JOIN courses c
ON a.course_id = c.id

WHERE a.teacher_id=?

ORDER BY a.created_at DESC
");

$stmt->execute([
    $teacherId
]);

$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

                                📝 بنك الواجبات

                            </h2>

                            <small class="text-muted">

                                إدارة جميع الواجبات وإعادة استخدامها وتعيينها للشعب

                            </small>

                        </div>

                        <div class="col-md-6 text-md-end mt-3 mt-md-0">

                            <a
                            href="create.php"
                            class="btn btn-success">

                                <i class="bi bi-plus-circle"></i>

                                إضافة واجب

                            </a>

                        </div>

                    </div>

                </div>

            </div>

            <?php if (isset($_SESSION['success'])): ?>

                <div class="alert alert-success alert-dismissible fade show">

                    <?= $_SESSION['success']; ?>

                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>

                </div>

                <?php unset($_SESSION['success']); ?>

            <?php endif; ?>

            <!-- Assignments Table -->

            <div class="card border-0 shadow">

                <div class="card-header bg-primary text-white">

                    <h5 class="mb-0">

                        <i class="bi bi-journal-check"></i>

                        قائمة الواجبات

                    </h5>

                </div>

                <div class="card-body">

                    <div class="table-responsive">

                        <table class="table table-bordered table-hover align-middle">

                            <thead class="table-dark">

                                <tr>

                                    <th width="60">#</th>

                                    <th>عنوان الواجب</th>

                                    <th>المقرر</th>

                                    <th>رمز المقرر</th>

                                    <th>تاريخ التسليم</th>

                                    <th>تاريخ الإنشاء</th>

                                    <th>الصفوف المعيّنة</th>

                                    <th>التسليمات</th>

                                    <th width="280">الإجراءات</th>

                                </tr>

                            </thead>

                            <tbody>

                            <?php if (empty($assignments)): ?>

                                <tr>

                                    <td colspan="9" class="text-center text-muted">

                                        لا توجد واجبات مضافة

                                    </td>

                                </tr>

                            <?php else: ?>

                                <?php foreach ($assignments as $index => $assignment): ?>

                                <tr>

                                    <td>

                                        <?= $index + 1 ?>

                                    </td>

                                    <td>

                                        <strong>

                                            <?= htmlspecialchars($assignment['title']); ?>

                                        </strong>

                                    </td>

                                    <td>

                                        <?= htmlspecialchars($assignment['course_name']); ?>

                                    </td>

                                    <td>

                                        <span class="badge bg-secondary">

                                            <?= htmlspecialchars($assignment['course_code']); ?>

                                        </span>

                                    </td>

                                    <td>

                                        <?php
                                            $due = strtotime($assignment['due_date']);
                                            $today = strtotime(date('Y-m-d'));
                                            $badgeClass = ($due < $today) ? 'bg-danger' : 'bg-warning text-dark';
                                        ?>

                                        <span class="badge <?= $badgeClass ?>">

                                            <?= date('d/m/Y', $due); ?>

                                        </span>

                                    </td>

                                    <td>

                                        <?= date('d/m/Y', strtotime($assignment['created_at'])); ?>

                                    </td>

                                    <td>

                                        <span class="badge bg-info">

                                            <?= $assignment['assigned_classes_count'] ?? 0 ?>

                                        </span>

                                    </td>

                                    <td>

                                        <span class="badge bg-success">

                                            <?= $assignment['submissions_count'] ?? 0 ?>

                                        </span>

                                    </td>

                                    <td>

                                        <a
                                        href="assign.php?id=<?= $assignment['id']; ?>"
                                        class="btn btn-primary btn-sm">

                                            <i class="bi bi-diagram-3"></i>

                                            تعيين

                                        </a>

                                        <a
                                        href="edit.php?id=<?= $assignment['id']; ?>"
                                        class="btn btn-warning btn-sm">

                                            <i class="bi bi-pencil"></i>

                                            تعديل

                                        </a>

                                        <a
                                        href="submissions.php?id=<?= $assignment['id']; ?>"
                                        class="btn btn-success btn-sm">

                                            <i class="bi bi-eye"></i>

                                            التسليمات

                                        </a>

                                        <a
                                        href="delete.php?id=<?= $assignment['id']; ?>"
                                        class="btn btn-danger btn-sm"
                                        onclick="return confirm('هل تريد حذف هذا الواجب؟');">

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
