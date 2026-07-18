<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

require_once '../config/config.php';
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/login.php");
    exit;
}

$db = (new Database())->connect();

/*
|--------------------------------------------------------------------------
| مقررات الطالب الحالية
|--------------------------------------------------------------------------
*/

$sql = "
SELECT DISTINCT

    c.course_name,
    c.course_code,

    u.full_name AS teacher_name,

    cl.class_name

FROM students s

INNER JOIN course_assignments ca
    ON s.class_id = ca.class_id

INNER JOIN courses c
    ON ca.course_id = c.id

INNER JOIN teachers t
    ON ca.teacher_id = t.id

INNER JOIN users u
    ON t.user_id = u.id

INNER JOIN classes cl
    ON s.class_id = cl.id

WHERE s.user_id = ?

ORDER BY c.course_name
";

$stmt = $db->prepare($sql);

$stmt->execute([
    $_SESSION['user_id']
]);

$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../app/views/layouts/header.php';

?>

<div class="container-fluid">

    <div class="row flex-lg-row-reverse">

        <?php include '../app/views/layouts/student_sidebar.php'; ?>
<div class="main-content">

     

            <div class="d-flex justify-content-between align-items-center mb-4">

                <h2 class="fw-bold">
                    <i class="bi bi-book-fill text-primary"></i>
                    مقرراتي الدراسية
                </h2>

            </div>

            <div class="card shadow border-0">

                <div class="card-header bg-primary text-white">

                    <h5 class="mb-0">

                        <i class="bi bi-journal-bookmark-fill"></i>

                        قائمة المقررات

                    </h5>

                </div>

                <div class="card-body">

                    <?php if(empty($courses)): ?>

                        <div class="alert alert-info text-center">

                            لا توجد مقررات مرتبطة بحسابك حالياً

                        </div>

                    <?php else: ?>

                        <div class="table-responsive">

                            <table class="table table-bordered table-hover align-middle">

                                <thead class="table-dark">

                                    <tr>

                                        <th width="60">#</th>

                                        <th>اسم المقرر</th>

                                        <th>رمز المقرر</th>

                                        <th>المعلم</th>

                                        <th>الصف</th>

                                    </tr>

                                </thead>

                                <tbody>

                                    <?php foreach($courses as $index => $course): ?>

                                        <tr>

                                            <td>

                                                <?= $index + 1; ?>

                                            </td>

                                            <td>

                                                <?= htmlspecialchars($course['course_name']); ?>

                                            </td>

                                            <td>

                                                <?= htmlspecialchars($course['course_code']); ?>

                                            </td>

                                            <td>

                                                <span class="badge bg-success">

                                                    <?= htmlspecialchars($course['teacher_name']); ?>

                                                </span>

                                            </td>

                                            <td>

                                                <?= htmlspecialchars($course['class_name']); ?>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </div>

</div>

<?php include '../app/views/layouts/footer.php'; ?>