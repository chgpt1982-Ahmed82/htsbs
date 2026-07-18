<?php

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';

if(
    !isset($_SESSION['user_id']) ||
    $_SESSION['role_id'] != 3
){
    exit('Unauthorized Access');
}

$db = (new Database())->connect();

/*
====================================
Student
====================================
*/

$stmt = $db->prepare("
SELECT
    id,
    class_id
FROM students
WHERE user_id=?
");

$stmt->execute([
    $_SESSION['user_id']
]);

$student = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$student)
{
    die('Student Not Found');
}

$classId = $student['class_id'];

/*
====================================
Lessons
====================================
*/

$stmt = $db->prepare("
SELECT

    l.id,
    l.lesson_title,

    c.course_name,
    c.course_code,

    u.full_name AS teacher_name,

    l.created_at

FROM lesson_assignments la

INNER JOIN lessons l
    ON la.lesson_id = l.id

INNER JOIN courses c
    ON l.course_id = c.id

INNER JOIN course_assignments ca
    ON c.id = ca.course_id
    AND ca.class_id = la.class_id

INNER JOIN teachers t
    ON ca.teacher_id = t.id

INNER JOIN users u
    ON t.user_id = u.id

WHERE la.class_id = ?

ORDER BY
    c.course_name,
    l.created_at DESC
");

$stmt->execute([
    $classId
]);

$lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../app/views/layouts/header.php';

?>

<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/student_sidebar.php'; ?>
<div class="main-content">


<div class="d-flex justify-content-between align-items-center mb-4">

    <h2 class="fw-bold">
        📚 دروسي
    </h2>

    <span class="badge bg-primary fs-6">

        <?= count($lessons); ?>

        درس

    </span>

</div>

<?php if(empty($lessons)): ?>

<div class="alert alert-info">

    لا توجد دروس متاحة حالياً

</div>

<?php else: ?>

<div class="card shadow border-0">

    <div class="card-header bg-primary text-white">

        <h5 class="mb-0">

            <i class="bi bi-book-fill"></i>

            قائمة الدروس

        </h5>

    </div>

    <div class="card-body">

        <div class="table-responsive">

            <table class="table table-bordered table-hover align-middle">

                <thead class="table-dark">

                <tr>

                    <th width="60">#</th>

                    <th>المقرر</th>

                    <th>رمز المقرر</th>

                    <th>اسم الدرس</th>

                    <th>المعلم</th>

                    <th>تاريخ الإضافة</th>

                    <th width="150">الإجراءات</th>

                </tr>

                </thead>

                <tbody>

                <?php foreach($lessons as $index => $lesson): ?>

                <tr>

                    <td>

                        <?= $index + 1 ?>

                    </td>

                    <td>

                        <?= htmlspecialchars(
                        $lesson['course_name']
                        ); ?>

                    </td>

                    <td>

                        <span class="badge bg-info">

                            <?= htmlspecialchars(
                            $lesson['course_code']
                            ); ?>

                        </span>

                    </td>

                    <td>

                        <?= htmlspecialchars(
                        $lesson['lesson_title']
                        ); ?>

                    </td>

                    <td>

                        <?= htmlspecialchars(
                        $lesson['teacher_name']
                        ); ?>

                    </td>

                    <td>

                        <?= date(
                        'd/m/Y',
                        strtotime(
                        $lesson['created_at']
                        )
                        ); ?>

                    </td>

                    <td>

                        <a
                        href="view_lesson.php?id=<?= $lesson['id']; ?>"
                        class="btn btn-primary btn-sm">

                            <i class="bi bi-eye"></i>

                            فتح الدرس

                        </a>

                    </td>

                </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<?php endif; ?>

</div>

</div>


</div>
<?php include '../../app/views/layouts/footer.php'; ?>

