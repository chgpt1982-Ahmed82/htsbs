<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

require_once '../config/config.php';
require_once '../app/models/CourseAssignment.php';

if (!isset($_SESSION['user_id'])) {

    header(
        "Location: " .
        BASE_URL .
        "/login.php"
    );

    exit;
}

$model = new CourseAssignment();

$courses = $model->getTeacherCourses(
    $_SESSION['user_id']
);

include '../app/views/layouts/header.php';

?>


<<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">
        <h2 class="mb-4">

            My Courses

        </h2>

        <table class="table table-bordered">

            <thead class="table-dark">

            <tr>

                <th>Course</th>

                <th>Course Code</th>

                <th>Class</th>

                <th>Semester</th>

                <th>Academic Year</th>

            </tr>

            </thead>

            <tbody>

            <?php foreach($courses as $course): ?>

            <tr>

                <td>

                    <?= htmlspecialchars($course['course_name']); ?>

                </td>

                <td>

                    <?= htmlspecialchars($course['course_code']); ?>

                </td>

                <td>

                    <?= htmlspecialchars($course['class_name']); ?>

                </td>

                <td>

                    <?= htmlspecialchars($course['semester']); ?>

                </td>

                <td>

                    <?= htmlspecialchars($course['academic_year']); ?>

                </td>

            </tr>

            <?php endforeach; ?>

            </tbody>

        </table>

    </div>

</div>


</div>

<?php

include '../app/views/layouts/footer.php';

?>
